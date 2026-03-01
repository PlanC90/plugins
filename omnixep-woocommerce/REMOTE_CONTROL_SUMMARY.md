# Remote Plugin Control - Quick Reference

**Status:** ✅ Implemented  
**Date:** February 26, 2026

---

## Sistem Tamamlandı! 🎉

Uzaktan plugin kontrol sistemi başarıyla entegre edildi.

---

## Nasıl Çalışır?

```
Admin → API'ye disable isteği
   ↓
Firebase'de kayıt oluşur
   ↓
Plugin → Her işlemde kontrol yapar
   ↓
Devre dışı ise → İşlemi durdurur
```

---

## Admin Komutları

### Plugin'i Devre Dışı Bırak
```bash
npm run plugin:disable 5d41402abc... "Terms violation"
```

### Plugin'i Aktif Et
```bash
npm run plugin:enable 5d41402abc... "Issue resolved"
```

### Durumu Kontrol Et
```bash
npm run plugin:check 5d41402abc...
```

### Tüm Devre Dışı Plugin'leri Listele
```bash
npm run plugin:list-disabled
```

---

## Plugin Tarafında Ne Olur?

### 1. Gateway Kullanılamaz
```php
public function is_available()
{
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        return false; // Gateway not available
    }
}
```

### 2. Ödeme İşlemi Bloklanır
```php
public function process_payment($order_id)
{
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        wc_add_notice('Gateway unavailable: ' . $remote_status['reason'], 'error');
        return ['result' => 'failure'];
    }
}
```

### 3. Admin Ayarları Kilitlenir
```php
public function admin_options()
{
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        // Show error notice
        // Block settings
        return;
    }
}
```

### 4. Admin Notice Gösterilir
```
┌─────────────────────────────────────────────────────────────┐
│ 🚫 OmniXEP Plugin Remotely Disabled                         │
│                                                              │
│ Reason: Terms violation                                     │
│ Disabled At: 2026-02-26 14:30:00                           │
│                                                              │
│ Contact: support@xepmarket.com                              │
│ Merchant ID: 5d41402abc...                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## API Endpoints

### Check Status
```json
POST /api
{
  "action": "check_plugin_status",
  "merchant_id": "5d41402abc..."
}

Response:
{
  "plugin_enabled": false,
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z"
}
```

### Disable Plugin
```json
POST /api
{
  "action": "disable_plugin",
  "admin_key": "secret-key",
  "merchant_id": "5d41402abc...",
  "reason": "Terms violation"
}
```

### Enable Plugin
```json
POST /api
{
  "action": "enable_plugin",
  "admin_key": "secret-key",
  "merchant_id": "5d41402abc...",
  "reason": "Issue resolved"
}
```

---

## Kullanım Senaryoları

### 1. Terms İhlali
```bash
npm run plugin:disable 5d41402abc... "Terms violation: Commission bypass"
```

### 2. Komisyon Ödememe
```bash
npm run plugin:disable 5d41402abc... "Commission not paid for 90 days"
```

### 3. Dolandırıcılık
```bash
npm run plugin:disable 5d41402abc... "Fraudulent activity detected"
```

### 4. Lisans Süresi Doldu
```bash
npm run plugin:disable 5d41402abc... "License expired"
```

---

## Güvenlik Özellikleri

✅ **Admin Key Koruması** - Sadece admin key ile disable/enable
✅ **Audit Trail** - Tüm işlemler kaydedilir
✅ **Fail-Open** - API hatası durumunda plugin çalışmaya devam eder
✅ **Cache** - 5 dakika cache ile performans
✅ **Logging** - Tüm işlemler loglanır

---

## Cache Stratejisi

```php
// 5 dakika cache
set_transient('omnixep_remote_status_' . $merchant_id, $status, 300);

// API hatası durumunda 1 dakika cache
set_transient($cache_key, ['enabled' => true], 60);
```

**Sonuç:**
- Maksimum 5 dakika gecikme
- API yükü azalır
- Hızlı response

---

## Merchant Deneyimi

### Checkout Sayfası
```
OmniXEP Payment Gateway is not available.
Please contact the store administrator.
```

### Admin Dashboard
```
🚫 Plugin Remotely Disabled

Reason: Terms violation
Contact: support@xepmarket.com
Merchant ID: 5d41402abc...
```

### Settings Sayfası
```
🚫 Plugin Remotely Disabled

This plugin has been disabled and cannot be configured.
Contact support to resolve.
```

---

## Logging

### Text Log
```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP REMOTE CONTROL: PLUGIN DISABLED ===
[26-Feb-2026 14:30:00 UTC] Merchant ID: 5d41402abc...
[26-Feb-2026 14:30:00 UTC] Reason: Terms violation
```

### JSON Log
```json
{
  "event": "remote_disable_detected",
  "merchant_id": "5d41402abc...",
  "disable_reason": "Terms violation",
  "status": "plugin_disabled_remotely"
}
```

---

## Kontrol Noktaları

Plugin şu noktalarda kontrol yapar:

1. ✅ `is_available()` - Gateway kullanılabilirlik
2. ✅ `process_payment()` - Ödeme işleme
3. ✅ `admin_options()` - Admin ayarları
4. ✅ `admin_notices` - Admin bildirimleri

---

## Detaylı Dokümantasyon

Tüm detaylar için:
- `REMOTE_CONTROL_SYSTEM.md` - Tam dokümantasyon

---

**Sonuç:** Uzaktan kontrol sistemi aktif ve çalışıyor! 🎉

---

**Version:** 1.0  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
