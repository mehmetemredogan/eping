<p align="center"><b>🇹🇷 Türkçe</b> · <a href="README.en.md">🇬🇧 English</a></p>

# ePing (Extended Ping) — Go Terminal İstemcisi

ePing Go istemcisi, yüksek doğruluklu HTTP TTFB, traceroute analizi ve **gerçek web servisleri üzerinden ağ kalitesi testi** (Network Quality Test) gerçekleştiren çok modlu bir ağ test aracıdır.

## Çalıştırma Modları

İstemci üç farklı modda çalıştırılabilir:

```text
KULLANIM:
  eping                    İnteraktif terminal arayüzünü (TUI) başlatır
  eping quality [bayraklar] Gerçek web servisleri üzerinden ağ kalitesini test eder
  eping daemon [bayraklar]  Arkaplanda periyodik olarak ağ kalitesini izler ve raporlar
  eping version            Versiyon bilgisini gösterir
```

---

### 1. İnteraktif TUI Modu (`eping`)

Tüm hedefleri listelemek, aramak, filtrelemek ve anlık ping/traceroute ölçümleri yapmak için kullanılır.

```bash
cd ui
go run .
# veya derlenmiş ikili dosya ile:
./pinglab.exe
```

#### TUI Klavye Kısayolları

| Tuş | İşlem |
|---|---|
| `/` | Canlı arama ve filtreleme |
| `[` `]` | Kategori değiştirme (AWS, Azure, CDN, Oyun vb.) |
| `enter` / `space` | Seçili hedefi test et (HTTP TTFB + Traceroute) |
| `a` | Filtrelenmiş tüm hedefleri toplu test et (ortak `session_id` ile) |
| `n` | **Gerçek Web Ağ Kalite Testini başlat (Skor & Derece)** |
| `e` | Sağlayıcı grubunu aç/kapat (katla/genişlet) |
| `i` | Detay paneli (p50/p95, DNS/TCP/TLS, hop tablosu, geçmiş eğilim) |
| `l` | Platforma kullanıcı girişi yap (`username` / `password`) |
| `o` | Oturumu kapat |
| `r` | Hedefleri API'den yeniden yükle |
| `q` | Çıkış |

---

### 2. CLI Ağ Kalite Testi (`eping quality`)

Arayüze girmeden doğrudan terminal üzerinden 12 bağımsız web servisine (Google, Cloudflare, Microsoft, Apple, GitHub, AWS, Wikipedia, YouTube, Netflix, e-Devlet, Trendyol, Hetzner) eşzamanlı istek atarak kapsamlı bir ağ kalitesi karnesi oluşturur.

```bash
# Terminal özet kartı ile ölçüm:
go run . quality

# JSON çıktısı (CI/CD veya otomasyonlar için):
go run . quality --json

# Sunucuya kaydetmeden yalnızca yerel test:
go run . quality --no-upload

# Özel zaman aşımı belirterek:
go run . quality --timeout 8s
```

#### Ölçülen Metrikler ve Karne:
- **DNS Çözümleme Süresi (DNS Lookup)**
- **TCP Bağlantı Süresi (TCP Connect)**
- **TLS El Sıkışma Süresi (TLS Handshake)**
- **İlk Bayt Süresi (TTFB - Time To First Byte)**
- **Toplam İstek Süresi & HTTP Durum Kodu (200, 301...)**
- **0–100 Kalite Skoru ve Derece (A+, A, B, C, D, F)**
- **Ağ Durumu (`excellent`, `good`, `fair`, `poor`, `critical`)**

---

### 3. Arka Plan Daemon Modu (`eping daemon`)

Headless sunucularda veya geliştirici bilgisayarlarında arka planda bir servis gibi çalışarak periyodik aralıklarla ağ kalitesini test eder ve platforma raporlar.

```bash
# Daemon modunu başlat:
go run . daemon
# veya derlenmiş binary ile:
./pinglab.exe daemon

# Tek bir döngü çalıştırıp hemen çıkmak için (cron / container healthcheck):
go run . daemon --once
```

> [!IMPORTANT]
> **Dinamik ve Rastgele Ölçüm Aralığı:** Ağ kalite testlerinde ölçüm periyodu kullanıcı tarafından belirlenemez. Ölçümler **her seferinde 15 dakika ile 60 dakika arasında rastgele** olarak otomatik planlanır. Bu sayede hedef sunucularda yapay trafik yükü oluşmaz ve günün farklı saatlerinde doğal ağ davranışı örneklenir.

#### İşletim Sistemi Başlangıcına Ekleme (Autostart)
- **Windows (PowerShell Görevi):**
  `schtasks /create /tn "ePingDaemon" /tr "C:\eping\pinglab.exe daemon" /sc onlogon /rl limited`
- **Linux (systemd User Service):**
  `~/.config/systemd/user/eping.service` oluşturup `systemctl --user enable --now eping.service` çalıştırın.
- **macOS (launchd Agent):**
  `~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist` oluşturup `launchctl load ...` çalıştırın.
(Detaylı kılavuz için projenin ana [README.md](../README.md#i̇şletim-sistemi-açılışına-ekleme) dokümanına bakın.)

---

## Yapılandırma

İstemci yapılandırmayı şu sırayla okur:
1. Ortam Değişkeni: `EPING_API_URL` veya `PINGLAB_API_URL`
2. Dosya: `%AppData%/eping/config.yaml` (Windows) veya `~/.config/eping/config.yaml` (Linux/macOS)
3. Kod içi varsayılan adres: `https://ping.mehmetemredogan.tr`

Örnek `config.yaml`:
```yaml
api_url: https://ping.mehmetemredogan.tr
samples: 4
concurrency: 6
token: "1|abcdef..."
username: "med"
trace_on_measure: true
trace_on_all: false
```

TUI içerisindeyken API adresi değiştirildiğinde Enter'a basıldığında `config.yaml` dosyasına otomatik kaydedilir.

---

## Ölçüm Detayları

**Ping (HTTP TTFB)** ve **Tracert (traceroute)** sonuçları her zaman ayrı etiketli bölümler halinde gösterilir:
- Hedef listesinde: ping satırının hemen altında `↳ Tracert: ...` şeklinde ayrı, renkli bir satır.
- Alt bilgi panelinde: `Ping: ...` ve `Tracert: ...` iki ayrı satır.
- `i` (detay) panelinde: `── PING (HTTP TTFB) ──` ve `── TRACEROUTE ──` başlıklı iki ayrı bölüm.

---

## Derleme

Çoklu platform (Windows/Linux/macOS) derleme seçenekleri için bkz. [`../docs/BUILD.md`](../docs/BUILD.md). Hızlı özet:

```bash
# Sadece mevcut platform için
go build -o eping .

# Tüm platformlar için (Makefile)
make build-all

# Windows'ta PowerShell ile
./build.ps1

# Linux/macOS'ta shell ile
./build.sh
```
