# Commission Feature Documentation

## 🆕 Version 1.8.0 - Two-Transaction Commission System

### Overview
OmniXEP Payment Gateway now supports **automatic commission splitting** for payments using **TWO SEPARATE BLOCKCHAIN TRANSACTIONS** to avoid UTXO conflicts.

### ⚠️ Important: Why Two Transactions?

**UTXO Problem Solved:** 
Blockchain systems like Electra Protocol use UTXO (Unspent Transaction Output) model. Attempting to send to multiple recipients in a single transaction from a browser wallet extension can cause conflicts.

**Solution:**
1. **Transaction 1:** Customer → Merchant (99.9% of payment)
2. **Transaction 2:** Customer → Commission Wallet (0.1% of payment)

Both transactions are signed sequentially by the customer's wallet, ensuring no UTXO conflicts.

---

## ⚙️ Configuration

### Admin Settings
Navigate to: **WooCommerce → Settings → Payments → OmniXEP**

#### New Settings:

1. **Commission Wallet Address**
   - Field: Text input
   - Description: The OmniXEP address for receiving commission fees
   - Default: Empty (commission disabled)
   - Format: Valid XEP wallet address

2. **Commission Rate (%)**
   - Field: Number input
   - Description: Percentage of payment to send as commission
   - Default: `0.1%`
   - Range: `0% - 10%`
   - Step: `0.01%` (decimal precision)

---

## 💰 How It Works

### Two-Transaction Payment Flow

When a customer makes a payment with commission enabled:

**Step 1: Customer Checkout**
- Selects OmniXEP payment method
- Chooses token (XEP, MEMEX, etc.)
- Clicks "Place Order"

**Step 2: Wallet Prompts (Sequential)**
- **First Prompt:** Sign transaction to merchant (999 XEP)
- **Second Prompt:** Sign transaction to commission wallet (1 XEP)
- Customer approves both in OmniXEP Wallet Extension

**Step 3: Blockchain Processing**
- Both transactions broadcast to Electra Protocol network
- Independent confirmation (no UTXO conflict)
- Both TXIDs recorded in order meta

**Step 4: Verification**
- Smart Polling checks merchant transaction
- Order status updates when confirmed
- Commission transaction tracked separately

### Payment Split Calculation

**Example:** Customer pays **1000 XEP**, Commission Rate: **0.1%**

```
TRANSACTION 1 (Merchant):
  To:      Merchant Wallet Address
  Amount:  999 XEP (99.9%)
  TXID:    abc123...

TRANSACTION 2 (Commission):
  To:      Commission Wallet Address  
  Amount:  1 XEP (0.1%)
  TXID:    def456...
```

### Automatic Split
The plugin automatically calculates:
- **Merchant Amount** = Total - Commission
- **Commission Amount** = Total × (Commission Rate ÷ 100)

---

## 📊 Frontend Display

### Checkout Page
When commission is enabled, customers see:

```
ℹ️ Payment Split:
Merchant: 99.90%
Commission: 0.10%
```

This information appears below the merchant wallet QR code.

---

## 📋 Admin Order Details

### Order Meta Box
Each order displays complete payment breakdown with **both transaction IDs**:

```
💰 Two-Transaction Split:
→ Merchant: 999 XEP
→ Commission: 1 XEP
Commission Wallet: EVxD...abc123

Merchant Transaction:
✓ abc123def456... 🔗

Commission Transaction:
✓ 789ghi012jkl... 🔗

[View Merchant TX]  [View Commission TX]
```

### Saved Meta Data
The following information is stored for each order:
- `_omnixep_txid` - Merchant transaction ID
- `_omnixep_commission_txid` - Commission transaction ID
- `_omnixep_merchant_amount` - Amount received by merchant
- `_omnixep_commission_amount` - Commission amount
- `_omnixep_commission_rate` - Commission percentage used
- `_omnixep_commission_address` - Commission wallet address

---

## 🔧 Technical Implementation

### Server-Side Calculation
Commission is calculated **server-side only** for security:

```php
// Example calculation
$commission_percentage = 0.1 / 100;  // 0.1% = 0.001
$commission_amount = $total * $commission_percentage;
$merchant_amount = $total - $commission_amount;
```

### Decimal Precision
- For tokens with **decimals** (8 decimals): Rounded to 8 decimal places
- For tokens **without decimals** (integers): 
  - Commission: `ceil()` - Rounded up
  - Merchant: `floor()` - Rounded down

---

## 🎯 Use Cases

### Platform Fees
Marketplace platforms can automatically collect platform fees on each transaction.

### Referral Commissions
Set up affiliate/referral commission structures.

### Payment Processing Fees
Cover blockchain transaction costs or service fees.

### Revenue Sharing
Implement automatic profit-sharing with partners.

---

## 🔐 Security Features

### Commission Validation
✅ Server-side calculation only (client cannot manipulate)  
✅ Commission rate validated (0% - 10% range)  
✅ Wallet address validation  
✅ All values sanitized and escaped  

### Audit Trail
✅ Complete payment split logged in order notes  
✅ Commission details visible in admin panel  
✅ Immutable order meta data  

---

## ⚡ Quick Setup Guide

### Step 1: Enable Commission
1. Go to **WooCommerce → Settings → Payments**
2. Click **Manage** next to OmniXEP
3. Scroll to **Commission Wallet Address**
4. Enter your commission wallet address

### Step 2: Set Commission Rate
1. Find **Commission Rate (%)** field
2. Enter desired percentage (e.g., `0.1` for 0.1%)
3. Click **Save changes**

### Step 3: Verify
1. Place a test order
2. Check order details in admin
3. Verify split calculation is correct

---

## 🎨 Customization Examples

### Different Commission Rates by Order Total

```php
// Add custom commission rate logic
add_filter('wc_omnixep_commission_rate', function($rate, $order) {
    $total = $order->get_total();
    
    if ($total >= 1000) {
        return 0.05; // 0.05% for orders >= $1000
    }
    
    return $rate; // Default rate
}, 10, 2);
```

### Dynamic Commission Wallet

```php
// Route commission to different wallets
add_filter('wc_omnixep_commission_address', function($address, $order) {
    // Example: Different wallet for high-value orders
    if ($order->get_total() >= 5000) {
        return 'EVxD_HIGH_VALUE_WALLET_ADDRESS';
    }
    
    return $address; // Default
}, 10, 2);
```

---

## 📊 Reporting

### Commission Reports
View commission data in order exports:
- Export orders with OmniXEP payment method
- Columns: Order ID, Total, Merchant Amount, Commission Amount
- Filter by date range

### Database Query Example

```sql
SELECT 
    pm1.post_id as order_id,
    pm2.meta_value as total_amount,
    pm3.meta_value as merchant_amount,
    pm4.meta_value as commission_amount
FROM wp_postmeta pm1
JOIN wp_postmeta pm2 ON pm1.post_id = pm2.post_id AND pm2.meta_key = '_omnixep_amount'
JOIN wp_postmeta pm3 ON pm1.post_id = pm3.post_id AND pm3.meta_key = '_omnixep_merchant_amount'
JOIN wp_postmeta pm4 ON pm1.post_id = pm4.post_id AND pm4.meta_key = '_omnixep_commission_amount'
WHERE pm1.meta_key = '_payment_method' AND pm1.meta_value = 'omnixep'
AND pm4.meta_value > 0;
```

---

## 🐛 Troubleshooting

### Commission Not Appearing

**Problem:** Commission info not showing on checkout  
**Solution:** 
1. Verify commission address is entered
2. Ensure commission rate > 0
3. Clear cache (WP Super Cache, W3 Total Cache, etc.)

### Incorrect Split Calculation

**Problem:** Amounts don't match expected split  
**Solution:**
1. Check commission rate setting
2. Verify token decimals configuration
3. Remember: Integer tokens use ceil()/floor()

### Commission Address Invalid

**Problem:** Error when saving commission address  
**Solution:**
1. Verify address format (XEP address)
2. No extra spaces before/after
3. Check address on blockchain explorer

---

## 📝 Changelog

### Version 1.8.0 (2026-01-31)
✅ Added commission wallet configuration  
✅ Implemented automatic payment splitting  
✅ Server-side commission calculation  
✅ Admin display of commission details  
✅ Checkout page commission info  
✅ Secure validation and sanitization  

---

## 🤝 Support

For commission feature support:
- Email: support@electraprotocol.com
- Documentation: https://www.electraprotocol.com/omnixep/commission

---

## ⚖️ License

This feature is part of the OmniXEP WooCommerce Payment Gateway plugin.
Licensed under GPL v2 or later.
