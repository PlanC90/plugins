# OmniXEP Remote Plugin Control System

**Version:** 1.0  
**Date:** February 26, 2026  
**Status:** ✅ Implemented

---

## Overview

Uzaktan plugin kontrol sistemi, admin'in API üzerinden herhangi bir merchant'ın plugin'ini devre dışı bırakmasına olanak sağlar. Bu sistem terms ihlali, komisyon ödememe, kötüye kullanım gibi durumlarda kullanılır.

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    ADMIN PANEL                               │
│                                                              │
│  • Admin key ile korumalı                                   │
│  • Plugin disable/enable komutları                          │
│  • Sebep ve tarih kaydı                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ API Request
                       │ (HTTPS)
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              API (api.planc.space)                           │
│                                                              │
│  • check_plugin_status endpoint                             │
│  • disable_plugin endpoint                                  │
│  • enable_plugin endpoint                                   │
│  • list_disabled_plugins endpoint                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Store in Database
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│           FIREBASE / DATABASE                                │
│                                                              │
│  • plugin_controls collection                               │
│  • merchant_id, enabled, reason, timestamp                  │
│  • Full audit trail                                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Status Check
                       │ (Every 5 minutes)
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              MERCHANT PLUGIN                                 │
│                                                              │
│  • Checks status before critical operations                 │
│  • Caches status for 5 minutes                              │
│  • Blocks operations if disabled                            │
│  • Shows admin notice                                       │
└─────────────────────────────────────────────────────────────┘
```

---

## How It Works

### 1. Admin Disables Plugin

```bash
# Admin runs command
npm run plugin:disable 5d41402abc4b2a76b9719d911017c592 "Terms violation"
```

**What happens:**
1. API receives disable request with admin key
2. Validates admin key
3. Creates/updates record in Firebase:
```json
{
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "plugin_enabled": false,
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z",
  "disabled_by": "admin@xepmarket.com"
}
```
4. Returns success response

### 2. Plugin Checks Status

**When:**
- Before payment processing
- Before admin settings display
- On checkout page load
- Every 5 minutes (cached)

**Code:**
```php
$remote_status = wc_omnixep_check_remote_status();

if (!$remote_status['enabled']) {
    // Block operation
    // Show error message
    // Log event
}
```

**API Request:**
```json
{
  "action": "check_plugin_status",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "site_url": "https://example.com"
}
```

**API Response (Disabled):**
```json
{
  "plugin_enabled": false,
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z",
  "disabled_by": "admin@xepmarket.com"
}
```

**API Response (Enabled):**
```json
{
  "plugin_enabled": true,
  "disable_reason": "",
  "disabled_at": null,
  "disabled_by": null
}
```

### 3. Plugin Blocks Operations

**If disabled:**
- ❌ Payment gateway not available on checkout
- ❌ Admin settings page shows error
- ❌ New orders cannot be placed
- ❌ Payment processing blocked
- ✅ Existing orders not affected
- ✅ Admin can view notice

### 4. Admin Re-enables Plugin

```bash
# Admin runs command
npm run plugin:enable 5d41402abc4b2a76b9719d911017c592 "Issue resolved"
```

**What happens:**
1. API receives enable request
2. Updates Firebase record:
```json
{
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "plugin_enabled": true,
  "disable_reason": "",
  "enabled_at": "2026-02-26T16:00:00Z",
  "enabled_by": "admin@xepmarket.com"
}
```
3. Plugin cache cleared
4. Plugin works normally again

---

## API Endpoints

### 1. Check Plugin Status

**Endpoint:** `POST /api`

**Request:**
```json
{
  "action": "check_plugin_status",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "site_url": "https://example.com"
}
```

**Response:**
```json
{
  "success": true,
  "plugin_enabled": false,
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z",
  "disabled_by": "admin@xepmarket.com"
}
```

### 2. Disable Plugin

**Endpoint:** `POST /api`

**Request:**
```json
{
  "action": "disable_plugin",
  "admin_key": "your-secret-admin-key",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "reason": "Terms violation"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Plugin disabled successfully",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "disabled_at": "2026-02-26T14:30:00Z"
}
```

### 3. Enable Plugin

**Endpoint:** `POST /api`

**Request:**
```json
{
  "action": "enable_plugin",
  "admin_key": "your-secret-admin-key",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "reason": "Issue resolved"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Plugin enabled successfully",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "enabled_at": "2026-02-26T16:00:00Z"
}
```

### 4. List Disabled Plugins

**Endpoint:** `POST /api`

**Request:**
```json
{
  "action": "list_disabled_plugins",
  "admin_key": "your-secret-admin-key"
}
```

**Response:**
```json
{
  "success": true,
  "disabled_plugins": [
    {
      "merchant_id": "5d41402abc4b2a76b9719d911017c592",
      "site_url": "https://example.com",
      "disable_reason": "Terms violation",
      "disabled_at": "2026-02-26T14:30:00Z",
      "disabled_by": "admin@xepmarket.com"
    },
    {
      "merchant_id": "abc123def456...",
      "site_url": "https://another-site.com",
      "disable_reason": "Commission not paid",
      "disabled_at": "2026-02-25T10:00:00Z",
      "disabled_by": "admin@xepmarket.com"
    }
  ],
  "total": 2
}
```

---

## Plugin Implementation

### 1. Status Check Function

```php
function wc_omnixep_check_remote_status()
{
    // Get merchant ID
    $merchant_id = md5(get_site_url());
    
    // Check cache first (5 minutes)
    $cache_key = 'omnixep_remote_status_' . $merchant_id;
    $cached_status = get_transient($cache_key);
    
    if ($cached_status !== false) {
        return $cached_status;
    }
    
    // Check with API
    $response = wp_remote_post('https://api.planc.space/api', [
        'body' => json_encode([
            'action' => 'check_plugin_status',
            'merchant_id' => $merchant_id,
            'site_url' => get_site_url()
        ]),
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'timeout' => 10
    ]);
    
    // Fail-open: If API fails, allow plugin to work
    if (is_wp_error($response)) {
        set_transient($cache_key, ['enabled' => true], 60);
        return ['enabled' => true, 'reason' => ''];
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    $status = [
        'enabled' => $data['plugin_enabled'] ?? true,
        'reason' => $data['disable_reason'] ?? '',
        'disabled_at' => $data['disabled_at'] ?? '',
        'disabled_by' => $data['disabled_by'] ?? ''
    ];
    
    // Cache for 5 minutes
    set_transient($cache_key, $status, 300);
    
    return $status;
}
```

### 2. Gateway Availability Check

```php
public function is_available()
{
    // Check remote status FIRST
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        return false; // Gateway not available
    }
    
    // Other checks...
    return parent::is_available();
}
```

### 3. Payment Processing Check

```php
public function process_payment($order_id)
{
    // Check remote status
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        wc_add_notice('Payment gateway unavailable: ' . $remote_status['reason'], 'error');
        return ['result' => 'failure'];
    }
    
    // Process payment...
}
```

### 4. Admin Settings Check

```php
public function admin_options()
{
    // Check remote status
    $remote_status = wc_omnixep_check_remote_status();
    if (!$remote_status['enabled']) {
        // Show error notice
        // Block settings access
        return;
    }
    
    // Show settings...
}
```

### 5. Admin Notice

```php
add_action('admin_notices', 'wc_omnixep_remote_disable_notice');
function wc_omnixep_remote_disable_notice()
{
    $status = wc_omnixep_check_remote_status();
    
    if (!$status['enabled']) {
        ?>
        <div class="notice notice-error">
            <h2>🚫 Plugin Remotely Disabled</h2>
            <p><strong>Reason:</strong> <?php echo esc_html($status['reason']); ?></p>
            <p>Contact support: support@xepmarket.com</p>
        </div>
        <?php
    }
}
```

---

## Admin Commands

### Setup

```bash
# 1. Set admin key in .env
echo "ADMIN_API_KEY=your-secret-key" >> .env

# 2. Install dependencies (if needed)
npm install
```

### Disable Plugin

```bash
npm run plugin:disable <merchant_id> "<reason>"

# Example
npm run plugin:disable 5d41402abc4b2a76b9719d911017c592 "Terms violation"
```

### Enable Plugin

```bash
npm run plugin:enable <merchant_id> "<reason>"

# Example
npm run plugin:enable 5d41402abc4b2a76b9719d911017c592 "Issue resolved"
```

### Check Status

```bash
npm run plugin:check <merchant_id>

# Example
npm run plugin:check 5d41402abc4b2a76b9719d911017c592
```

### List Disabled Plugins

```bash
npm run plugin:list-disabled
```

---

## Use Cases

### 1. Terms of Service Violation

**Scenario:** Merchant violates terms by attempting to bypass commission

**Action:**
```bash
npm run plugin:disable 5d41402abc... "Terms violation: Commission bypass attempt"
```

**Result:**
- Plugin immediately disabled
- Merchant cannot process new payments
- Admin notice displayed
- Merchant must contact support

### 2. Commission Non-Payment

**Scenario:** Merchant hasn't paid commission for 90 days

**Action:**
```bash
npm run plugin:disable 5d41402abc... "Commission not paid for 90 days"
```

**Result:**
- Plugin disabled
- Merchant must pay outstanding commission
- After payment, admin re-enables

### 3. Fraudulent Activity

**Scenario:** Suspicious activity detected

**Action:**
```bash
npm run plugin:disable 5d41402abc... "Fraudulent activity detected - under investigation"
```

**Result:**
- Immediate shutdown
- Investigation proceeds
- Re-enable after resolution

### 4. License Expiration

**Scenario:** License expired or terminated

**Action:**
```bash
npm run plugin:disable 5d41402abc... "License expired"
```

**Result:**
- Plugin disabled
- Merchant must renew license
- Re-enable after renewal

---

## Security Features

### 1. Admin Key Protection

```javascript
// Only admin with secret key can disable/enable
if (req.body.admin_key !== process.env.ADMIN_API_KEY) {
    return res.status(403).json({
        success: false,
        error: 'Unauthorized'
    });
}
```

### 2. Audit Trail

```json
{
  "merchant_id": "5d41402abc...",
  "plugin_enabled": false,
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z",
  "disabled_by": "admin@xepmarket.com",
  "enabled_at": null,
  "enabled_by": null,
  "history": [
    {
      "action": "disabled",
      "reason": "Terms violation",
      "timestamp": "2026-02-26T14:30:00Z",
      "by": "admin@xepmarket.com"
    }
  ]
}
```

### 3. Fail-Open Design

```php
// If API fails, allow plugin to work (availability over security)
if (is_wp_error($response)) {
    return ['enabled' => true];
}
```

**Rationale:** 
- Prevents false positives
- Ensures merchant can operate during API outages
- Security check is secondary to availability

### 4. Cache Strategy

```php
// Cache for 5 minutes to reduce API calls
set_transient($cache_key, $status, 300);
```

**Benefits:**
- Reduces API load
- Faster response times
- Still responsive (5 min max delay)

---

## Logging

### Plugin Logs

**When Disabled:**
```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP REMOTE CONTROL: PLUGIN DISABLED ===
[26-Feb-2026 14:30:00 UTC] Merchant ID: 5d41402abc4b2a76b9719d911017c592
[26-Feb-2026 14:30:00 UTC] Site: https://example.com
[26-Feb-2026 14:30:00 UTC] Reason: Terms violation
[26-Feb-2026 14:30:00 UTC] Disabled At: 2026-02-26T14:30:00Z
[26-Feb-2026 14:30:00 UTC] Disabled By: admin@xepmarket.com
```

**JSON Log:**
```json
{
  "event": "remote_disable_detected",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:00Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "site_url": "https://example.com",
  "disable_reason": "Terms violation",
  "disabled_at": "2026-02-26T14:30:00Z",
  "disabled_by": "admin@xepmarket.com",
  "status": "plugin_disabled_remotely"
}
```

**Payment Blocked:**
```
[26-Feb-2026 14:35:00 UTC] === OMNIXEP PAYMENT BLOCKED: REMOTE DISABLE ===
[26-Feb-2026 14:35:00 UTC] Order ID: 123
[26-Feb-2026 14:35:00 UTC] Reason: Terms violation
[26-Feb-2026 14:35:00 UTC] Merchant ID: 5d41402abc4b2a76b9719d911017c592
```

---

## Merchant Experience

### When Plugin is Disabled

**Admin Dashboard:**
```
┌─────────────────────────────────────────────────────────────┐
│ 🚫 OmniXEP Plugin Remotely Disabled                         │
│                                                              │
│ Your OmniXEP Payment Gateway has been disabled by the       │
│ administrator.                                              │
│                                                              │
│ Reason: Terms violation                                     │
│ Disabled At: 2026-02-26 14:30:00                           │
│                                                              │
│ What this means:                                            │
│ ❌ Payment gateway not available on checkout                │
│ ❌ New orders cannot be placed                              │
│ ❌ Plugin settings are locked                               │
│ ✅ Existing orders not affected                             │
│                                                              │
│ To resolve:                                                 │
│ 1. Contact support: support@xepmarket.com                   │
│ 2. Provide Merchant ID: 5d41402abc...                       │
│ 3. Address the issue                                        │
│ 4. Wait for re-enablement                                   │
└─────────────────────────────────────────────────────────────┘
```

**Checkout Page:**
```
OmniXEP Payment Gateway is not available.
Please contact the store administrator.
```

**Settings Page:**
```
┌─────────────────────────────────────────────────────────────┐
│ 🚫 Plugin Remotely Disabled                                 │
│                                                              │
│ This plugin has been disabled by the administrator and      │
│ cannot be configured.                                       │
│                                                              │
│ Reason: Terms violation                                     │
│                                                              │
│ To resolve: Contact support at support@xepmarket.com        │
│ Your Merchant ID: 5d41402abc...                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Best Practices

### 1. Clear Communication

✅ Always provide clear reason for disable
✅ Include contact information
✅ Provide merchant ID for reference
✅ Set expectations for resolution

### 2. Documentation

✅ Document all disable/enable actions
✅ Keep audit trail
✅ Record resolution steps
✅ Track repeat offenders

### 3. Gradual Enforcement

1. **Warning:** Email merchant about issue
2. **Grace Period:** Give time to resolve (7-30 days)
3. **Disable:** Only after grace period expires
4. **Re-enable:** After issue resolved

### 4. Emergency Procedures

**Immediate Disable:**
- Fraudulent activity
- Security breach
- Legal requirement
- Severe terms violation

**Standard Disable:**
- Commission non-payment (after grace period)
- Minor terms violations (after warning)
- License expiration (after notice)

---

## Troubleshooting

### Problem: Plugin Still Works After Disable

**Cause:** Cache not cleared

**Solution:**
```bash
# Wait 5 minutes for cache to expire
# OR
# Clear cache manually on merchant site
```

### Problem: API Call Fails

**Cause:** Network issue, API down

**Solution:**
- Plugin fails open (continues working)
- Check API status
- Retry after API is back

### Problem: Wrong Merchant Disabled

**Cause:** Incorrect merchant_id

**Solution:**
```bash
# Re-enable immediately
npm run plugin:enable <correct_merchant_id> "Disabled by mistake"

# Apologize to merchant
# Document incident
```

---

## Future Enhancements

### 1. Temporary Disable

```bash
# Disable for specific duration
npm run plugin:disable-temp 5d41402abc... "Maintenance" --duration 2h
```

### 2. Partial Disable

```bash
# Disable only specific features
npm run plugin:disable-feature 5d41402abc... "commission_payment"
```

### 3. Auto-Disable Rules

```javascript
// Auto-disable if commission unpaid for 90 days
if (unpaidDays > 90) {
    autoDisablePlugin(merchantId, "Commission unpaid for 90 days");
}
```

### 4. Notification System

```javascript
// Email merchant before disable
sendEmail(merchant, {
    subject: "Warning: Plugin will be disabled in 7 days",
    body: "Please pay outstanding commission..."
});
```

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
