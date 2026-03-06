# Master Key Güvenlik Çözümü

## Sorun
Şu anda her mağaza kendi `internal_secret`'ini kullanıyor. Mağaza sahibi kendi veritabanına erişirse kendi wallet'ını çözebilir.

## Çözüm Seçenekleri

### Seçenek 1: API-Based Master Key (ÖNERİLEN) ⭐
```php
// Plugin her yüklendiğinde API'den master key alır
$master_key = wp_remote_get('https://api.omnixep.com/v1/get-master-key', [
    'headers' => [
        'X-Plugin-Version' => OMNIXEP_VERSION,
        'X-Site-Hash' => hash('sha256', get_site_url())
    ]
]);

// API, site hash'i kontrol eder ve key döner
// Sadece onaylı siteler key alabilir
```

**Avantajlar:**
- ✅ Master key hiçbir yerde saklanmaz
- ✅ İstediğin zaman key'i değiştirebilirsin
- ✅ Şüpheli siteleri engelleyebilirsin
- ✅ Kullanım istatistikleri toplayabilirsin

**Dezavantajlar:**
- ⚠️ API down olursa plugin çalışmaz
- ⚠️ Her sayfa yüklemesinde API call

### Seçenek 2: Obfuscated Master Key
```php
// Master key'i şifreli ve obfuscate edilmiş şekilde sakla
function get_master_key() {
    $obfuscated = base64_decode('Y2V5aHVuLXBsYW5jLW95a3UtLi4u');
    $salt = hash('sha256', __FILE__ . __LINE__);
    return hash_hmac('sha256', $obfuscated, $salt);
}
```

**Avantajlar:**
- ✅ Offline çalışır
- ✅ Hızlı

**Dezavantajlar:**
- ❌ Reverse engineering ile bulunabilir
- ❌ Key değiştirilemez

### Seçenek 3: Hybrid (API + Fallback)
```php
// Önce API'den al
$master_key = get_master_key_from_api();

// API down ise obfuscated key kullan
if (!$master_key) {
    $master_key = get_obfuscated_master_key();
}
```

**Avantajlar:**
- ✅ Güvenli (API)
- ✅ Reliable (fallback)

### Seçenek 4: İki Katmanlı Şifreleme
```php
// 1. Katman: Site-specific key (mevcut)
$site_key = hash_hmac('sha256', $site_hash, $internal_secret);

// 2. Katman: Master key (API'den)
$master_key = get_master_key_from_api();

// İkisini birleştir
$final_key = hash_hmac('sha256', $site_key, $master_key);
```

**Avantajlar:**
- ✅ Çift koruma
- ✅ Master key olmadan çözülemez
- ✅ Site key olmadan da çözülemez

## Önerim: Seçenek 4 (İki Katmanlı)

### Implementasyon:

1. **API Endpoint Oluştur**
```php
// https://api.omnixep.com/v1/master-key
function get_master_key($site_hash) {
    // Whitelist kontrolü
    if (!is_approved_site($site_hash)) {
        return false;
    }
    
    // Master key döner (her site için aynı)
    return 'ceyhun-planc-oyku-karen-esra-4517-8519-cinar';
}
```

2. **Plugin'de API Call**
```php
function get_encryption_key() {
    // Site-specific key
    $site_key = hash_hmac('sha256', 
        get_site_url() . ABSPATH, 
        get_option('omnixep_internal_secret')
    );
    
    // Master key (cache 24 saat)
    $master_key = get_transient('omnixep_master_key');
    if (!$master_key) {
        $response = wp_remote_get('https://api.omnixep.com/v1/master-key', [
            'headers' => [
                'X-Site-Hash' => hash('sha256', get_site_url())
            ]
        ]);
        
        if (!is_wp_error($response)) {
            $master_key = wp_remote_retrieve_body($response);
            set_transient('omnixep_master_key', $master_key, DAY_IN_SECONDS);
        }
    }
    
    // İkisini birleştir
    return hash_hmac('sha256', $site_key, $master_key);
}
```

### Güvenlik Analizi:

**Saldırgan Senaryosu 1: Plugin İndirir**
- ❌ Master key yok (API'de)
- ❌ API endpoint korumalı
- ❌ Çözemez

**Saldırgan Senaryosu 2: Kendi Mağazasını Kurar**
- ✅ Site key'i bulabilir (kendi DB'si)
- ❌ Master key yok (API'de)
- ❌ API whitelist kontrolü
- ❌ Çözemez

**Saldırgan Senaryosu 3: API'yi Hack'ler**
- ✅ Master key'i bulabilir
- ❌ Ama site key'leri yok
- ❌ Her mağaza farklı site key
- ❌ Çözemez

**Saldırgan Senaryosu 4: Hem API Hem DB Hack'ler**
- ✅ Her ikisini de bulabilir
- ✅ Çözebilir
- ⚠️ Ama bu durumda zaten her şey hack'lenmiş

## Sonuç

Mevcut sistem **kendi mağazası için güvenli değil**.
Önerilen sistem **1 milyon mağaza için güvenli**.

Hangi seçeneği implement edeyim?
