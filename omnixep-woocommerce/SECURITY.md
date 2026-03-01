# Security Changelog - Version 1.8.1

## 🆕 Latest Security Enhancements (v1.8.1)

### 6. **SecureVault Memory-Only Storage** (SEC-OMNIXEP-2026-016)
**Severity**: HIGH  
**Status**: IMPLEMENTED ✓

**Issue**: Mnemonic was stored in plain text in `sessionStorage`, accessible via XSS/console.

**Fix**:
- Implemented **SecureVault** IIFE (Immediately Invoked Function Expression) closure
- Mnemonic encrypted with random session key before storing in memory
- Private variables NOT accessible from console/XSS
- Memory overwritten with random data before deallocation

**Benefits**:
- ✅ XSS-resistant (closure-protected variables)
- ✅ Memory-only (not persisted to disk)
- ✅ Session key unique per page load
- ✅ Secure wipe with memory overwrite

**Locations**:
- `class-wc-gateway-omnixep.php:691-830`

---

### 7. **Auto-Lock and Activity Monitoring** (SEC-OMNIXEP-2026-017)
**Severity**: MEDIUM  
**Status**: IMPLEMENTED ✓

**Issue**: Mnemonic remained unlocked indefinitely (15 minutes timeout was too long).

**Fix**:
- Reduced auto-lock timeout to **5 minutes** of inactivity
- Added activity tracking (mouse, keyboard, click, scroll)
- Auto-lock when tab is hidden for >60 seconds
- Secure wipe on page unload/navigation

**Events Triggering Lock**:
- ⏱️ 5 minutes of inactivity
- 👁️ Tab hidden for 60+ seconds
- 🔄 Page navigation/refresh
- ❌ Browser/tab close

**Locations**:
- `class-wc-gateway-omnixep.php:743-785`

---

### 8. **Password Strength Enforcement** (SEC-OMNIXEP-2026-018)
**Severity**: LOW  
**Status**: IMPLEMENTED ✓

**Issue**: Weak passwords allowed for wallet encryption.

**Fix**:
- Minimum password length: 8 characters
- User feedback on security requirements

**Locations**:
- `class-wc-gateway-omnixep.php:1114-1117`

---

## Critical Security Fixes

### 1. **Payment Bypass & Verification Logic** (CVE-OMNIXEP-2026-011)
**Severity**: CRITICAL  
**Status**: FIXED ✓

**Issue**: Initial payment processing and AJAX endpoints accepted transactions without full chain verification, and the cron job relied only on TXID existence rather than full recipient/amount validation.

**Fix**: 
- Added mandatory `verify_transaction_on_chain()` check before any status update.
- Enhanced verification to validate recipient address, amount (2% tolerance), and property ID.
- Cron job now performs full re-verification of all transaction details.

**Locations**:
- `class-wc-gateway-omnixep.php:517-522`
- `class-wc-gateway-omnixep.php:1009-1014`
- `omnixep-woocommerce.php:311-320`

---

### 2. **Commission Bypass Prevention** (CVE-OMNIXEP-2026-012)
**Severity**: HIGH  
**Status**: FIXED ✓

**Issue**: The two-step payment (commission + merchant) could be bypassed because commission transactions were not verified on the blockchain.

**Fix**:
- Integrated commission transaction verification into the main verification pipeline.
- Validates commission recipient and amount against plugin settings.
- Rejects orders if commission requirements are not met on-chain.

**Locations**:
- `class-wc-gateway-omnixep.php:886-950`

---

## High Priority Security Fixes

### 3. **SSRF Protection in Transaction Lookups** (CVE-OMNIXEP-2026-013)
**Severity**: HIGH  
**Status**: FIXED ✓

**Issue**: Transaction IDs were used in API URLs without strict format validation, potentially allowing SSRF.

**Fix**:
- Implemented strict regex validation `/^[a-fA-F0-9]{64}$/` for all TXIDs used in remote requests.

**Locations**:
- `omnixep-woocommerce.php:222-225, 840-844`

---

### 4. **Session Hijacking & Authorization Protection** (CVE-OMNIXEP-2026-014)
**Severity**: HIGH  
**Status**: FIXED ✓

**Issue**: AJAX endpoints lacked sufficient authorization checks for guest orders and logged-in users.

**Fix**:
- Implemented strict session matching for guest orders using `_customer_session_key`.
- Added capability checks (`manage_woocommerce`) for admin-level actions.
- Added ownership validation for logged-in users.

**Locations**:
- `class-wc-gateway-omnixep.php:940-961`

---

## Medium Priority Security Fixes

### 5. **Safe Exchange Rate Fallback** (CVE-OMNIXEP-2026-015)
**Severity**: MEDIUM  
**Status**: FIXED ✓

**Issue**: Fallback to hardcoded TRY/USD exchange rate during API failure was insecure and exploitable.

**Fix**:
- Implemented "Last Known Good Rate" (LKGR) caching via transients.
- System now prefers the most recent successful API rate over hardcoded defaults.

**Locations**:
- `class-wc-gateway-omnixep.php:86-97`

---

## Security Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    OmniXEP Security Layers                      │
├─────────────────────────────────────────────────────────────────┤
│  Layer 1: PERMANENT STORAGE (localStorage)                      │
│  ├─ Encryption: AES-256                                         │
│  ├─ Key Derivation: PBKDF2 (150,000 iterations)                │
│  └─ Salt/IV: Random per encryption                             │
├─────────────────────────────────────────────────────────────────┤
│  Layer 2: SESSION STORAGE (SecureVault - Memory Only)          │
│  ├─ Closure-protected (XSS resistant)                          │
│  ├─ Encrypted with random session key                          │
│  ├─ Auto-wipe: 5 min inactivity                                │
│  ├─ Auto-wipe: Tab hidden >60s                                 │
│  └─ Secure memory overwrite on deallocation                    │
├─────────────────────────────────────────────────────────────────┤
│  Layer 3: TRANSACTION VERIFICATION                              │
│  ├─ On-chain recipient validation                               │
│  ├─ Amount verification (2% tolerance)                         │
│  ├─ Property ID verification                                   │
│  └─ TXID format validation (SSRF prevention)                   │
└─────────────────────────────────────────────────────────────────┘
```

## Security Best Practices Implemented
✅ Full logic-level transaction verification (Recipient, Amount, PID)  
✅ Two-step payment integrity (Commission verification)  
✅ SSRF-proof API requests (Strict Input Filtering)  
✅ Session-locked guest checkouts  
✅ Last Known Good Rate (LKGR) pattern for external APIs  
✅ Memory-only SecureVault with closure protection  
✅ Activity-based auto-lock (5 minutes)  
✅ Tab visibility monitoring  
✅ Secure memory wipe before deallocation  
✅ Password strength enforcement  

---

## Version History

### v1.8.2 (2026-02-05)
🔒 **SECURITY PATCH** - Fixed smart polling verification logic bypass, added order session ownership validation, and improved commission metadata integrity.

### v1.8.1 (2026-02-04)
🔒 **SECURITY HARDENING** - Added SecureVault memory-only storage, auto-lock, activity monitoring.

### v1.8.0 (2026-01-31)
🔒 **MAJOR SECURITY RELEASE** - Fixed critical bypasses and logic vulnerabilities.

### v1.7.3
❌ **VULNERABLE** - Logic bypasses discovered. Upgrade immediately.

---

## Reporting Security Issues
If you discover a security vulnerability, please email: security@electraprotocol.com

**Do not** open public GitHub issues for security vulnerabilities.
