# OmniXEP Logging System - Quick Reference

**Status:** ✅ Fully Implemented  
**Date:** February 26, 2026

---

## Evet, Plugin Her Şeyi Logluyor! 📝

Plugin şu işlemleri detaylı olarak loglar:

### ✅ Loglanan İşlemler:

1. **Sözleşme Onayı**
   - Onay tarihi ve saati
   - Kullanıcı bilgileri (ID, email, isim)
   - IP adresi
   - User agent
   - Site bilgileri
   - Terms versiyonu

2. **API Senkronizasyonu**
   - API endpoint
   - Gönderilen data boyutu
   - Merchant bilgileri
   - Başarı/hata durumu
   - Response detayları

3. **Plugin Deactivation**
   - Deactivation tarihi
   - Önceki terms durumu
   - Deactivate eden kullanıcı
   - Silinen veriler

4. **Güvenlik Olayları**
   - Invalid TXID denemeleri
   - Rate limit aşımları
   - Replay attack denemeleri
   - Wallet balance kontrolleri

5. **Transaction İşlemleri**
   - TXID kayıtları
   - Order status değişiklikleri
   - Payment verification
   - Cron job çalışmaları

---

## Log Dosyası Konumu

```
/wp-content/debug.log
```

---

## Nasıl Aktif Edilir?

`wp-config.php` dosyasına ekleyin:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## Nasıl Görüntülenir?

### SSH ile:
```bash
# Son 100 satır
tail -n 100 wp-content/debug.log

# Canlı takip
tail -f wp-content/debug.log

# Sadece OmniXEP logları
grep "OMNIXEP" wp-content/debug.log

# Sadece terms acceptance
grep "TERMS ACCEPTANCE" wp-content/debug.log

# Sadece hatalar
grep "ERROR" wp-content/debug.log
```

### FTP ile:
1. `wp-content/debug.log` dosyasını indirin
2. Text editor ile açın
3. "OMNIXEP" kelimesini arayın

---

## Log Örnekleri

### Başarılı Terms Acceptance:
```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
[26-Feb-2026 14:30:00 UTC] User Email: admin@example.com
[26-Feb-2026 14:30:00 UTC] IP Address: 192.168.1.100
[26-Feb-2026 14:30:00 UTC] ✅ Terms acceptance saved to WordPress options
[26-Feb-2026 14:30:01 UTC] ✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY
[26-Feb-2026 14:30:01 UTC] === OMNIXEP TERMS ACCEPTANCE COMPLETED ===
```

### API Sync Hatası:
```
[26-Feb-2026 14:30:05 UTC] ❌ TERMS ACCEPTANCE API ERROR: Connection timeout
[26-Feb-2026 14:30:05 UTC] Error Code: http_request_failed
```

### Plugin Deactivation:
```
[26-Feb-2026 16:00:00 UTC] === OMNIXEP PLUGIN DEACTIVATION START ===
[26-Feb-2026 16:00:00 UTC] Previous Terms Status: ACCEPTED
[26-Feb-2026 16:00:00 UTC] Deactivated By: John Doe (admin@example.com)
[26-Feb-2026 16:00:01 UTC] ✅ Terms acceptance data cleared successfully
[26-Feb-2026 16:00:01 UTC] === OMNIXEP PLUGIN DEACTIVATION COMPLETED ===
```

---

## Log Kategorileri

| Kategori | Log Prefix | Örnek |
|----------|-----------|-------|
| Terms Acceptance | `OMNIXEP TERMS ACCEPTANCE` | Sözleşme onayları |
| API Sync | `TERMS ACCEPTANCE API SYNC` | API senkronizasyonu |
| Deactivation | `OMNIXEP PLUGIN DEACTIVATION` | Plugin kapatma |
| Security | `OmniXEP Security` | Güvenlik olayları |
| Transactions | `OmniXEP Mobile Callback` | İşlem kayıtları |
| Cron | `OmniXEP Cron` | Otomatik kontroller |

---

## İstatistikler

### Kaç kez terms kabul edildi?
```bash
grep -c "TERMS ACCEPTANCE START" wp-content/debug.log
```

### Kaç API sync başarılı?
```bash
grep -c "API SYNC SENT SUCCESSFULLY" wp-content/debug.log
```

### Kaç hata oluştu?
```bash
grep -c "ERROR" wp-content/debug.log
```

### Kaç kez deactivate edildi?
```bash
grep -c "PLUGIN DEACTIVATION START" wp-content/debug.log
```

---

## Önemli Notlar

### ✅ Avantajlar:
- Tam audit trail
- Hata ayıklama kolaylığı
- Güvenlik takibi
- Yasal kayıt tutma
- GDPR uyumlu

### ⚠️ Dikkat Edilmesi Gerekenler:
- Log dosyası büyüyebilir (haftalık rotation önerilir)
- Kişisel veri içerir (GDPR)
- Public erişime kapalı olmalı
- 90 gün sonra silinmeli

---

## Detaylı Dokümantasyon

Tüm detaylar için bakınız:
- `LOGGING_SYSTEM.md` - Tam dokümantasyon
- `wp-content/debug.log` - Gerçek log dosyası

---

**Sonuç:** Evet, plugin her şeyi detaylı olarak logluyor! 🎉

---

**Version:** 1.0  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
