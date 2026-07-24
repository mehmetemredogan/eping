/**
 * Client-side latency probe (from the visitor's browser only).
 *
 * Target metric ≈ Chrome DevTools "Waiting for server response".
 *
 * Browsers hide responseStart/requestStart for cross-origin hosts without
 * Timing-Allow-Origin. We therefore:
 *   1) preconnect + warmup to pay DNS/TCP/TLS once
 *   2) measure subsequent requests on the reused socket
 *   3) prefer exact Waiting when TAO exposes it; otherwise use the hot
 *      connection timing (wall / duration), never a cold full waterfall
 */
const DEFAULT_WARMUP = 2;
const DEFAULT_SAMPLES = 5;
const DEFAULT_TIMEOUT = 4000;

function isIp(host) {
    return /^(?:\d{1,3}\.){3}\d{1,3}$/.test(host) || (host.includes(':') && !host.includes('.'));
}

function normalizeHost(host) {
    let h = host.replace(/^https?:\/\//i, '').split('/')[0];
    // strip port for hostname (not IPv6)
    if (substrCount(h, ':') === 1 && !h.startsWith('[')) {
        h = h.split(':')[0];
    }
    return h;
}

function substrCount(str, ch) {
    return str.split(ch).length - 1;
}

function resolveScheme(host) {
    return isIp(normalizeHost(host)) ? 'http' : 'https';
}

function originOf(host) {
    return `${resolveScheme(host)}://${normalizeHost(host)}`;
}

function buildProbeUrl(host) {
    const bust = `${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
    return `${originOf(host)}/favicon.ico?_ping=${bust}`;
}

function exactWaiting(entry) {
    if (!entry) return null;
    if (entry.requestStart > 0 && entry.responseStart >= entry.requestStart) {
        return entry.responseStart - entry.requestStart;
    }
    return null;
}

function findEntry(url, notBefore) {
    const entries = performance.getEntriesByName(url, 'resource');
    for (let i = entries.length - 1; i >= 0; i--) {
        if (entries[i].startTime >= notBefore - 5) {
            return entries[i];
        }
    }
    return entries.length ? entries[entries.length - 1] : null;
}

async function nextFrame() {
    await new Promise((r) => requestAnimationFrame(r));
}

/**
 * Open DNS/TCP/TLS in the background so sample requests skip the handshake.
 */
function preconnect(host) {
    const href = originOf(host);
    return new Promise((resolve) => {
        const link = document.createElement('link');
        link.rel = 'preconnect';
        link.href = href;
        // no crossOrigin → connection can be reused by no-cors / image fetches
        document.head.appendChild(link);

        const dns = document.createElement('link');
        dns.rel = 'dns-prefetch';
        dns.href = href;
        document.head.appendChild(dns);

        // Browsers don't signal when preconnect finishes; give the handshake time.
        window.setTimeout(() => resolve(), 200);
    });
}

/**
 * One opaque request. Returns { exact, hot, cold, wall, entry }.
 * - exact: DevTools Waiting when TAO allows
 * - hot: duration/wall suitable after preconnect (≈ waiting + tiny body)
 * - cold: full waterfall (includes connect/TLS) — never used as the result
 */
async function probeFetch(url, timeoutMs) {
    const mark = performance.now();
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    let wall = null;

    try {
        await fetch(url, {
            method: 'GET',
            mode: 'no-cors',
            cache: 'no-store',
            credentials: 'omit',
            redirect: 'follow',
            signal: controller.signal,
            keepalive: true,
        });
        wall = performance.now() - mark;
    } catch {
        wall = performance.now() - mark;
    } finally {
        clearTimeout(timer);
    }

    await nextFrame();
    const entry = findEntry(url, mark);
    const exact = exactWaiting(entry);

    const duration = entry && entry.duration > 0 ? entry.duration : null;
    const hot = exact ?? duration ?? wall;
    const cold = duration ?? wall;

    return {
        exact,
        hot: hot !== null && Number.isFinite(hot) ? hot : null,
        cold: cold !== null && Number.isFinite(cold) ? cold : null,
        wall,
        entry,
    };
}

async function probeImage(url, timeoutMs) {
    return new Promise((resolve) => {
        const mark = performance.now();
        const img = new Image();
        let settled = false;

        const finish = async () => {
            if (settled) return;
            settled = true;
            clearTimeout(timer);
            const wall = performance.now() - mark;
            img.onload = null;
            img.onerror = null;
            await nextFrame();
            const entry = findEntry(url, mark);
            img.src = '';
            const exact = exactWaiting(entry);
            const duration = entry && entry.duration > 0 ? entry.duration : null;
            resolve({
                exact,
                hot: exact ?? duration ?? wall,
                cold: duration ?? wall,
                wall,
                entry,
            });
        };

        const timer = setTimeout(() => finish(), timeoutMs);
        img.onload = () => finish();
        img.onerror = () => finish();
        img.referrerPolicy = 'no-referrer';
        img.src = url;
    });
}

async function probeOnce(host, timeoutMs) {
    const url = buildProbeUrl(host);
    let result = await probeFetch(url, timeoutMs);
    if (result.hot !== null) {
        return result;
    }
    return probeImage(url, timeoutMs);
}

function median(values) {
    if (!values.length) return null;
    const sorted = [...values].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 === 0
        ? (sorted[mid - 1] + sorted[mid]) / 2
        : sorted[mid];
}

/**
 * Drop cold-handshake outliers: keep the cluster of lower timings.
 * If preconnect worked, most samples are ~Waiting; a cold one is 2–3× larger.
 */
function selectHotSamples(samples, coldHint) {
    const values = samples
        .map((s) => (s.exact !== null && s.exact > 0 ? s.exact : s.hot))
        .filter((v) => v !== null && Number.isFinite(v) && v >= 0)
        .map((v) => Math.round(v * 100) / 100);

    if (!values.length) {
        return [];
    }

    // Prefer exact Waiting samples when any exist.
    const exacts = samples
        .map((s) => s.exact)
        .filter((v) => v !== null && Number.isFinite(v) && v > 0)
        .map((v) => Math.round(v * 100) / 100);
    if (exacts.length) {
        return exacts;
    }

    const med = median(values);
    const coldRef = coldHint && coldHint > med * 1.4 ? coldHint : null;

    // Keep samples clearly below a known cold waterfall.
    let hot = coldRef
        ? values.filter((v) => v < coldRef * 0.75)
        : values;

    // Or keep those close to the median / min cluster (not 2× the best).
    const best = Math.min(...values);
    hot = (hot.length ? hot : values).filter((v) => v <= best * 1.8 || v <= med * 1.35);

    return hot.length ? hot : [best];
}

export async function measureLatency(host, options = {}) {
    const warmup = options.warmup ?? DEFAULT_WARMUP;
    const samples = options.samples ?? DEFAULT_SAMPLES;
    const timeoutMs = options.timeoutMs ?? DEFAULT_TIMEOUT;

    // 1) Establish client-side connection (DNS/TCP/TLS) from THIS browser.
    await preconnect(host);

    let coldHint = null;
    const raw = [];

    // 2) Warmup requests (discarded) — finish paying for handshake if preconnect was late.
    for (let i = 0; i < warmup; i++) {
        const result = await probeOnce(host, timeoutMs);
        if (result.cold !== null) {
            coldHint = coldHint === null ? result.cold : Math.max(coldHint, result.cold);
        }
        await new Promise((r) => setTimeout(r, 30));
    }

    // 3) Samples on the reused client connection.
    for (let i = 0; i < samples; i++) {
        const result = await probeOnce(host, timeoutMs);
        raw.push(result);
        if (result.cold !== null && (coldHint === null || result.cold > coldHint)) {
            // Only raise coldHint from early samples that look like full waterfall.
            if (i === 0 && result.exact === null) {
                coldHint = Math.max(coldHint ?? 0, result.cold);
            }
        }
        if (i < samples - 1) {
            await new Promise((r) => setTimeout(r, 40));
        }
    }

    const latencies = selectHotSamples(raw, coldHint);
    const received = latencies.length;
    const packetLoss = samples > 0 ? ((samples - received) / samples) * 100 : 100;

    if (received === 0) {
        return {
            status: 'failed',
            min_latency_ms: null,
            max_latency_ms: null,
            avg_latency_ms: null,
            jitter_ms: null,
            packet_loss_percent: 100,
            packets_sent: samples,
            packets_received: 0,
            samples: [],
            warmup_ms: coldHint !== null ? round2(coldHint) : null,
            warmup_excluded: true,
            metric: 'waiting',
        };
    }

    const min = Math.min(...latencies);
    const max = Math.max(...latencies);
    const avg = latencies.reduce((a, b) => a + b, 0) / received;
    let jitter = null;

    if (received >= 2) {
        const diffs = [];
        for (let i = 1; i < latencies.length; i++) {
            diffs.push(Math.abs(latencies[i] - latencies[i - 1]));
        }
        jitter = diffs.reduce((a, b) => a + b, 0) / diffs.length;
    }

    return {
        status: 'success',
        min_latency_ms: round2(min),
        max_latency_ms: round2(max),
        avg_latency_ms: round2(avg),
        jitter_ms: jitter === null ? null : round2(jitter),
        packet_loss_percent: round2(Math.max(0, ((samples - received) / samples) * 100)),
        packets_sent: samples,
        packets_received: received,
        samples: latencies,
        warmup_ms: coldHint !== null ? round2(coldHint) : null,
        warmup_excluded: true,
        metric: 'waiting',
    };
}

function round2(n) {
    return Math.round(n * 100) / 100;
}

export function latencyClass(ms) {
    if (ms === null || ms === undefined) return 'latency-none';
    if (ms < 80) return 'latency-ok';
    if (ms < 180) return 'latency-warn';
    return 'latency-bad';
}
