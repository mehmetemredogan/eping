/**
 * Detect the visitor's recursive DNS resolver and EDNS Client Subnet (ECS).
 * Must run in the browser so the OS resolver performs the lookup.
 * Uses https://edns.ip-api.com (unique subdomain per request).
 */

function randomLabel(length = 32) {
    const alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const bytes = crypto.getRandomValues(new Uint8Array(length));
    let out = '';
    for (let i = 0; i < length; i++) {
        out += alphabet[bytes[i] % alphabet.length];
    }
    return out;
}

function normalizePayload(data) {
    if (!data || typeof data !== 'object') {
        return null;
    }

    const dns = data.dns && typeof data.dns === 'object' ? data.dns : null;
    const edns = data.edns && typeof data.edns === 'object' ? data.edns : null;

    if (!dns?.ip && !edns?.ip) {
        return null;
    }

    return {
        dns: dns
            ? {
                  ip: dns.ip || null,
                  geo: dns.geo || null,
              }
            : null,
        edns: edns
            ? {
                  ip: edns.ip || null,
                  geo: edns.geo || null,
                  // ECS subnet approximation when only a network address is returned
                  ecs: edns.ip ? `${edns.ip}` : null,
              }
            : null,
        source: 'edns.ip-api.com',
        detected_at: new Date().toISOString(),
    };
}

/**
 * @returns {Promise<object|null>}
 */
export async function detectClientDns(timeoutMs = 8000) {
    const host = `${randomLabel()}.edns.ip-api.com`;
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const res = await fetch(`https://${host}/json`, {
            method: 'GET',
            mode: 'cors',
            cache: 'no-store',
            signal: controller.signal,
        });

        if (!res.ok) {
            return null;
        }

        return normalizePayload(await res.json());
    } catch {
        // Fallback redirecting endpoint (still HTTPS).
        try {
            const res = await fetch('https://edns.ip-api.com/json', {
                method: 'GET',
                mode: 'cors',
                cache: 'no-store',
                redirect: 'follow',
                signal: controller.signal,
            });

            if (!res.ok) {
                return null;
            }

            return normalizePayload(await res.json());
        } catch {
            return null;
        }
    } finally {
        clearTimeout(timer);
    }
}
