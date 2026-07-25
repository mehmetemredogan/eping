<p align="center"><b>🇹🇷 Türkçe</b> · <a href="README.en.md">🇬🇧 English</a></p>

# ePing (Extended Ping) — Go UI

Laravel API üzerinden hedef listesi ve oturum yöneten terminal istemci.
Gecikme: yerel HTTP **TTFB** (DNS/TCP/TLS kırılımı, p50/p95 ile birlikte).
Yol: OS `tracert` / `traceroute` / `tracepath` (hop bazlı analiz).

## Kurulum

```bash
cd ui
go mod tidy
go run .
```

Varsayılan API adresi kodda gömülüdür: `https://ping.mehmetemredogan.tr`.
Config yoksa bu adres kullanılır. Çalışırken TUI’daki API alanından değiştirip
Enter’a basınca hem anında geçerli olur hem de `config.yaml`’a yazılır.
Kalıcı düzenleme: `%AppData%/eping/config.yaml` (Windows) /
`~/.config/eping/config.yaml` (Linux/macOS), veya `EPING_API_URL`.

## Kısayollar

| Tuş | İşlem |
|-----|--------|
| `/` | Arama |
| `[` `]` | Kategori |
| `enter` | Ölç + traceroute |
| `a` | Filtrelenmiş tümünü ölç |
| `e` | Grubu aç/kapa |
| `i` | Detay paneli (p50/p95, DNS/TCP/TLS, hop tablosu, geçmişe göre eğilim) |
| `l` | Giriş yap |
| `o` | Çıkış (oturum) |
| `r` | Yenile |
| `q` | Çıkış |

## Ölçüm detayları

**Ping (HTTP TTFB)** ve **Tracert (traceroute)** sonuçları her zaman ayrı
etiketli bölümler halinde gösterilir, hiçbir yerde birleştirilip tek satırda
karıştırılmaz:

- Hedef listesinde: ping satırının hemen altında `↳ Tracert: ...` şeklinde
  ayrı, farklı renkte bir satır.
- Alt bilgi panelinde: `Ping: ...` ve `Tracert: ...` iki ayrı satır.
- `i` (detay) panelinde: `── PING (HTTP TTFB) ──` ve `── TRACEROUTE ──`
  başlıklı iki ayrı bölüm.

Ping bölümü; ortalama/min/maks/jitter yanında **p50/p95** yüzdelik
dilimlerini ve her isteğin **DNS / TCP / TLS / TTFB** kırılımını gösterir.
Tracert bölümü etkinse hop hop IP, gecikme ve sınıflandırma (loopback /
link-local / private / CGNAT / public) gösterilir.

## Geçmişle karşılaştırma (giriş yapılmış kullanıcılar)

Giriş yapıldığında istemci, sunucudaki `/api/v1/results/trend` uç noktasından
kullanıcının geçmiş ölçümlerini çeker ve:

- Üst bilgi çubuğunda genel ağ eğilimini (`↑ iyileşiyor` / `↓ kötüleşiyor` /
  `→ stabil`) gösterir.
- Her ölçümden sonra o hedefin son sonucunu kendi geçmiş ortalamasıyla
  karşılaştırıp ("Geçmişe göre: %18 daha hızlı (iyileşiyor, 32 geçmiş ölçüm)")
  şeklinde bir içgörü ekler.

Karşılaştırma, en son birkaç ölçümün ortalamasını (recent) önceki geçmişin
ortalamasıyla (baseline) kıyaslar; yeterli geçmiş veri yoksa "yetersiz veri"
olarak işaretlenir.

## Derleme

Çoklu platform (Windows/Linux/macOS) derleme seçenekleri ve otomatik release
süreci için bkz. [`../docs/BUILD.md`](../docs/BUILD.md). Hızlı özet:

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
