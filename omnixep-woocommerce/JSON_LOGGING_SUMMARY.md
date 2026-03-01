# JSON Structured Logging - Quick Reference

**Status:** ✅ Implemented  
**Date:** February 26, 2026

---

## Evet, İstediğiniz JSON Formatında Loglar Var! 🎉

Plugin artık hem text hem de JSON formatında log tutuyor.

---

## JSON Log Formatı

### Terms Acceptance:
```json
{
  "event": "terms_acceptance",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:00Z",
  "ip_address": "192.168.1.100",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "user_id": 1,
  "user_email": "admin@example.com",
  "user_name": "John Doe",
  "site_url": "https://example.com",
  "site_name": "Example Store",
  "status": "accepted_and_bound",
  "acceptance_method": "web_form",
  "user_agent": "Mozilla/5.0..."
}
```

### API Sync Success:
```json
{
  "event": "api_sync_success",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:02Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "api_endpoint": "https://api.planc.space/api",
  "payload_size": 8456,
  "status": "success"
}
```

### API Sync Error:
```json
{
  "event": "api_sync_error",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:05Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "error_message": "Connection timeout",
  "error_code": "http_request_failed",
  "status": "failed"
}
```

### Plugin Deactivation:
```json
{
  "event": "plugin_deactivation",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T16:00:01Z",
  "site_url": "https://example.com",
  "site_name": "Example Store",
  "previous_terms_status": "accepted",
  "previous_terms_version": "2.3",
  "previous_acceptance_date": "2026-02-26 14:30:00",
  "deactivated_by_user_id": 1,
  "deactivated_by_email": "admin@example.com",
  "ip_address": "192.168.1.100",
  "status": "terms_cleared_reacceptance_required"
}
```

---

## Nasıl Görüntülenir?

### Tüm JSON Logları:
```bash
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log
```

### Sadece Terms Acceptance:
```bash
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | grep "terms_acceptance"
```

### Sadece API Hataları:
```bash
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | grep "api_sync_error"
```

### JSON Parse ile (jq):
```bash
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | sed 's/.*OMNIXEP_JSON_LOG: //' | jq '.'
```

---

## Event Types

| Event | Description |
|-------|-------------|
| `terms_acceptance` | Sözleşme kabul edildi |
| `api_sync_attempt` | API'ye gönderim denendi |
| `api_sync_success` | API'ye başarıyla gönderildi |
| `api_sync_error` | API'ye gönderim başarısız |
| `plugin_deactivation` | Plugin deaktive edildi |

---

## Log Dosyası

```
/wp-content/debug.log
```

Her JSON log şu prefix ile başlar:
```
OMNIXEP_JSON_LOG:
```

---

## Örnek Log Çıktısı

```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
[26-Feb-2026 14:30:00 UTC] OMNIXEP_JSON_LOG: {"event":"terms_acceptance","version":"2.3","plugin_version":"1.8.8","timestamp":"2026-02-26T14:30:00Z","ip_address":"192.168.1.100","merchant_id":"5d41402abc4b2a76b9719d911017c592","user_id":1,"user_email":"admin@example.com","status":"accepted_and_bound"}
[26-Feb-2026 14:30:00 UTC] ✅ Terms acceptance saved to WordPress options
[26-Feb-2026 14:30:01 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_attempt","version":"2.3","merchant_id":"5d41402abc4b2a76b9719d911017c592","api_endpoint":"https://api.planc.space/api"}
[26-Feb-2026 14:30:02 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_success","version":"2.3","merchant_id":"5d41402abc4b2a76b9719d911017c592","status":"success"}
```

---

## Avantajlar

✅ Machine-readable (programatik olarak parse edilebilir)  
✅ Queryable (filtrelenebilir, sorgulanabilir)  
✅ Standardized (ISO 8601 timestamps)  
✅ Compatible with log management systems (Splunk, ELK, CloudWatch)  
✅ Easy to analyze (Python, PHP, jq ile kolay analiz)

---

## Detaylı Dokümantasyon

Tüm detaylar için:
- `JSON_LOGGING_SPEC.md` - Tam spesifikasyon
- `LOGGING_SYSTEM.md` - Genel logging sistemi

---

**Sonuç:** Evet, istediğiniz JSON formatında loglar mevcut! 🎉

---

**Version:** 1.0  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
