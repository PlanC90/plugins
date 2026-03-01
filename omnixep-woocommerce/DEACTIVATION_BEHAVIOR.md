# OmniXEP Plugin Deactivation Behavior

**Version:** 1.0  
**Date:** February 26, 2026

---

## Overview

When the OmniXEP WooCommerce Payment Gateway plugin is deactivated, all Terms of Service acceptance data is automatically cleared. This ensures that merchants must re-accept the terms when reactivating the plugin.

---

## What Happens on Deactivation

### Automatic Data Cleanup:

When you click "Deactivate" in WordPress Plugins page, the following data is automatically deleted:

1. **omnixep_terms_accepted** - Terms acceptance flag
2. **omnixep_terms_version** - Accepted terms version
3. **omnixep_terms_accepted_date** - Date of acceptance
4. **omnixep_terms_accepted_by** - User ID who accepted
5. **omnixep_terms_accepted_ip** - IP address of acceptance
6. **omnixep_terms_synced_to_api** - API sync status

### What is NOT Deleted:

The following settings are preserved:
- Gateway configuration (wallet addresses, invoice settings, etc.)
- 2FA settings
- Transaction history
- WooCommerce orders

---

## Reactivation Flow

### Step 1: Deactivate Plugin
```
WordPress Admin → Plugins → OmniXEP Payment Gateway → Deactivate
```

**Result:** Terms acceptance data is cleared immediately.

### Step 2: Reactivate Plugin
```
WordPress Admin → Plugins → OmniXEP Payment Gateway → Activate
```

**Result:** Plugin is active but gateway is disabled.

### Step 3: Terms Acceptance Required
```
WordPress Admin → WooCommerce → Settings → Payments → OmniXEP
```

**Result:** Red notice appears:
```
⚠️ OmniXEP Payment Gateway - Terms of Service Required

IMPORTANT: You must read and accept the Terms of Service before using the OmniXEP Payment Gateway.

[📄 Read & Accept Terms of Service]
```

### Step 4: Accept Terms Again
```
Click "Read & Accept Terms of Service" → Read Terms → Check Acceptance Box → Click "I Accept"
```

**Result:** 
- Terms acceptance recorded
- Data sent to API
- Gateway enabled
- Plugin fully functional

---

## Why This Behavior?

### Legal Protection:
- Ensures explicit consent on each activation
- Creates clear audit trail
- Prevents "forgotten" acceptances
- Complies with legal best practices

### Security:
- Forces review of terms after any plugin changes
- Ensures merchant awareness of commission structure
- Confirms understanding of security responsibilities

### Compliance:
- Meets GDPR consent requirements
- Provides clear acceptance timestamps
- Creates verifiable legal records

---

## Testing the Behavior

### Test Case 1: Normal Deactivation/Reactivation

1. **Initial State:**
   - Plugin active
   - Terms accepted
   - Gateway working

2. **Action:** Deactivate plugin

3. **Expected Result:**
   - Terms data cleared
   - Log entry created

4. **Action:** Reactivate plugin

5. **Expected Result:**
   - Terms notice appears
   - Gateway disabled until acceptance
   - Settings page shows warning

6. **Action:** Accept terms again

7. **Expected Result:**
   - Gateway enabled
   - New acceptance record created
   - New API sync performed

### Test Case 2: Check Data Persistence

1. **Before Deactivation:**
```php
get_option('omnixep_terms_accepted'); // true
get_option('omnixep_terms_version'); // "2.3"
get_option('omnixep_terms_accepted_date'); // "2026-02-26 14:30:00"
```

2. **After Deactivation:**
```php
get_option('omnixep_terms_accepted'); // false
get_option('omnixep_terms_version'); // false
get_option('omnixep_terms_accepted_date'); // false
```

3. **Gateway Settings (Preserved):**
```php
get_option('woocommerce_omnixep_settings'); // Still exists with all settings
```

---

## Log Entries

### Deactivation Log:
```
=== OMNIXEP PLUGIN DEACTIVATED ===
Terms acceptance data cleared. User must re-accept on reactivation.
```

### Reactivation + New Acceptance Log:
```
=== TERMS ACCEPTANCE API SYNC START ===
Merchant: Example Company Ltd
Site: https://example.com
Version: 2.3
Language: en
Text Size: 5234 bytes
=== TERMS ACCEPTANCE API SYNC SENT ===
```

---

## API Implications

### Multiple Acceptance Records:

Each deactivation/reactivation cycle creates a NEW acceptance record in the API:

```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE merchant_id = '5d41402abc4b2a76b9719d911017c592'
ORDER BY accepted_at DESC;
```

**Result:**
```
| id | merchant_id | terms_version | accepted_at          |
|----|-------------|---------------|----------------------|
| 45 | 5d4140...   | 2.3          | 2026-02-26 16:00:00 | ← New acceptance
| 32 | 5d4140...   | 2.3          | 2026-02-26 14:30:00 | ← Previous acceptance
```

This is CORRECT behavior - it creates a complete audit trail of all acceptances.

---

## User Experience

### Admin Notice After Reactivation:

```
┌─────────────────────────────────────────────────────────────┐
│ ⚠️ OmniXEP Payment Gateway - Terms of Service Required      │
│                                                              │
│ IMPORTANT: You must read and accept the Terms of Service   │
│ before using the OmniXEP Payment Gateway.                   │
│                                                              │
│ The Terms of Service include important information about:   │
│ ✅ 0.8% commission fee structure                            │
│ ✅ Security responsibilities and wallet management          │
│ ✅ Liability limitations and risk acknowledgments           │
│ ✅ Legal protections for both merchant and developer        │
│                                                              │
│ [📄 Read & Accept Terms of Service]                         │
└─────────────────────────────────────────────────────────────┘
```

### Gateway Status on Checkout:

Before acceptance:
```
OmniXEP Payment Gateway is not available.
Please contact the store administrator.
```

After acceptance:
```
✅ Pay with XEP Cryptocurrency
   Secure payment via OmniXEP Wallet
```

---

## Frequently Asked Questions

### Q: Why do I need to accept terms again after reactivation?

**A:** This ensures you're always aware of the current terms, commission structure, and legal obligations. It's a legal best practice and protects both you and the developer.

### Q: Will I lose my wallet addresses and settings?

**A:** No. Only the terms acceptance data is cleared. All your gateway settings (wallet addresses, invoice info, 2FA, etc.) are preserved.

### Q: What if I deactivate/reactivate multiple times?

**A:** Each acceptance creates a new record in the API. This is correct behavior and creates a complete audit trail.

### Q: Can I skip the terms acceptance?

**A:** No. The gateway will not function until terms are accepted. This is a legal requirement.

### Q: What happens to existing orders?

**A:** Nothing. Past orders and transactions are not affected. Only future transactions require the gateway to be active.

---

## Technical Implementation

### Deactivation Hook:

```php
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

### Terms Check on Admin Load:

```php
function wc_omnixep_check_terms_acceptance()
{
    $terms_accepted = get_option('omnixep_terms_accepted', false);
    $terms_version = get_option('omnixep_terms_version', '0.0.0');
    $current_version = '2.3';
    
    if (!$terms_accepted || version_compare($terms_version, $current_version, '<')) {
        return false;
    }
    
    return true;
}
```

---

## Compliance Notes

### GDPR Compliance:
- ✅ Explicit consent required
- ✅ Clear acceptance mechanism
- ✅ Audit trail maintained
- ✅ User can withdraw consent (by deactivating)

### Legal Best Practices:
- ✅ Terms version tracking
- ✅ Timestamp recording
- ✅ IP address logging
- ✅ User identification

### Financial Regulations:
- ✅ Commission disclosure
- ✅ Service nature clarification
- ✅ Liability limitations
- ✅ Jurisdiction specification

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
