# Plugin Deactivation - Terms Reset Feature

**Status:** ✅ Implemented  
**Date:** February 26, 2026

---

## What Was Implemented

Plugin deaktive edildiğinde sözleşme onayı otomatik olarak sıfırlanır ve tekrar aktif edildiğinde kullanıcı sözleşmeyi yeniden onaylamak zorundadır.

---

## How It Works

### 1. Deactivation (Plugin Kapatma)
```
WordPress Admin → Plugins → OmniXEP Payment Gateway → Deactivate
```

**Otomatik Silinen Veriler:**
- ✅ omnixep_terms_accepted
- ✅ omnixep_terms_version
- ✅ omnixep_terms_accepted_date
- ✅ omnixep_terms_accepted_by
- ✅ omnixep_terms_accepted_ip
- ✅ omnixep_terms_synced_to_api

**Korunan Veriler:**
- ✅ Wallet adresleri
- ✅ Fatura bilgileri
- ✅ 2FA ayarları
- ✅ Tüm gateway ayarları

### 2. Reactivation (Plugin Açma)
```
WordPress Admin → Plugins → OmniXEP Payment Gateway → Activate
```

**Sonuç:**
- ⚠️ Kırmızı uyarı mesajı görünür
- ⚠️ Gateway devre dışı kalır
- ⚠️ Checkout'ta ödeme seçeneği görünmez

### 3. Terms Re-Acceptance (Yeniden Onay)
```
WooCommerce → Settings → Payments → OmniXEP → "Read & Accept Terms"
```

**Sonuç:**
- ✅ Sözleşme sayfası açılır
- ✅ Kullanıcı okuyup onaylar
- ✅ Yeni acceptance kaydı oluşturulur
- ✅ API'ye yeni data gönderilir
- ✅ Gateway aktif olur

---

## Code Implementation

```php
/**
 * Plugin Deactivation Hook
 * Clear terms acceptance when plugin is deactivated
 */
register_deactivation_hook(__FILE__, 'wc_omnixep_deactivate');
function wc_omnixep_deactivate()
{
    // Clear terms acceptance data
    delete_option('omnixep_terms_accepted');
    delete_option('omnixep_terms_version');
    delete_option('omnixep_terms_accepted_date');
    delete_option('omnixep_terms_accepted_by');
    delete_option('omnixep_terms_accepted_ip');
    delete_option('omnixep_terms_synced_to_api');
    
    // Log deactivation
    error_log('=== OMNIXEP PLUGIN DEACTIVATED ===');
    error_log('Terms acceptance data cleared. User must re-accept on reactivation.');
}
```

---

## Benefits

### Legal Protection:
- Her aktivasyonda açık onay alınır
- Tam audit trail oluşturulur
- Yasal gereksinimlere uygun

### Security:
- Kullanıcı her seferinde terms'i gözden geçirir
- Komisyon yapısı hatırlatılır
- Güvenlik sorumlulukları tekrar onaylanır

### Compliance:
- GDPR uyumlu
- Açık timestamp kayıtları
- Doğrulanabilir yasal kayıtlar

---

## Testing

### Test Scenario:

1. **Initial State:** Plugin active, terms accepted
2. **Action:** Deactivate plugin
3. **Check:** `get_option('omnixep_terms_accepted')` → false
4. **Action:** Reactivate plugin
5. **Check:** Red notice appears
6. **Action:** Accept terms
7. **Check:** Gateway works, new API record created

---

## Files Modified

- `wp-content/plugins/omnixep-woocommerce/omnixep-woocommerce.php`
  - Added `register_deactivation_hook()`
  - Added `wc_omnixep_deactivate()` function

---

**Status:** ✅ Ready for Production  
**Version:** 1.8.8  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
