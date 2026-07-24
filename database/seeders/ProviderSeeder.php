<?php

namespace Database\Seeders;

use App\Models\PingTarget;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    /**
     * Creates a row for every provider used by targets and fills in
     * default markdown descriptions. Existing descriptions are kept.
     */
    public function run(): void
    {
        $descriptions = [
            'Amazon Web Services' => '**AWS**, dünyanın en yaygın bulut platformudur. EC2 bölgelerine olan gecikmeniz, bu bölgelerde barındırılan oyun ve uygulama sunucularındaki deneyiminizi doğrudan etkiler. [aws.amazon.com](https://aws.amazon.com)',
            'Microsoft Azure' => '**Azure**, Microsoft\'un bulut platformudur. Xbox Live ve birçok kurumsal servis Azure bölgeleri üzerinde çalışır. [azure.microsoft.com](https://azure.microsoft.com)',
            'Google Cloud' => '**Google Cloud**, Google\'ın altyapısını kullanan bulut platformudur. Düşük gecikmeli *premium* ağ omurgasıyla bilinir. [cloud.google.com](https://cloud.google.com)',
            'Cloudflare' => '**Cloudflare**, 300\'den fazla noktada CDN, DNS (`1.1.1.1`) ve DDoS koruması sunar. Gecikme genellikle size en yakın PoP\'a olan mesafeyi gösterir. [cloudflare.com](https://www.cloudflare.com)',
            'DigitalOcean' => '**DigitalOcean**, geliştirici odaklı bulut sağlayıcısıdır. Oyun toplulukları ve küçük sunucular arasında yaygındır. [digitalocean.com](https://www.digitalocean.com)',
            'Oracle Cloud' => '**Oracle Cloud (OCI)**, ücretsiz katmanıyla popüler olan kurumsal bulut platformudur. [oracle.com/cloud](https://www.oracle.com/cloud/)',
            'Alibaba Cloud' => '**Alibaba Cloud**, Asya-Pasifik bölgesinde güçlü varlığı olan bulut sağlayıcısıdır. [alibabacloud.com](https://www.alibabacloud.com)',
            'Hetzner' => '**Hetzner**, Almanya merkezli uygun fiyatlı sunucu sağlayıcısıdır. Avrupa oyun sunucularının önemli kısmı burada barınır. [hetzner.com](https://www.hetzner.com)',
            'Vultr' => '**Vultr**, 30\'dan fazla lokasyonda VPS sunan sağlayıcıdır. [vultr.com](https://www.vultr.com)',
            'Akamai Linode' => '**Linode (Akamai)**, Akamai bünyesindeki geliştirici dostu bulut platformudur. [linode.com](https://www.linode.com)',
            'OVHcloud' => '**OVHcloud**, Avrupa\'nın en büyük hosting sağlayıcısıdır. Oyun sunucuları için *Game* serisi DDoS korumasıyla bilinir. [ovhcloud.com](https://www.ovhcloud.com)',
            'Scaleway' => '**Scaleway**, Fransa merkezli bulut sağlayıcısıdır. [scaleway.com](https://www.scaleway.com)',
            'Riot Games' => '**Riot Games** — *League of Legends* ve *VALORANT* sunucuları. Düşük ping, nişancı ve MOBA oyunlarında kritik önem taşır.',
            'Valve' => '**Valve** — Steam ve *CS2 / Dota 2* resmi sunucuları (SDR ağı). Bölge seçiminizi bu gecikmelere göre yapabilirsiniz.',
            'Blizzard' => '**Blizzard** — *Overwatch, World of Warcraft, Diablo* sunucuları. [blizzard.com](https://www.blizzard.com)',
            'Epic Games' => '**Epic Games** — *Fortnite* ve Epic Online Services altyapısı. Çoğunlukla AWS bölgeleri üzerinde çalışır.',
            'Riot' => '**Riot Games** — *League of Legends* ve *VALORANT* sunucuları.',
            'Ubisoft' => '**Ubisoft** — *Rainbow Six Siege* ve diğer Ubisoft Connect oyun sunucuları.',
            'Electronic Arts' => '**EA** — *FC, Battlefield* ve EA app servisleri.',
            'EA / Respawn' => '**Respawn (EA)** — *Apex Legends* sunucuları. Oyun içi veri merkezi listesiyle karşılaştırabilirsiniz.',
            'Activision' => '**Activision** — *Call of Duty / Warzone* servisleri.',
            'KRAFTON' => '**KRAFTON** — *PUBG: Battlegrounds* sunucuları.',
            'Rockstar' => '**Rockstar Games** — *GTA Online* ve *Red Dead Online* servisleri.',
            'Roblox' => '**Roblox** — platform ve oyun sunucuları.',
            'Discord' => '**Discord** — sesli sohbet sunucuları. Ses gecikmesi, seçilen bölgeye olan RTT ile ilişkilidir.',
            'Google' => '**Google** — genel Google servisleri ve `8.8.8.8` genel DNS.',
            'Cisco OpenDNS' => '**OpenDNS (Cisco)** — `208.67.222.222` genel DNS servisi.',
            'Quad9' => '**Quad9** — `9.9.9.9` gizlilik odaklı genel DNS.',
            'AdGuard' => '**AdGuard DNS** — reklam engelleyen genel DNS servisi.',
            'Fastly' => '**Fastly** — büyük sitelerin kullandığı edge/CDN ağı.',
            'Akamai' => '**Akamai** — dünyanın en eski ve en büyük CDN ağlarından biri.',
            'Gcore' => '**Gcore** — oyun odaklı CDN ve edge sağlayıcısı.',
            'Bunny.net' => '**Bunny.net** — uygun fiyatlı, hızlı CDN servisi.',
            'KeyCDN' => '**KeyCDN** — İsviçre merkezli CDN sağlayıcısı.',
            'CDN77' => '**CDN77** — Avrupa merkezli CDN ağı.',
            'CacheFly' => '**CacheFly** — TCP-anycast tabanlı CDN.',
            'StackPath' => '**StackPath** — edge computing ve CDN platformu.',
            'Microsoft' => '**Microsoft** — Xbox Live ve genel Microsoft servisleri.',
            'Sony' => '**Sony** — PlayStation Network servisleri.',
            'Nintendo' => '**Nintendo** — Nintendo Switch Online servisleri.',
            'Amazon / Twitch' => '**Twitch (Amazon)** — canlı yayın giriş (ingest) sunucuları. Yayıncılar için düşük RTT önemlidir.',
            'Amazon Games' => '**Amazon Games** — *New World, Lost Ark* yayıncı servisleri.',
            'FACEIT' => '**FACEIT** — rekabetçi CS2 ve espor platformu sunucuları.',
            'ESL' => '**ESL** — espor turnuva platformu.',
            'Hypixel' => '**Hypixel** — dünyanın en büyük Minecraft sunucusu.',
            'CubeCraft' => '**CubeCraft** — popüler Minecraft mini oyun ağı.',
            'Mineplex' => '**Mineplex** — Minecraft mini oyun sunucusu.',
            'Cfx.re' => '**Cfx.re** — *FiveM / RedM* (GTA V & RDR2 roleplay) altyapısı.',
            'Facepunch' => '**Facepunch** — *Rust* resmi sunucuları.',
            'GRINDING GEAR' => '**Grinding Gear Games** — *Path of Exile* sunucuları.',
            'Jagex' => '**Jagex** — *RuneScape / OSRS* sunucuları.',
            'Square Enix' => '**Square Enix** — *Final Fantasy XIV* ve diğer servisler.',
        ];

        $providers = PingTarget::query()
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->pluck('provider');

        foreach ($providers as $name) {
            $provider = Provider::query()->firstOrCreate(['name' => $name]);

            if (blank($provider->description) && isset($descriptions[$name])) {
                $provider->update(['description' => $descriptions[$name]]);
            }
        }
    }
}
