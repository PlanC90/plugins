# Sync Existing Terms Acceptance to API

**Date:** February 26, 2026  
**Status:** ✅ IMPLEMENTED  
**Version:** 1.0

---

## 🎯 Problem

Eğer daha önce Terms of Service'i onayladıysanız, o veri sadece WordPress veritabanında duruyor. API'ye (Firebase) gönderilmemiş olabilir.

---

## ✅ Solution

### 1. **Otomatik Senkronizasyon**

Plugin artık admin paneline her girildiğinde otomatik olarak kontrol ediyor:

```php
// Hook to sync existing acceptance on admin init (runs once)
add_action('admin_init', 'wc_omnixep_sync_existing_terms_to_api');
```

**Ne Yapar:**
- Admin paneline ilk girişte çalışır
- Terms onaylanmış mı kontrol eder
- API'ye daha önce gönderilmiş mi kontrol eder
- Gönderilmemişse otomatik gönderir
- Bir kez gönderildikten sonra tekrar göndermez

**Veritabanı Flag:**
```php
omnixep_terms_synced_to_api = true/false
```

---

### 2. **Manuel Senkronizasyon Sayfası**

Yeni bir admin sayfası eklendi: **OmniXEP Sync Terms**

**Erişim:**
```
wp-admin/admin.php?page=omnixep-sync-terms
```

**Özellikler:**
- ✅ Mevcut onay durumunu gösterir
- ✅ Onay detaylarını listeler (tarih, kullanıcı, IP)
- ✅ API'ye gönderilip gönderilmediğini gösterir
- ✅ Manuel sync butonu
- ✅ Firebase'de nasıl görüntüleneceğini açıklar
- ✅ SQL sorgu örneği verir

---

## 📊 Sync Sayfası Özellikleri

### Gösterilen Bilgiler:

```
📋 Current Acceptance Status
├── Terms Version: 2.3
├── Accepted Date: 2026-02-26 14:30:00
├── Accepted By: Admin User (admin@example.com)
├── User ID: 1
├── IP Address: 192.168.1.100
└── Synced to API: ✅ Yes / ❌ No
```

### Manuel Sync Butonu:

- **İlk Sync:** "🔄 Sync to API Now"
- **Re-Sync:** "🔄 Re-Sync to API Now"

**Ne Zaman Kullanılır:**
- İlk kurulumda otomatik sync çalışmadıysa
- Invoice bilgileri güncellendiyse
- API'de veri kaybolmuşsa
- Test amaçlı

---

## 🔍 Firebase'de Nasıl Görüntülenir

### 1. **Firebase Console'a Git**

```
https://console.firebase.google.com
```

### 2. **Firestore Database'i Aç**

```
Project → Firestore Database → Data
```

### 3. **Collection'ı Bul**

```
omnixep_terms_acceptances
```

### 4. **Merchant ID ile Ara**

Sync sayfasında gösterilen Merchant ID'yi kullan:

```javascript
// Merchant ID örneği
merchant_id: "5d41402abc4b2a76b9719d911017c592"
```

**Firestore Query:**
```javascript
db.collection('omnixep_terms_acceptances')
  .where('merchant_id', '==', '5d41402abc4b2a76b9719d911017c592')
  .get()
```

### 5. **Site URL ile Ara**

```javascript
db.collection('omnixep_terms_acceptances')
  .where('site_url', '==', 'https://xepmarket.local')
  .get()
```

---

## 📊 SQL Database'de Görüntüleme

Eğer API SQL database kullanıyorsa:

### Merchant ID ile:

```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE merchant_id = '5d41402abc4b2a76b9719d911017c592'
ORDER BY accepted_at DESC;
```

### Site URL ile:

```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE site_url = 'https://xepmarket.local'
ORDER BY accepted_at DESC;
```

### Email ile:

```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE merchant_email = 'billing@xepmarket.com'
ORDER BY accepted_at DESC;
```

### Tüm Kayıtlar:

```sql
SELECT 
    merchant_legal_name,
    site_url,
    terms_version,
    accepted_at,
    synced_to_api
FROM omnixep_terms_acceptances
ORDER BY accepted_at DESC
LIMIT 50;
```

---

## 🔄 Sync Flow

### Otomatik Sync:

```
Admin Panel Açılır
        ↓
admin_init Hook Çalışır
        ↓
wc_omnixep_sync_existing_terms_to_api()
        ↓
Terms Onaylanmış mı? → Hayır → Exit
        ↓ Evet
API'ye Gönderilmiş mi? → Evet → Exit
        ↓ Hayır
Verileri Topla
        ↓
API'ye Gönder
        ↓
Flag Kaydet (synced = true)
        ↓
Log Yaz
```

### Manuel Sync:

```
Sync Sayfasına Git
        ↓
"Sync to API Now" Tıkla
        ↓
Flag Sil (synced = false)
        ↓
wc_omnixep_sync_existing_terms_to_api()
        ↓
Verileri Topla
        ↓
API'ye Gönder
        ↓
Flag Kaydet (synced = true)
        ↓
Başarı Mesajı Göster
```

---

## 🎯 Kullanım Senaryoları

### Senaryo 1: İlk Kurulum (Daha Önce Onaylanmış)

**Durum:**
- Terms daha önce onaylanmış (v2.0.0)
- API entegrasyonu yeni eklendi
- Veri API'de yok

**Çözüm:**
1. Admin paneline gir
2. Otomatik sync çalışır
3. Veri API'ye gönderilir
4. ✅ Tamamlandı

### Senaryo 2: Invoice Bilgileri Güncellendi

**Durum:**
- Terms onaylanmış ve sync edilmiş
- Invoice bilgileri değiştirildi
- API'deki veri eski

**Çözüm:**
1. `wp-admin/admin.php?page=omnixep-sync-terms` git
2. "Re-Sync to API Now" tıkla
3. Güncel veri API'ye gönderilir
4. ✅ Tamamlandı

### Senaryo 3: API'de Veri Kayboldu

**Durum:**
- Terms onaylanmış
- API'de veri yok (silinmiş/kaybolmuş)
- WordPress'te flag "synced = true"

**Çözüm:**
1. Sync sayfasına git
2. "Re-Sync" butonuna tıkla
3. Veri tekrar gönderilir
4. ✅ Tamamlandı

### Senaryo 4: Test Amaçlı

**Durum:**
- API endpoint'i test etmek istiyorsun
- Veriyi tekrar göndermek istiyorsun

**Çözüm:**
1. Sync sayfasına git
2. "Re-Sync" tıkla
3. API loglarını kontrol et
4. ✅ Test tamamlandı

---

## 🔍 Troubleshooting

### Problem: Otomatik Sync Çalışmıyor

**Kontrol:**
```php
// WordPress error log'a bak
tail -f wp-content/debug.log

// Şunu aramalısın:
=== EXISTING TERMS ACCEPTANCE SYNCED TO API ===
```

**Çözüm:**
- Manuel sync kullan
- API endpoint'in erişilebilir olduğunu kontrol et
- WordPress error log'u kontrol et

### Problem: "Already Synced" Diyor Ama API'de Yok

**Neden:**
- Flag set edilmiş ama API isteği başarısız olmuş
- Non-blocking request kullanıldığı için hata yakalanmamış

**Çözüm:**
```php
// Flag'i sil
delete_option('omnixep_terms_synced_to_api');

// Sync sayfasından tekrar gönder
```

### Problem: Sync Sayfası Açılmıyor

**Kontrol:**
- `manage_woocommerce` capability'n var mı?
- Admin kullanıcısı mısın?

**Çözüm:**
```
Direct link kullan:
wp-admin/admin.php?page=omnixep-sync-terms
```

---

## 📝 Admin Notice Sistemi

### Terms Onaylanmamış:

```
⚠️ OmniXEP Payment Gateway - Terms of Service Required
[Kırmızı banner]
→ "Read & Accept Terms of Service" butonu
```

### Terms Onaylanmış, Sync Edilmemiş:

```
ℹ️ Terms Acceptance Not Synced
[Mavi banner - sadece OmniXEP settings sayfasında]
→ "Click here to sync now →" linki
```

### Terms Onaylanmış ve Sync Edilmiş:

```
[Hiçbir notice gösterilmez]
✅ Her şey tamam
```

---

## 🔧 Developer Notes

### Flag Kontrolü:

```php
$synced = get_option('omnixep_terms_synced_to_api', false);

if ($synced) {
    // Already synced
} else {
    // Not synced yet
}
```

### Manuel Flag Reset:

```php
// Force re-sync
delete_option('omnixep_terms_synced_to_api');
```

### Sync Function Çağırma:

```php
// Programmatically sync
wc_omnixep_sync_existing_terms_to_api();
```

---

## 📊 Monitoring

### WordPress Error Log:

```bash
# Sync başarılı
=== TERMS ACCEPTANCE API SYNC START ===
Merchant: XEPMARKET Ltd
Site: https://xepmarket.local
Version: 2.3
=== TERMS ACCEPTANCE API SYNC SENT ===
=== EXISTING TERMS ACCEPTANCE SYNCED TO API ===

# Sync başarısız
TERMS ACCEPTANCE API ERROR: Connection timeout
```

### Database Check:

```sql
-- Check sync status
SELECT 
    option_name,
    option_value
FROM wp_options
WHERE option_name LIKE 'omnixep_terms%';
```

---

## ✅ Checklist

### İlk Kurulum:
- [ ] Terms onaylandı mı?
- [ ] Admin paneline girildi mi?
- [ ] Otomatik sync çalıştı mı?
- [ ] Error log'da başarı mesajı var mı?
- [ ] API'de veri görünüyor mu?

### Manuel Sync:
- [ ] Sync sayfasına erişildi mi?
- [ ] Mevcut durum doğru gösteriliyor mu?
- [ ] Sync butonu çalışıyor mu?
- [ ] Başarı mesajı göründü mü?
- [ ] API'de veri güncellendi mi?

### Verification:
- [ ] Firebase/SQL'de veri var mı?
- [ ] Merchant ID doğru mu?
- [ ] Site URL doğru mu?
- [ ] Tüm alanlar dolu mu?
- [ ] Timestamp doğru mu?

---

## 🎉 Summary

**Özellikler:**
- ✅ Otomatik sync (admin_init)
- ✅ Manuel sync sayfası
- ✅ Durum gösterimi
- ✅ Re-sync özelliği
- ✅ Firebase görüntüleme kılavuzu
- ✅ SQL sorgu örnekleri
- ✅ Admin notice sistemi
- ✅ Error logging

**Avantajlar:**
- Eski onaylar kaybolmaz
- İstediğin zaman sync edebilirsin
- Firebase'de kolayca bulabilirsin
- Re-sync ile güncelleyebilirsin

**Kullanım:**
1. Admin paneline gir → Otomatik sync
2. Veya: Sync sayfasına git → Manuel sync
3. Firebase'de kontrol et → Veri orada!

---

**Last Updated:** February 26, 2026  
**Version:** 1.0  
**Author:** XEPMARKET & Ceyhun Yılmaz

