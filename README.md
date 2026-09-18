<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777bb4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Go-1.24%2B-00add8?logo=go&logoColor=white" alt="Go">
  <a href="https://github.com/mehmetemredogan/eping/actions/workflows/ui-ci.yml"><img src="https://github.com/mehmetemredogan/eping/actions/workflows/ui-ci.yml/badge.svg" alt="UI CI"></a>
  <img src="https://img.shields.io/badge/license-MIT-informational" alt="License">
</p>

<p align="center"><b>🇹🇷 Türkçe</b> · <a href="README.en.md">🇬🇧 English</a></p>

# ePing (Extended Ping)

ePing, farklı bulut sağlayıcıları, oyun sunucuları ve CDN'lere olan ağ gecikmenizi
ölçmenizi sağlayan bir gecikme (latency) test platformudur. İki parçadan oluşur:

- **Web uygulaması** (bu depo — Laravel 13): Üyelik/oturum yönetimi, terminal
  istemcisiyle yapılan testlerin görüntülendiği üye paneli, admin paneli ve
  terminal istemcisinin kullandığı REST API'yi sunar. Tarayıcı üzerinden ping
  testi **yapılmaz** — ölçüm yalnızca terminal istemcisi ile gerçekleştirilir.
- **Terminal istemcisi** ([`ui/`](ui/) — Go): API'ye bağlanan, HTTP gecikme ölçümü
  ve traceroute analizini bir arada gösteren, asıl test aracı olan TUI (terminal
  kullanıcı arayüzü).

**Bu projeyi neden yazdık?**

Biz yazılım geliştiriyor ve bu yazılımların yönetimini yapıyoruz.
Hangi platforma hangi rotalardan ve ne kadar gecikme ile bağlandığımızı görmek bizim
verimli altyapılar oluşturmamız için kritik öneme sahip. Ayrıca günlük hayatımızda
oynadığımız çevrimiçi oyunlar ve kullandığımız platformlara da en iyi şekilde erişim
sağlamak istiyoruz.

ePing yazılımı aracılığı ile internete en iyi rotalarla en hızlı şekilde ulaşabildiğimiz
internet servis sağlayıcıları tespit edip aboneliklerimizi ona göre değiştirmek istiyoruz.

ePing ile toplanan veriler anonimleştirilerek istatistikler bölümünde yayınlanır.

## İçindekiler

- [Özellikler](#özellikler)
- [Mimari](#mimari)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Geliştirme](#geliştirme)
- [Test](#test)
- [Terminal istemcisi (ui/)](#terminal-i̇stemcisi-yapılandırması-ui)
- [İşletim sistemi açılışına ekleme](#işletim-sistemi-açılışına-ekleme)
- [Dil desteği](#dil-desteği)
- [CI/CD ve derleme](#cicd-ve-derleme)
- [Klasör yapısı](#klasör-yapısı)
- [Dokümantasyon](#dokümantasyon)
- [Katkıda bulunma](#katkıda-bulunma)
- [Lisans](#lisans)

## Özellikler

- **Global hedef listesi** — AWS, Azure, GCP, Cloudflare, DigitalOcean, Oracle,
  Hetzner, Vultr, OVH, oyun sunucuları ve daha fazlası, kategoriye ve sağlayıcıya
  göre gruplanmış (terminal istemcisi üzerinden test edilir).
- **Terminal istemcisi (TUI)** — HTTP TTFB (DNS/TCP/TLS kırılımı, p50/p95) ve
  OS `tracert`/`traceroute` tabanlı hop analizi.
- **Gerçek Web Ağ Kalite Testi (Network Quality Test)** — Google, Cloudflare, Microsoft,
  Apple, GitHub, AWS, Wikipedia, YouTube, Netflix, e-Devlet, Trendyol, Hetzner gibi
  12 farklı bağımsız web servisine HTTP/TLS/TCP probe istekleri atar; DNS, TCP, TLS,
  TTFB, toplam süre, paket kaybı, 0–100 kalite skoru ve A+/F dereceleme üretir.
- **Arka Plan İzleme Servisi (Daemon Modu)** — Headless olarak arka planda çalışarak
  periyodik aralıklarla ağ kalitesini ölçer, loglar ve platforma otomatik kaydeder.
- **Ağ Kalitesi Web Paneli** — `/quality` üzerinden test geçmişi, skorlar ve
  servis bazlı kırılımlar görsel olarak incelenebilir.
- **Üye paneli** — Terminal istemcisiyle yaptığınız testlerin geçmişini tarihe ve
  oturum kimliğine (`session_id`) göre listeler.
- **Geçmişle karşılaştırma** — Giriş yapan kullanıcılar için geçmiş ölçümlere
  göre iyileşme/kötüleşme trendi (API üzerinden, `/api/v1/results/trend`).
- **Admin paneli** — Hedef, sağlayıcı ve test logu yönetimi; dashboard istatistikleri.
- **Basit kimlik doğrulama** — Yalnızca kullanıcı adı + parola (e-posta/isim istenmez),
  API tarafında Sanctum token ile.
- **Çok dilli arayüz** — Türkçe ve İngilizce arasında anlık geçiş (bkz. [Dil desteği](#dil-desteği)).

## Mimari

Backend, hedef listesini, test sonuçlarını ve ağ kalite testlerini PostgreSQL (veya SQLite, test ortamı)
üzerinde saklar. Ping ve ağ kalitesi ölçümü yalnızca Go terminal istemcisi tarafından yapılır ve
sonuçlar REST API üzerinden gönderilir; web uygulaması bu sonuçları üye paneli, ağ kalitesi sayfası
ve admin panelinde görüntüler.

## Gereksinimler

- PHP >= 8.3, Composer
- Node.js >= 18, npm
- PostgreSQL (üretim/geliştirme) veya SQLite (test)
- Go >= 1.24 (yalnızca `ui/` terminal istemcisini derlemek için)

## Kurulum

```bash
git clone git@github.com:mehmetemredogan/eping.git
cd eping

composer install
cp .env.example .env
php artisan key:generate

# .env içinde DB_* / DATABASE_URL değerlerini düzenleyin
php artisan migrate --seed

npm install
npm run build
```

Tek komutla kurulum için (bağımlılıklar, .env, migration, frontend build):

```bash
composer run setup
```

## Çalıştırma Şekilleri

ePing farklı senaryolara uygun çeşitli çalıştırma modlarına sahiptir:

### 1. Web Sunucusu (Laravel)

```bash
# Sadece web sunucusunu başlatmak için:
php artisan serve

# Geliştirme ortamında sunucu, kuyruk dinleyici ve Vite'ı birlikte çalıştırmak için:
composer run dev
```

Uygulama varsayılan olarak `http://localhost:8000` adresinde çalışır.

### 2. Terminal İstemcisi — İnteraktif TUI Modu

Tüm hedefleri listelemek, filtrelemek ve anlık ölçüm yapmak için:

```bash
cd ui
go run .
# veya derlenmiş binary ile:
./pinglab.exe
```

**TUI Kısayolları:**

| Tuş | İşlem |
|---|---|
| `/` | Canlı arama ve filtreleme |
| `[` `]` | Kategori değiştirme |
| `enter` / `space` | Seçili hedefi test et (HTTP TTFB + Traceroute) |
| `a` | Filtrelenmiş tüm hedefleri toplu test et (`session_id` ile) |
| `n` | **Gerçek Web Ağ Kalite Testini başlat (Skor & Derece hesaplar)** |
| `e` | Sağlayıcı grubunu aç/kapat |
| `i` | Detay paneli (p50/p95, DNS/TCP/TLS, hop tablosu, geçmiş eğilim) |
| `l` | Platforma giriş yap (kullanıcı adı & parola) |
| `o` | Oturumu kapat |
| `r` | Hedefleri API'den yeniden yükle |
| `q` | Çıkış |

### 3. Terminal İstemcisi — CLI Ağ Kalite Testi (Tek Seferlik)

Arayüze girmeden, terminal üzerinden hızlıca 12 farklı web servisine (Google, Cloudflare, Apple, Netflix, e-Devlet vb.) probe atıp skor ve ANSI özet kartı almak için:

```bash
cd ui
go run . quality
# veya
./pinglab.exe quality
```

**Bayraklar:**
- `--json` : Sonuçları otomasyonlar için JSON formatında yazdırır (`go run . quality --json`).
- `--no-upload` : Oturum açık olsa dahi sunucuya kaydetmez, sadece yerel rapor üretir.
- `--timeout <süre>` : Hedef başına zaman aşımı süresi (varsayılan: `6s`).

### 4. Terminal İstemcisi — Arka Plan Daemon Modu (Headless İzleme)

Headless sunucularda veya kişisel bilgisayarınızda arka planda otomatik olarak ağ kalitesini izlemek için:

```bash
cd ui
go run . daemon
# veya derlenmiş ikili dosya ile:
./pinglab.exe daemon
```

> [!IMPORTANT]
> **Dinamik Ölçüm Aralığı:** Ağ kalite testlerinde ölçüm periyodu kullanıcı tarafından belirlenemez. Hedef sunucularda yapay trafik yığılmasını önlemek ve günün farklı dilimlerinde gerçekçi ağ kalitesi örneği toplayabilmek amacıyla ölçüm aralığı **her seferinde minimum 15 dakika ile maksimum 60 dakika arasında rastgele** olarak otomatik belirlenir. Örneğin ilk testten sonra 22 dakika, sonrakinde 47 dakika, sonrakinde 18 dakika sonra test çalıştırılır.

**Bayraklar:**
- `--once` : Yalnızca tek bir ölçüm döngüsü çalıştırıp hemen çıkar (cron görevleri veya container healthcheck senaryoları için).

---

## İşletim Sistemi Açılışına Ekleme (Autostart / Boot Service)

`eping daemon` modunun bilgisayar veya sunucu her başladığında otomatik olarak arka planda çalışması için aşağıdaki adımları uygulayabilirsiniz:

### 🪟 Windows (Görev Zamanlayıcısı veya Başlangıç Klasörü)

#### Seçenek A: Komut Satırı / PowerShell ile (Önerilen)
Yönetici PowerShell terminalinde aşağıdaki komutu çalıştırarak kullanıcınız oturum açtığında otomatik başlayan bir görev tanımlayabilirsiniz:

```powershell
# 'C:\eping\pinglab.exe' yolunu kendi dosya yolunuzla değiştirin:
schtasks /create /tn "ePingDaemon" /tr "C:\eping\pinglab.exe daemon" /sc onlogon /rl limited
```

Görevi durdurmak veya kaldırmak için:
```powershell
schtasks /delete /tn "ePingDaemon" /f
```

#### Seçenek B: Başlangıç (Startup) Klasörü
1. `Win + R` tuşlarına basın ve `shell:startup` yazıp Enter'a basın.
2. Açılan klasörün içine `pinglab.exe` için bir kısayol oluşturun.
3. Kısayola sağ tıklayıp **Özellikler** penceresini açın.
4. **Hedef** alanının sonuna ` daemon` ekleyin (örn: `C:\eping\pinglab.exe daemon`).
5. **Çalıştır** kutusunu "Simge durumuna küçültülmüş" olarak ayarlayın.

---

### 🐧 Linux (systemd User Service)

Linux üzerinde kullanıcı oturumu ile birlikte başlayıp arka planda servis olarak çalışması için:

1. Servis dizinini oluşturun:
   ```bash
   mkdir -p ~/.config/systemd/user
   ```

2. `~/.config/systemd/user/eping.service` dosyasını oluşturun:
   ```ini
   [Unit]
   Description=ePing Network Quality Daemon
   After=network-online.target
   Wants=network-online.target

   [Service]
   Type=simple
   ExecStart=/usr/local/bin/eping daemon
   Restart=always
   RestartSec=15

   [Install]
   WantedBy=default.target
   ```

3. Servisi etkinleştirin ve başlatın:
   ```bash
   systemctl --user daemon-reload
   systemctl --user enable --now eping.service
   ```

4. *(Opsiyonel - Sunucu İçin)* Kullanıcı oturum kapatmış olsa bile sunucu açılışında arka planda çalışmaya devam etmesi için "linger" modunu açın:
   ```bash
   loginctl enable-linger $USER
   ```

Logları incelemek için:
```bash
journalctl --user -u eping.service -f
```

---

### 🍏 macOS (launchd Agent)

macOS açılışında otomatik çalışması için:

1. `~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist` dosyasını oluşturun:
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
   <plist version="1.0">
   <dict>
       <key>Label</key>
       <string>tr.mehmetemredogan.eping</string>
       <key>ProgramArguments</key>
       <array>
           <string>/usr/local/bin/eping</string>
           <string>daemon</string>
       </array>
       <key>RunAtLoad</key>
       <true/>
       <key>KeepAlive</key>
       <true/>
       <key>StandardOutPath</key>
       <string>/tmp/eping-daemon.log</string>
       <key>StandardErrorPath</key>
       <string>/tmp/eping-daemon.err</string>
   </dict>
   </plist>
   ```

2. Servisi yükleyin ve başlatın:
   ```bash
   launchctl load ~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist
   ```

Durdurmak için:
```bash
launchctl unload ~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist
```

## Test

```bash
composer run test
# veya
php artisan test

# Go birim testleri için:
cd ui && go test -v ./...
```

Testler `phpunit.xml` üzerinden `sqlite (:memory:)` kullanır, gerçek veritabanınızı etkilemez.

## Terminal İstemcisi Yapılandırması (ui/)

Yapılandırma: `%AppData%/eping/config.yaml` (Windows) veya `~/.config/eping/config.yaml`
(Linux/macOS), ya da `EPING_API_URL` ortam değişkeni.

Detaylar, klavye kısayolları ve ölçüm mantığı için: [`ui/README.md`](ui/README.md)
([English](ui/README.en.md)).

## Dil desteği

Web arayüzü (giriş/kayıt, üye paneli/geçmiş) ve admin paneli
**Türkçe (tr)** ve **İngilizce (en)** olarak tam çevrilidir. Dil seçimi:

- Üst menüdeki `TR` / `EN` düğmesiyle anlık değiştirilebilir (oturum bazlı, `session('locale')`).
- Varsayılan dil `.env` içindeki `APP_LOCALE` değeriyle belirlenir (bkz. `.env.example`).
- Çeviri dosyaları `lang/tr/` ve `lang/en/` altında `ping.php` (genel arayüz) ve
  `admin.php` (yönetim paneli) olarak ayrılmıştır.

Yeni bir dil eklemek için:

1. `lang/<locale>/ping.php` ve `lang/<locale>/admin.php` dosyalarını oluşturup çevirin.
2. `app/Http/Middleware/SetLocale.php` ve `app/Http/Controllers/LocaleController.php`
   içindeki `in_array`/`validate` listelerine yeni dil kodunu ekleyin.
3. `resources/views/layouts/ping.blade.php` ve `layouts/admin.blade.php` içindeki
   dil seçici `<select>`/butonlara yeni seçeneği ekleyin.

## CI/CD ve derleme

GitHub Actions üzerinde Go terminal istemcisi için iki iş akışı bulunur:

- **`ui-ci.yml`** — `ui/**` altında her push/PR'da Windows, Linux ve macOS
  runner'larında `go vet`, `go test` ve `go build` çalıştırır.
- **`ui-release.yml`** — `v*` formatında bir git tag'i push edildiğinde **veya**
  Actions sekmesinden manuel çalıştırıldığında (`workflow_dispatch`) Windows
  (amd64/arm64), Linux (amd64/arm64) ve macOS (amd64/arm64) için binary'leri
  cross-compile eder, arşivler ve otomatik bir GitHub Release oluşturup ekler.
  Sürüm numarası `ui/VERSION` dosyasından (manuel çalıştırmada girdi
  boş bırakılırsa) veya tag adından okunur — bkz. [`docs/BUILD.md`](docs/BUILD.md#versioning).

Yeni bir sürüm yayınlamak için `ui/VERSION` dosyasını güncelleyip tag'i push edin:

```bash
git tag v0.1.4
git push origin v0.1.4
```

veya tag oluşturmadan **Actions → UI Release → Run workflow** ile manuel tetikleyin
(sürüm alanı boş bırakılırsa `ui/VERSION` içindeki güncel değer — şu an `0.1.4` —
kullanılır).

Yerel olarak tüm platformlar için derlemek isterseniz `ui/Makefile`,
`ui/build.sh` (Linux/macOS) veya `ui/build.ps1` (Windows) betiklerini kullanabilirsiniz;
ayrıntılar için [`docs/BUILD.md`](docs/BUILD.md).

## Klasör yapısı

```
app/                    Laravel uygulama kodu (Controller, Model, Service, Middleware)
config/                 Framework ve uygulama yapılandırması
database/               Migration'lar ve seeder'lar
lang/                   tr/en çeviri dosyaları
resources/              Blade view'ları, CSS, JS
routes/                 web.php, api.php, auth.php
tests/                  PHPUnit Feature/Unit testleri
ui/                     Go tabanlı terminal istemcisi (bağımsız modül)
docs/                   Mimari, API ve derleme dokümantasyonu
.github/workflows/      CI/CD tanımları
```

## Dokümantasyon

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — Sistem mimarisi ve veri akışı
- [`docs/API.md`](docs/API.md) — REST API referansı (`/api/v1/*`)
- [`docs/BUILD.md`](docs/BUILD.md) — Go istemcisi için çoklu platform derleme ve release süreci
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — Katkı sağlama rehberi

## Katkıda bulunma

Katkılarınızı bekliyoruz! Lütfen [`CONTRIBUTING.md`](CONTRIBUTING.md) dosyasına göz atın.

## Lisans

Bu proje [MIT lisansı](https://opensource.org/licenses/MIT) ile lisanslanmıştır.
