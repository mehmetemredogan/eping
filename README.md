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
- [Terminal istemcisi (ui/)](#terminal-istemcisi-ui)
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
- **Terminal istemcisi** — HTTP TTFB (DNS/TCP/TLS kırılımı, p50/p95) ve
  OS `tracert`/`traceroute` tabanlı hop analizi; tek ölçüm aracı budur.
- **Üye paneli** — Terminal istemcisiyle yaptığınız testlerin geçmişini
  tarihe göre listeler.
- **Geçmişle karşılaştırma** — Giriş yapan kullanıcılar için geçmiş ölçümlere
  göre iyileşme/kötüleşme trendi (API üzerinden, `/api/v1/results/trend`).
- **Admin paneli** — Hedef, sağlayıcı ve test logu yönetimi; dashboard istatistikleri.
- **Basit kimlik doğrulama** — Yalnızca kullanıcı adı + parola (e-posta/isim istenmez),
  API tarafında Sanctum token ile.
- **Çok dilli arayüz** — Türkçe ve İngilizce arasında anlık geçiş (bkz. [Dil desteği](#dil-desteği)).

## Mimari

Backend, hedef listesini ve test sonuçlarını PostgreSQL (veya SQLite, test ortamı)
üzerinde saklar. Ping ölçümü yalnızca Go terminal istemcisi tarafından yapılır ve
sonuçlar API üzerinden gönderilir; web uygulaması bu sonuçları üye paneli ve admin
panelinde görüntüler.

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

Yerel sunucuyu başlatın:

```bash
php artisan serve
```

Uygulama varsayılan olarak `http://localhost:8000` adresinde çalışır.

## Geliştirme

Sunucu, kuyruk dinleyicisi, log takipçisi ve Vite'ı tek komutla birlikte çalıştırır:

```bash
composer run dev
```

## Test

```bash
composer run test
# veya
php artisan test
```

Testler `phpunit.xml` üzerinden `sqlite (:memory:)` kullanır, gerçek veritabanınızı etkilemez.

## Terminal istemcisi (ui/)

`ui/` klasöründeki Go uygulaması, API üzerinden hedef listesini çeker ve
ping/traceroute ölçümlerini bir terminal arayüzünde gösterir.

```bash
cd ui
go mod tidy
go run .
```

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
