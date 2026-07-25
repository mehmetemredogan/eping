<?php

return [
    'nav_terms' => 'Üyelik ve kullanım',
    'nav_privacy' => 'Gizlilik (KVKK / GDPR)',
    'nav_cookies' => 'Çerezler',

    'terms_title' => 'Üyelik Sözleşmesi ve Kullanım Koşulları',
    'terms_updated' => 'Son güncelleme: 25 Temmuz 2026',
    'terms_intro' => 'Bu metin, ePing web uygulaması ve terminal istemcisinin kullanım koşullarını ile üyelik kurallarını açıklar. Hesap oluşturarak veya hizmeti kullanarak bu koşulları kabul etmiş sayılırsınız.',

    'terms_s1_title' => '1. Hizmetin kapsamı',
    'terms_s1_body' => 'ePing; bulut sağlayıcıları, oyun sunucuları ve CDN hedeflerine yönelik ağ gecikmesi (latency) ölçümü ile traceroute analizi sunar. Ölçümler tarayıcıda değil, kullanıcının kendi cihazında çalışan terminal istemcisi üzerinden yapılır. Web arayüzü üyelik, sonuç geçmişi, anonim istatistikler ve yönetim paneli içindir.',

    'terms_s2_title' => '2. Hesap ve üyelik',
    'terms_s2_body' => 'Üyelik için yalnızca kullanıcı adı ve parola istenir; e-posta veya gerçek ad zorunlu değildir. Hesap güvenliği (parola seçimi ve saklanması) kullanıcıya aittir. Parolanızı unutmanız halinde kurtarma için e-posta kanalı bulunmayabilir. Hesabınızı kötüye kullanmanız veya hizmeti bozmanız halinde hesabınız askıya alınabilir veya silinebilir.',

    'terms_s3_title' => '3. Hangi bilgileri tutuyoruz?',
    'terms_s3_intro' => 'Hizmetin çalışması için aşağıdaki veriler saklanabilir:',
    'terms_s3_items' => [
        'Hesap: kullanıcı adı, parola özeti (hash), admin yetkisi bayrağı, oluşturulma zamanı.',
        'Oturum ve API: web oturumu, API token’ları (istemci girişi için).',
        'Ölçüm sonuçları: hedef, durum, gecikme/jitter/kayıp metrikleri, örnek değerler, traceroute hop listesi ve ham çıktı özeti, istemci sürümü, bağlantı tipi (Wi‑Fi / Ethernet / bilinmiyor).',
        'Ağ bağlamı: istemci IP adresi; IP’den türetilen yaklaşık konum / ASN / ISS bilgisi (üçüncü taraf IP coğrafi servisi ile); çözümleme (DNS) kayıtları.',
        'Teknik günlükler: güvenlik ve hata ayıklama amaçlı sunucu logları (sınırlı süre).',
    ],

    'terms_s4_title' => '4. Kayıtları nasıl alıyoruz?',
    'terms_s4_body' => 'Ölçüm kayıtları, giriş yapmış kullanıcının terminal istemcisinin API’ye gönderdiği sonuçlarla oluşur. Web sitesi üzerinden ping testi yapılmaz. İstemci; seçilen hedeflere HTTP gecikmesi ve isteğe bağlı traceroute çalıştırır, ardından sonucu hesabınıza bağlayarak sunucuya iletir. Genel istatistik sayfasında gösterilen veriler bu kayıtlardan anonimleştirilerek (kullanıcı adı ve ham IP gösterilmeden, yeterli örnek sayısı ile) gruplanır.',

    'terms_s5_title' => '5. İstemci kullanımı — kullanıcı sorumluluğu',
    'terms_s5_body' => 'Terminal istemcisini indirmek, kurmak ve çalıştırmak tamamen kullanıcının sorumluluğundadır. İstemcinin ağınıza, güvenlik politikanıza, işvereninizin veya ISS’nizin kurallarına uygun kullanılması gerekir. Yetkisiz hedeflere yoğun tarama, kötüye kullanım veya yasa dışı faaliyetler için hizmeti kullanmak yasaktır. ePing; istemcinin yanlış yapılandırılması, yanlış yorumlanan sonuçlar, ağ kesintileri veya üçüncü taraf sistemlerden kaynaklanan zararlardan sorumlu tutulamaz. Yazılım “olduğu gibi” sunulur; kesintisiz veya hatasız çalışma garantisi verilmez.',

    'terms_s6_title' => '6. Kabul edilebilir kullanım',
    'terms_s6_body' => 'Hizmeti yalnızca meşru ağ ölçümü ve kişisel/kurumsal performans değerlendirmesi için kullanabilirsiniz. API’ye aşırı yük bindirmek, diğer kullanıcıların erişimini engellemek, güvenlik açıklarını istismar etmek veya yanıltıcı veri enjekte etmek yasaktır.',

    'terms_s7_title' => '7. Fikri mülkiyet ve lisans',
    'terms_s7_body' => 'ePing yazılımı projenin açık kaynak lisansına tabidir. Marka, arayüz metinleri ve barındırılan hizmet içeriği aksi belirtilmedikçe ilgili hak sahiplerine aittir.',

    'terms_s8_title' => '8. Değişiklikler',
    'terms_s8_body' => 'Bu koşullar zaman zaman güncellenebilir. Önemli değişikliklerde bu sayfadaki “son güncelleme” tarihi yenilenir. Güncellemeden sonra hizmeti kullanmaya devam etmeniz yeni metni kabul ettiğiniz anlamına gelir.',

    'terms_s9_title' => '9. İletişim',
    'terms_s9_body' => 'Sorularınız için proje deposu veya site üzerinde belirtilen iletişim kanallarını kullanabilirsiniz: github.com/mehmetemredogan/eping',

    'privacy_title' => 'Gizlilik Bildirimi (KVKK ve GDPR)',
    'privacy_updated' => 'Son güncelleme: 25 Temmuz 2026',
    'privacy_intro' => 'Bu bildirim, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ve Avrupa Birliği Genel Veri Koruma Tüzüğü (GDPR) kapsamında kişisel verilerin işlenmesine ilişkin bilgilendirmeyi içerir. ePing, mümkün olduğunca az kişisel veri toplayacak şekilde tasarlanmıştır (e-posta ve gerçek ad zorunlu değildir).',

    'privacy_s1_title' => '1. Veri sorumlusu',
    'privacy_s1_body' => 'Hizmeti işleten taraf, bu siteyi ve ilişkili API’yi barındıran işletmecidir. Talepleriniz için proje iletişim kanallarını kullanabilirsiniz.',

    'privacy_s2_title' => '2. İşlenen veriler',
    'privacy_s2_body' => 'Kullanıcı adı ve parola özeti; ölçüm sonuçları ve traceroute ayrıntıları; istemci IP’si ve bundan türetilen ISS/ASN/ülke gibi kaba konum bilgileri; oturum ve API token’ları; dil tercihi gibi teknik çerezler. E-posta, telefon veya kimlik numarası toplanmaz.',

    'privacy_s3_title' => '3. Amaçlar ve hukuki sebepler',
    'privacy_s3_items' => [
        'Hizmetin sunulması ve hesabınızın yönetilmesi (sözleşmenin ifası / hizmetin sağlanması).',
        'Ölçüm geçmişinin gösterilmesi ve API kimlik doğrulaması.',
        'Anonim / toplulaştırılmış ISP istatistiklerinin üretilmesi (meşru menfaat; k-anonimlik eşikleri uygulanır).',
        'Güvenlik, kötüye kullanımın önlenmesi ve teknik loglama (meşru menfaat / yasal yükümlülük).',
        'Dil ve oturum için zorunlu çerezler (hizmetin sunulması).',
    ],

    'privacy_s4_title' => '4. Aktarım ve üçüncü taraflar',
    'privacy_s4_body' => 'IP coğrafi bilgisi için üçüncü taraf bir IP sorgu servisi kullanılabilir. Barındırma ve altyapı sağlayıcıları teknik olarak verilere erişebilir. Veriler, hizmetin sunulduğu ülkede veya sağlayıcının altyapısında saklanabilir. Açık rıza gerektiren pazarlama aktarımı yapılmaz.',

    'privacy_s5_title' => '5. Saklama süreleri',
    'privacy_s5_body' => 'Hesap verileri hesap silinene kadar; ölçüm sonuçları siz geçmişinizi temizleyene veya hesap kapatılana kadar; teknik loglar makul bir süre; oturum/token’lar geçerlilikleri bitene veya iptal edilene kadar saklanır.',

    'privacy_s6_title' => '6. Haklarınız (KVKK / GDPR)',
    'privacy_s6_body' => 'KVKK m.11 ve GDPR kapsamında; verilerinize erişme, düzeltme, silme/unutulma, işlemeyi kısıtlama, itiraz etme ve (uygulanabilir olduğunda) veri taşınabilirliği talep etme haklarınız vardır. Üye panelinden test geçmişinizi silebilir; kullanıcı adınızı güncelleyebilirsiniz. Ek talepler için iletişime geçin. Şikâyet hakkınız için Kişisel Verileri Koruma Kurumu’na (Türkiye) veya yetkili denetim makamına başvurabilirsiniz.',

    'privacy_s7_title' => '7. Güvenlik',
    'privacy_s7_body' => 'Parolalar hash’lenerek saklanır; API erişimi token ile korunur. Yine de hiçbir sistem %100 güvenli değildir; güçlü parola kullanmanız ve token’larınızı paylaşmamanız önemlidir.',

    'cookies_title' => 'Çerez Politikası',
    'cookies_updated' => 'Son güncelleme: 25 Temmuz 2026',
    'cookies_intro' => 'ePing, siteyi çalıştırmak için gerekli teknik çerezleri kullanır. Reklam veya üçüncü taraf izleme çerezleri kullanılmaz.',

    'cookies_s1_title' => 'Kullandığımız çerezler',
    'cookies_s1_items' => [
        'Oturum çerezi — giriş yaptığınızda oturumunuzu sürdürmek için (zorunlu).',
        'XSRF-TOKEN / CSRF — form güvenliği için (zorunlu).',
        'Dil tercihi — arayüz dilini hatırlamak için (işlevsel / tercih).',
        'Çerez bildirimi onayı — yalnızca tarayıcınızda yerel olarak saklanır (localStorage); sunucuya gönderilmez.',
    ],

    'cookies_s2_title' => 'Yönetim',
    'cookies_s2_body' => 'Tarayıcı ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezleri engellerseniz giriş ve form gönderimi çalışmayabilir. Bildirim çubuğundaki “Anladım” seçeneği tercihinizi cihazınızda saklar.',

    'banner_text' => 'Bu site oturum, güvenlik ve dil tercihi için zorunlu çerezler kullanır. Ayrıntılar için çerez politikasına bakın.',
    'banner_accept' => 'Anladım',
    'banner_learn' => 'Çerez politikası',

    'register_accept_label' => ':terms ve :privacy metinlerini okudum, kabul ediyorum. İstemci kullanımının kendi sorumluluğumda olduğunu biliyorum.',
    'register_accept_terms' => 'Üyelik Sözleşmesi ve Kullanım Koşulları',
    'register_accept_privacy' => 'Gizlilik Bildirimi',
    'register_accept_required' => 'Devam etmek için üyelik sözleşmesi ve gizlilik bildirimini kabul etmelisiniz.',
];
