# OmniXEP WooCommerce Payment Gateway - Technical Specification for Legal Review

**Document Version:** 1.0  
**Date:** February 26, 2026  
**Prepared For:** Legal Counsel Review  
**Developer:** XEPMARKET & Ceyhun Yılmaz  
**Plugin Version:** 1.8.8

---

## EXECUTIVE SUMMARY

OmniXEP WooCommerce Payment Gateway is a **SOFTWARE-ONLY** plugin that enables WooCommerce stores to accept cryptocurrency payments (XEP and tokens) via the OmniXEP Wallet. 

**CRITICAL LEGAL POINTS:**

1. ✅ **NO CUSTODY** - Developer NEVER has access to merchant funds or private keys
2. ✅ **NO PAYMENT PROCESSING** - Plugin does NOT process, hold, or transmit customer payments
3. ✅ **SOFTWARE LICENSE** - 0.8% commission is a software service fee, NOT a payment processing fee
4. ✅ **DIRECT PAYMENTS** - All customer payments go DIRECTLY to merchant's wallet
5. ✅ **MERCHANT CONTROL** - Merchant has 100% control over their wallet and funds

---

## 1. SYSTEM ARCHITECTURE

### 1.1 Core Components

```
┌─────────────────────────────────────────────────────────────┐
│                    CUSTOMER (Buyer)                          │
│                                                              │
│  • Has OmniXEP Wallet (mobile or browser extension)         │
│  • Controls their own private keys                          │
│  • Initiates payment transaction                            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Payment Transaction
                       │ (Blockchain Network)
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              MERCHANT WALLET (Seller)                        │
│                                                              │
│  • Receives customer payments DIRECTLY                      │
│  • Merchant controls private keys (stored in browser)       │
│  • Developer has NO ACCESS to this wallet                   │
│  • Funds are NEVER held by plugin or developer              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Commission Payment
                       │ (Separate transaction)
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              FEE WALLET (Merchant-Controlled)                │
│                                                              │
│  • Merchant creates this wallet for commission payments     │
│  • Merchant controls private keys (stored in browser)       │
│  • Plugin calculates 0.8% commission                        │
│  • Merchant pays commission from this wallet                │
│  • Developer has NO ACCESS to this wallet                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Commission Transfer
                       │ (Merchant-initiated)
                       ↓
┌─────────────────────────────────────────────────────────────┐
│           DEVELOPER COMMISSION WALLET                        │
│                                                              │
│  • Receives 0.8% software service fee                       │
│  • Developer controls this wallet                           │
│  • Receives commission ONLY when merchant pays              │
│  • NO ACCESS to merchant or customer funds                  │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Data Flow

```
1. Customer places order on WooCommerce store
   ↓
2. Plugin calculates total amount in cryptocurrency
   ↓
3. Plugin displays payment QR code with merchant's wallet address
   ↓
4. Customer scans QR code with OmniXEP Wallet
   ↓
5. Customer signs transaction with THEIR private key
   ↓
6. Transaction is broadcast to blockchain network
   ↓
7. Funds go DIRECTLY to merchant's wallet
   ↓
8. Plugin verifies transaction on blockchain
   ↓
9. Order status updated to "processing" or "completed"
   ↓
10. Plugin calculates 0.8% commission
   ↓
11. Merchant pays commission from fee wallet (separate transaction)
```

---

## 2. WALLET MANAGEMENT

### 2.1 Merchant Wallet (Main Receiving Wallet)

**Purpose:** Receives customer payments

**Storage Location:** 
- Private key (mnemonic phrase) stored in merchant's browser localStorage
- NEVER stored in WordPress database
- NEVER transmitted to any server
- NEVER accessible by developer

**Access Control:**
- Only merchant has access via their browser
- Protected by browser security
- Can be exported/backed up by merchant
- Auto-masked after 30 seconds for security

**Technical Implementation:**
```javascript
// Mnemonic stored in browser localStorage ONLY
localStorage.setItem('omnixep_mnemonic_encrypted', encryptedMnemonic);

// NEVER sent to server
// NEVER stored in database
// NEVER accessible by plugin developer
```

**Legal Implications:**
- ✅ Merchant has 100% custody of funds
- ✅ Developer has ZERO access to funds
- ✅ Plugin is NOT a custodial service
- ✅ Plugin is NOT a payment processor

### 2.2 Fee Wallet (Commission Payment Wallet)

**Purpose:** Pays 0.8% software service fee to developer

**Storage Location:**
- Same as merchant wallet (browser localStorage)
- Separate wallet address for commission payments
- Merchant controls private keys

**How It Works:**
1. Plugin calculates 0.8% commission on each sale
2. Commission accumulates as "debt" in WordPress database
3. Merchant reviews accumulated commission
4. Merchant initiates payment from fee wallet
5. Payment goes to developer's commission wallet

**Technical Implementation:**
```php
// Commission calculation (server-side)
$commission_amount = $total_amount * 0.008; // 0.8%

// Store as debt (NOT a payment)
update_post_meta($order_id, '_omnixep_commission_debt', $commission_amount);

// Merchant pays later via browser wallet
// Developer CANNOT force payment
// Developer CANNOT access fee wallet
```

**Legal Implications:**
- ✅ Commission is a software service fee
- ✅ NOT a payment processing fee
- ✅ Merchant controls when to pay
- ✅ Developer cannot access fee wallet
- ✅ Payment is voluntary (but required by license terms)

### 2.3 Developer Commission Wallet

**Purpose:** Receives 0.8% software service fee

**Storage Location:**
- Developer's own wallet
- Hardcoded in plugin (obfuscated)
- Receives commission payments from merchants

**Access Control:**
- Only developer has access
- Cannot access merchant or customer funds
- Only receives what merchant voluntarily pays

**Technical Implementation:**
```php
// Obfuscated commission wallet address
private static function _get_ca()
{
    return self::_xd('GzMTDSUfVjUYMQ4tH1YWUCtLMiw8BD46QB0CGB8fMA0XLQ==');
}

// Commission rate (0.8%)
private static function _get_cr()
{
    return (float) self::_xd('U0tB'); // 0.008
}
```

**Legal Implications:**
- ✅ Developer receives software license fee
- ✅ NOT payment processing revenue
- ✅ Cannot access merchant funds
- ✅ Cannot force commission payment

---

## 3. PAYMENT FLOW

### 3.1 Desktop/Web Payment Flow

```
1. Customer adds products to cart
   ↓
2. Customer proceeds to checkout
   ↓
3. Customer selects "OmniXEP Payment" method
   ↓
4. Plugin calculates:
   - Total amount in store currency (USD/TRY)
   - Equivalent amount in cryptocurrency (XEP/Token)
   - Exchange rate from CoinGecko API
   ↓
5. Customer clicks "Place Order"
   ↓
6. Plugin generates payment page with:
   - QR code containing merchant wallet address
   - Payment amount
   - Order details
   ↓
7. Customer scans QR code with OmniXEP Wallet
   ↓
8. OmniXEP Wallet opens with pre-filled transaction:
   - Recipient: Merchant's wallet address
   - Amount: Calculated cryptocurrency amount
   ↓
9. Customer reviews transaction in wallet
   ↓
10. Customer enters wallet password/PIN
   ↓
11. Customer confirms transaction
   ↓
12. Wallet signs transaction with customer's private key
   ↓
13. Wallet broadcasts transaction to blockchain
   ↓
14. Transaction ID (TXID) returned to plugin
   ↓
15. Plugin verifies transaction on blockchain:
    - Checks recipient address matches merchant
    - Checks amount matches order total
    - Checks transaction is confirmed
   ↓
16. If verified:
    - Order status → "processing" or "completed"
    - Customer receives order confirmation
    - Merchant receives order notification
   ↓
17. Plugin calculates 0.8% commission
   ↓
18. Commission stored as "debt" in database
   ↓
19. Merchant pays commission later (separate transaction)
```

### 3.2 Mobile Payment Flow

```
1. Customer browses store on mobile device
   ↓
2. Customer adds products to cart
   ↓
3. Customer proceeds to checkout
   ↓
4. Customer selects "OmniXEP Payment"
   ↓
5. Plugin detects mobile device
   ↓
6. Plugin generates deep link:
   omnixep://pay?address=MERCHANT_ADDRESS&amount=AMOUNT&orderId=ORDER_ID
   ↓
7. Customer clicks "Pay with OmniXEP Wallet"
   ↓
8. Deep link opens OmniXEP Wallet app
   ↓
9. Wallet app shows pre-filled transaction
   ↓
10. Customer confirms payment in app
   ↓
11. App broadcasts transaction to blockchain
   ↓
12. App redirects back to store with TXID
   ↓
13. Plugin verifies transaction (same as desktop)
   ↓
14. Order completed
```

---

## 4. SECURITY ARCHITECTURE

### 4.1 Private Key Security

**Storage:**
- ✅ Stored ONLY in browser localStorage
- ✅ Encrypted with site-specific key
- ✅ NEVER stored in database
- ✅ NEVER transmitted to server
- ✅ NEVER accessible by developer

**Encryption:**
```javascript
// Site-specific encryption key
const siteKey = md5(window.location.hostname + 'omnixep_secret_salt');

// Encrypt mnemonic
const encrypted = CryptoJS.AES.encrypt(mnemonic, siteKey).toString();

// Store in browser
localStorage.setItem('omnixep_mnemonic_encrypted', encrypted);
```

**Auto-Masking:**
- Mnemonic phrase auto-masked after 30 seconds
- Prevents shoulder surfing
- Merchant must re-enter to view

**Access Control:**
- 2FA required to view mnemonic
- Double confirmation required
- Logged for audit trail

### 4.2 Transaction Security

**TXID Validation:**
```php
// Validate TXID format (64 hex characters)
if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
    return false; // Invalid format
}
```

**Replay Attack Prevention:**
```php
// Check if TXID already used
$existing_order = $wpdb->get_var($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} 
     WHERE meta_key = '_omnixep_txid' AND meta_value = %s",
    $txid
));

if ($existing_order) {
    return false; // TXID already used
}
```

**Race Condition Prevention:**
```php
// Use database transaction with FOR UPDATE lock
$wpdb->query('START TRANSACTION');
$existing_order = $wpdb->get_var($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} 
     WHERE meta_key = '_omnixep_txid' AND meta_value = %s 
     FOR UPDATE",
    $txid
));
```

**Rate Limiting:**
```php
// Limit verification attempts
$attempts = (int) $order->get_meta('_omnixep_verification_attempts');
if ($attempts > 10) {
    return false; // Too many attempts
}
```

### 4.3 Wallet Balance Protection

**Daily Limit:**
- Fee wallet limited to 50,000 XEP maximum
- Excess automatically transferred to merchant wallet
- Prevents large theft if fee wallet compromised

**Implementation:**
```php
// Check fee wallet balance daily
$balance = get_balance($fee_wallet_address);
$limit = 50000; // XEP

if ($balance > $limit) {
    $excess = $balance - $limit;
    // Auto-transfer excess to merchant wallet
    transfer($fee_wallet_address, $merchant_address, $excess);
}
```

### 4.4 Content Security Policy (CSP)

**XSS Prevention:**
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://api.qrserver.com;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
```

---

## 5. COMMISSION SYSTEM

### 5.1 Commission Calculation

**Rate:** 0.8% (fixed)

**Calculation:**
```php
function calculate_commission_split($total_amount, $token_decimals = 8)
{
    $commission_rate = 0.008; // 0.8%
    $commission_amount = $total_amount * $commission_rate;
    $merchant_amount = $total_amount - $commission_amount;
    
    return array(
        'merchant_amount' => $merchant_amount,
        'commission_amount' => $commission_amount,
        'total_amount' => $total_amount
    );
}
```

**Example:**
- Customer pays: 1000 XEP
- Commission (0.8%): 8 XEP
- Merchant receives: 992 XEP
- Developer receives: 8 XEP (when merchant pays)

### 5.2 Commission Payment Flow

**Step 1: Order Completed**
```php
// Store commission as debt
update_post_meta($order_id, '_omnixep_commission_debt', $commission_amount);
update_post_meta($order_id, '_omnixep_debt_settled', 'no');
```

**Step 2: Merchant Reviews Debt**
```
WordPress Admin → OmniXEP Settings → Commission Debt
- Shows total accumulated commission
- Shows per-order breakdown
- Shows payment status
```

**Step 3: Merchant Pays Commission**
```javascript
// Merchant clicks "Pay Commission" button
// Browser wallet signs transaction
// Transaction sent to developer's commission wallet
// Debt marked as "settled"
```

**Step 4: Verification**
```php
// Verify commission payment on blockchain
$verified = verify_transaction_on_chain($txid, $commission_amount, $developer_wallet);

if ($verified) {
    update_post_meta($order_id, '_omnixep_debt_settled', 'yes');
    update_post_meta($order_id, '_omnixep_commission_txid', $txid);
}
```

### 5.3 Commission Enforcement

**License Terms:**
- Commission payment is REQUIRED by license agreement
- Failure to pay results in license termination
- Plugin may be disabled if commission not paid

**Technical Enforcement:**
- Plugin tracks unpaid commission
- Admin notices displayed for unpaid debt
- Gateway may be disabled after grace period

**Legal Enforcement:**
- Commission is a contractual obligation
- Non-payment is breach of license terms
- Developer may pursue legal remedies

---

## 6. DATA HANDLING

### 6.1 Personal Data Collected

**Customer Data:**
- ❌ NO private keys
- ❌ NO wallet passwords
- ❌ NO mnemonic phrases
- ✅ Transaction ID (TXID) - public blockchain data
- ✅ Order details (name, email, address) - standard WooCommerce data

**Merchant Data:**
- ✅ Legal name (for invoicing)
- ✅ Country (for invoicing)
- ✅ Tax ID (for invoicing)
- ✅ Email (for invoicing)
- ✅ Wallet addresses (public blockchain data)
- ❌ NO private keys
- ❌ NO mnemonic phrases

### 6.2 Data Storage

**WordPress Database:**
```sql
-- Order metadata
_omnixep_txid              -- Transaction ID (public)
_omnixep_amount            -- Payment amount
_omnixep_token_name        -- Token used (XEP, etc.)
_omnixep_commission_debt   -- Commission owed
_omnixep_debt_settled      -- Payment status
_omnixep_verification_attempts -- Security counter

-- Plugin settings
omnixep_terms_accepted     -- Terms acceptance flag
omnixep_terms_version      -- Terms version
omnixep_terms_accepted_date -- Acceptance date
omnixep_terms_accepted_by  -- User ID
omnixep_terms_accepted_ip  -- IP address
```

**Browser localStorage:**
```javascript
// ONLY stored in browser, NEVER in database
omnixep_mnemonic_encrypted  -- Encrypted mnemonic phrase
omnixep_wallet_address      -- Wallet address (public)
omnixep_fee_wallet_address  -- Fee wallet address (public)
```

**API Database (Firebase/MySQL):**
```sql
-- Terms acceptance records
omnixep_terms_acceptances
  - merchant_id
  - terms_version
  - terms_text
  - accepted_at
  - accepted_by_email
  - site_url
  - merchant_legal_name
  - wallet_addresses
```

### 6.3 Data Transmission

**To API:**
- ✅ Terms acceptance data
- ✅ Merchant profile (for invoicing)
- ✅ Wallet addresses (public)
- ✅ Commission records
- ❌ NO private keys
- ❌ NO customer payment data

**To Blockchain:**
- ✅ Transaction data (public)
- ✅ Wallet addresses (public)
- ✅ Payment amounts (public)
- ❌ NO personal information

**To Third Parties:**
- ✅ CoinGecko API (for exchange rates) - NO personal data
- ✅ QR Code API (for QR generation) - NO personal data
- ❌ NO payment processors
- ❌ NO financial institutions

---

## 7. BLOCKCHAIN VERIFICATION

### 7.1 Transaction Verification Process

**Step 1: Fetch Transaction from Blockchain**
```php
// Query blockchain API
$api_url = "https://api.omnixep.com/tx/{$txid}";
$response = wp_remote_get($api_url);
$tx_data = json_decode($response['body'], true);
```

**Step 2: Verify Transaction Details**
```php
// Check recipient address
if ($tx_data['recipient'] !== $merchant_address) {
    return false; // Wrong recipient
}

// Check amount
if ($tx_data['amount'] < $expected_amount) {
    return false; // Insufficient amount
}

// Check confirmations
if ($tx_data['confirmations'] < 1) {
    return false; // Not confirmed yet
}
```

**Step 3: Verify Transaction is Valid**
```php
// Check transaction is not double-spend
// Check transaction is included in valid block
// Check block is part of main chain
```

**Step 4: Update Order Status**
```php
if ($verified) {
    $order->payment_complete($txid);
    $order->add_order_note('Payment verified on blockchain. TXID: ' . $txid);
}
```

### 7.2 Blockchain APIs Used

**Primary API:** OmniXEP API
- URL: https://api.omnixep.com
- Purpose: Transaction verification, balance checking
- Data: Public blockchain data only

**Fallback API:** ElectrumX
- Purpose: Backup verification
- Data: Public blockchain data only

**Exchange Rate API:** CoinGecko
- URL: https://api.coingecko.com
- Purpose: Cryptocurrency price data
- Data: Public market data only

---

## 8. LEGAL CLASSIFICATIONS

### 8.1 What OmniXEP Plugin IS

✅ **Software Tool**
- Provides technical functionality
- Enables cryptocurrency payments
- Integrates with WooCommerce

✅ **Software License**
- Licensed to merchants
- 0.8% software service fee
- Terms of Service required

✅ **Payment Gateway Integration**
- Connects store to blockchain
- Displays payment information
- Verifies transactions

✅ **Non-Custodial Solution**
- Merchant controls funds
- Merchant controls private keys
- No third-party custody

### 8.2 What OmniXEP Plugin IS NOT

❌ **NOT a Payment Processor**
- Does NOT process payments
- Does NOT hold funds
- Does NOT transmit funds
- Does NOT settle transactions

❌ **NOT a Financial Institution**
- Does NOT provide banking services
- Does NOT provide financial services
- Does NOT provide money transmission
- Does NOT provide payment services

❌ **NOT a Custodial Service**
- Does NOT hold customer funds
- Does NOT hold merchant funds
- Does NOT control private keys
- Does NOT have access to wallets

❌ **NOT a Money Transmitter**
- Does NOT transmit money
- Does NOT transfer funds
- Does NOT facilitate fund transfers
- Funds go directly peer-to-peer

❌ **NOT a Payment Institution**
- Does NOT require payment institution license
- Does NOT provide regulated payment services
- Does NOT fall under PSD2/PSD3
- Software tool only

### 8.3 Regulatory Considerations

**FinCEN (USA):**
- Plugin is software, not money transmitter
- No FinCEN registration required
- Merchant may need to register (depends on volume)

**EU Payment Services Directive (PSD2/PSD3):**
- Plugin is not payment service provider
- No PSD2 license required
- Software tool exemption applies

**MiCA (Markets in Crypto-Assets Regulation):**
- Plugin is not crypto-asset service provider
- No MiCA license required
- Software tool exemption applies

**Turkey (MASAK):**
- Plugin is software tool
- Merchant responsible for compliance
- Developer provides software only

**GDPR (Data Protection):**
- Plugin collects minimal personal data
- Terms acceptance recorded
- Privacy policy required
- Data retention policies apply

---

## 9. RISK ANALYSIS

### 9.1 Technical Risks

**Risk:** Private key theft from browser
**Mitigation:**
- Encryption with site-specific key
- Auto-masking after 30 seconds
- 2FA for viewing mnemonic
- Browser security best practices

**Risk:** Transaction replay attack
**Mitigation:**
- TXID uniqueness check
- Database transaction locks
- Rate limiting

**Risk:** Fee wallet compromise
**Mitigation:**
- Daily balance limit (50,000 XEP)
- Auto-transfer excess to merchant wallet
- Separate wallet for commission payments

**Risk:** Blockchain network issues
**Mitigation:**
- Multiple API endpoints
- Fallback verification methods
- Transaction confirmation requirements

### 9.2 Legal Risks

**Risk:** Misclassification as payment processor
**Mitigation:**
- Clear Terms of Service
- "Software Only" disclaimers
- No custody of funds
- No payment processing

**Risk:** Regulatory compliance
**Mitigation:**
- Merchant responsible for compliance
- Clear liability limitations
- Jurisdiction specification (Turkey)
- Legal disclaimers

**Risk:** Commission non-payment
**Mitigation:**
- License termination clause
- Technical enforcement (gateway disable)
- Legal remedies available
- Audit trail of debt

### 9.3 Business Risks

**Risk:** Merchant disputes commission
**Mitigation:**
- Clear Terms of Service
- Commission disclosed upfront
- Acceptance required before use
- Audit trail of acceptance

**Risk:** Customer payment disputes
**Mitigation:**
- Blockchain verification
- Immutable transaction records
- Clear payment instructions
- Customer education

**Risk:** Exchange rate volatility
**Mitigation:**
- Real-time exchange rates
- Clear amount display
- Customer confirms amount
- Merchant accepts crypto risk

---

## 10. COMPLIANCE REQUIREMENTS

### 10.1 Merchant Responsibilities

**KYC/AML Compliance:**
- Merchant responsible for customer due diligence
- Merchant responsible for suspicious activity reporting
- Plugin does NOT provide KYC/AML services

**Tax Compliance:**
- Merchant responsible for tax reporting
- Merchant responsible for VAT/sales tax
- Plugin provides transaction records
- Commission invoiced monthly

**Regulatory Compliance:**
- Merchant responsible for local regulations
- Merchant responsible for licensing (if required)
- Merchant responsible for consumer protection laws

**Data Protection:**
- Merchant responsible for customer data
- Merchant responsible for privacy policy
- Plugin provides data processing tools
- GDPR compliance required

### 10.2 Developer Responsibilities

**Software Maintenance:**
- Bug fixes and security updates
- Compatibility with WooCommerce updates
- Documentation and support

**Terms of Service:**
- Clear and enforceable terms
- Legal protection for developer
- Liability limitations
- Jurisdiction specification

**Commission Collection:**
- Invoice generation
- Payment tracking
- Tax reporting (developer's taxes)

**Data Protection:**
- Secure API endpoints
- Data retention policies
- Privacy policy compliance

---

## 11. TECHNICAL SPECIFICATIONS

### 11.1 System Requirements

**WordPress:**
- Version: 5.8 or higher
- PHP: 7.4 or higher
- MySQL: 5.6 or higher

**WooCommerce:**
- Version: 5.8 or higher
- HPOS (High-Performance Order Storage) compatible

**Server:**
- HTTPS required
- cURL enabled
- JSON support
- Session support

**Browser (Merchant):**
- Modern browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- localStorage support
- 2MB+ storage available

### 11.2 Blockchain Specifications

**Supported Cryptocurrencies:**
- XEP (Electra Protocol)
- OmniXEP Tokens (custom tokens on XEP blockchain)

**Transaction Format:**
- Standard XEP transaction
- OP_RETURN for order metadata (optional)
- Minimum 1 confirmation required

**Wallet Compatibility:**
- OmniXEP Wallet (mobile and browser extension)
- HD wallet (BIP32/BIP39/BIP44)
- Mnemonic phrase (12 or 24 words)

### 11.3 API Specifications

**OmniXEP API:**
- Endpoint: https://api.omnixep.com
- Methods: GET (transaction, balance, UTXO)
- Format: JSON
- Authentication: None (public data)

**CoinGecko API:**
- Endpoint: https://api.coingecko.com
- Methods: GET (price data)
- Format: JSON
- Rate limit: 50 calls/minute

**Plugin API:**
- Endpoint: https://api.planc.space/api
- Methods: POST (terms acceptance, commission sync)
- Format: JSON
- Authentication: API key (future)

---

## 12. GLOSSARY

**Blockchain:** Distributed ledger technology that records transactions

**Commission:** 0.8% software service fee paid by merchant to developer

**Cryptocurrency:** Digital currency using cryptography (e.g., XEP)

**Custody:** Holding or controlling someone else's funds (Plugin does NOT do this)

**Fee Wallet:** Merchant-controlled wallet used to pay commission

**Mnemonic Phrase:** 12 or 24 words used to recover wallet (private key)

**Merchant Wallet:** Merchant-controlled wallet that receives customer payments

**Non-Custodial:** User controls their own private keys and funds

**OmniXEP:** Cryptocurrency wallet and token platform on Electra Protocol

**Payment Processor:** Entity that processes payments (Plugin is NOT this)

**Private Key:** Secret key used to sign transactions (stored in browser only)

**TXID:** Transaction ID - unique identifier for blockchain transaction

**Wallet Address:** Public address for receiving cryptocurrency (like bank account number)

**XEP:** Electra Protocol cryptocurrency

---

## 13. LEGAL DISCLAIMERS

### 13.1 No Custody Disclaimer

**IMPORTANT:** The OmniXEP WooCommerce Payment Gateway plugin does NOT:
- Hold customer funds
- Hold merchant funds
- Control private keys
- Access wallet credentials
- Provide custodial services

All funds are controlled by the respective wallet owners (customers and merchants).

### 13.2 No Payment Processing Disclaimer

**IMPORTANT:** The OmniXEP WooCommerce Payment Gateway plugin does NOT:
- Process payments
- Transmit funds
- Settle transactions
- Provide payment services
- Act as payment intermediary

All payments go directly from customer to merchant via blockchain network.

### 13.3 Software License Disclaimer

**IMPORTANT:** The 0.8% commission is:
- A software license fee
- A technical service fee
- NOT a payment processing fee
- NOT a financial service fee
- Required by license terms

### 13.4 Liability Limitation

**IMPORTANT:** Developer liability is limited to:
- Maximum: $100 USD or 30 days of commission (whichever is lower)
- No liability for: lost funds, blockchain issues, regulatory penalties, merchant errors

### 13.5 Jurisdiction

**IMPORTANT:** These terms are governed by:
- Laws of: Republic of Türkiye
- Courts of: Kırklareli Courts and Enforcement Offices
- Language: English (authoritative version)

---

## 14. CONCLUSION

The OmniXEP WooCommerce Payment Gateway is a **SOFTWARE TOOL** that enables cryptocurrency payments. It is:

✅ **Non-Custodial** - Merchant controls all funds and private keys
✅ **Non-Processing** - No payment processing or fund transmission
✅ **Software License** - 0.8% fee is for software services
✅ **Transparent** - All transactions verifiable on blockchain
✅ **Secure** - Multiple security layers and best practices
✅ **Compliant** - Designed to avoid regulated activities

The plugin provides technical functionality only. The developer:
- ❌ Does NOT hold funds
- ❌ Does NOT process payments
- ❌ Does NOT access private keys
- ❌ Does NOT provide financial services
- ✅ Provides software tool only
- ✅ Receives software license fee (0.8%)

---

## 15. CONTACT INFORMATION

**Developer:**
- Name: XEPMARKET & Ceyhun Yılmaz
- Email: legal@xepmarket.com
- Support: support@xepmarket.com
- Website: https://xepmarket.com

**Plugin Information:**
- Name: OmniXEP WooCommerce Payment Gateway
- Version: 1.8.8
- License: Proprietary (Terms of Service v2.3)

**Legal Jurisdiction:**
- Country: Republic of Türkiye
- Courts: Kırklareli Courts and Enforcement Offices

---

**Document Version:** 1.0  
**Date:** February 26, 2026  
**Prepared By:** XEPMARKET & Ceyhun Yılmaz  
**Purpose:** Legal Review and Contract Preparation

---

© 2026 XEPMARKET. All Rights Reserved.

**CONFIDENTIAL:** This document contains proprietary information and is intended for legal counsel review only.
