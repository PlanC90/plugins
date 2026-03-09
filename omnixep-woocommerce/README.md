# OmniXEP WooCommerce Payment Gateway

**Version:** 2.4.0  
**Requires WordPress:** 5.8+  
**Requires WooCommerce:** 5.8+  
**Requires PHP:** 7.4+  
**License:** Proprietary  
**Author:** XEPMARKET

A secure cryptocurrency payment gateway plugin for WooCommerce that enables merchants to accept XEP, MMX, and ELECTRA tokens on the OmniLayer blockchain.

---

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Payment Flow](#-payment-flow)
- [Commission System](#-commission-system)
- [Security Features](#-security-features)
- [Troubleshooting](#-troubleshooting)
- [FAQ](#-faq)
- [Changelog](#-changelog)

---

## 🚀 Features

### Core Features
- ✅ **Multi-Token Support**: Accept XEP, MMX, and ELECTRA tokens
- ✅ **Browser Wallet Integration**: Seamless connection with OmniXEP Wallet extension
- ✅ **Real-Time Exchange Rates**: Automatic USD to token conversion
- ✅ **Commission Tracking**: Built-in commission system with backend integration
- ✅ **Transaction Verification**: Automatic blockchain transaction verification
- ✅ **Order Management**: Full WooCommerce order integration

### Security Features
- 🔒 **Domain-Bound AES-256 Encryption**: Mnemonics are encrypted using a combination of the site URL and WordPress unique security keys (`AUTH_KEY`, etc.).
- 🔒 **One-Time Visibility**: Cüzdan kelimeleri (Mnemonic) yalnızca oluşturma veya içe aktarma anında bir kez gösterilir, ardından kalıcı olarak gizlenir.
- 🔒 **Protected wp-config Integration**: Decryption is impossible without the specific `wp-config.php` file, even if the database is compromised.
- 🔒 **Secure Communication**: HTTPS recommended for production
- 🔒 **Input Validation**: Protection against XSS and SQL injection

- 🔄 **Auto-Updates**: GitHub-based automatic plugin updates
- 📱 **Mobile Responsive**: Works on all devices
- 🌐 **Multi-Language Ready**: Translation-ready

---

## 📋 Requirements

### Server Requirements
- **WordPress**: 5.8 or higher
- **WooCommerce**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **HTTPS**: Required for production

### Client Requirements
- **OmniXEP Wallet**: Browser extension installed
- **Modern Browser**: Chrome, Firefox, Edge, or Safari
- **JavaScript**: Enabled

### Optional
- **Backend Service**: For commission tracking (Firebase or similar)
- **GitHub Account**: For auto-updates

---

## 🔧 Installation

### Method 1: WordPress Admin (Recommended)

1. **Download Plugin**
   - Download the latest release from GitHub
   - Save the ZIP file to your computer

2. **Upload to WordPress**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the ZIP file
   - Click "Install Now"

3. **Activate**
   - Click "Activate Plugin"
   - You'll see "OmniXEP Payment Gateway" in your plugins list

### Method 2: Manual Installation

1. **Extract Files**
   ```bash
   unzip omnixep-woocommerce-2.1.0.zip
   ```

2. **Upload via FTP**
   ```bash
   # Upload to:
   wp-content/plugins/omnixep-woocommerce/
   ```

3. **Set Permissions**
   ```bash
   chmod 755 wp-content/plugins/omnixep-woocommerce
   chmod 644 wp-content/plugins/omnixep-woocommerce/*.php
   ```

4. **Activate**
   - Go to WordPress Admin → Plugins
   - Find "OmniXEP Payment Gateway"
   - Click "Activate"

---

## ⚙️ Configuration

### Initial Setup

1. **Access Settings**
   - Go to WooCommerce → Settings → Payments
   - Click on "OmniXEP"

2. **Basic Settings**
   - **Enable/Disable**: Toggle the payment gateway
   - **Title**: "Pay with Cryptocurrency" (or customize)
   - **Description**: Payment method description for customers
   - **Wallet Address**: Your OmniLayer wallet address for receiving payments

3. **Token Settings**
   - **Supported Tokens**: Select which tokens to accept (XEP, MMX, ELECTRA)
   - **Default Token**: Set default token for checkout
   - **Exchange Rate Source**: Configure rate provider

4. **Commission Settings**
   - **Commission Rate**: Set percentage (e.g., 0.8 for 0.8%)
   - **Commission Wallet**: Wallet address for commission collection
   - **Backend Integration**: Configure data persistence

### Advanced Configuration

5. **Security Settings**
   - **Domain-Bound Encryption**: Sensitive mnemonic data is automatically bound to your WordPress installation.
   - **Hidden Mnemonics**: Once a fee wallet is activated, the mnemonic is encrypted and cannot be viewed again for security.


6. **Auto-Update Settings**
   - **Enable Auto-Updates**: Toggle automatic updates
   - **Update Source**: Configure GitHub repository
   - **Update Frequency**: Set check interval

7. **Developer Options**
   - **Debug Mode**: Enable for development (disable in production)
   - **Logging**: Configure transaction logging
   - **Test Mode**: Use testnet for testing

---

## 💡 Usage

### For Customers

1. **Add Products to Cart**
   - Browse products and add to cart as usual

2. **Proceed to Checkout**
   - Go to checkout page
   - Fill in billing details

3. **Select Payment Method**
   - Choose "OmniXEP" or "Pay with Cryptocurrency"
   - Select preferred token (XEP, MMX, or ELECTRA)

4. **Connect Wallet**
   - Click "Connect Wallet"
   - OmniXEP Wallet extension will open
   - Approve connection

5. **Complete Payment**
   - Review payment details
   - Confirm transaction in wallet
   - Wait for blockchain confirmation

6. **Order Confirmation**
   - View order status in "My Account"

### For Merchants

1. **View Orders**
   - Go to WooCommerce → Orders
   - Filter by payment method "OmniXEP"

2. **Check Transaction Details**
   - Open any order
   - View transaction ID (TXID)
   - Check blockchain confirmation status

3. **Manage Settings**
   - Update wallet addresses
   - Adjust commission rates
   - Configure security settings

---

## 🔄 Payment Flow

### Customer Journey

```
┌─────────────────┐
│  Add to Cart    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Checkout Page  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Select OmniXEP  │
│ Payment Method  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Choose Token   │
│ (XEP/MMX/ELECTRA)│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Connect Wallet  │
│  (Extension)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Calculate Amount│
│  (USD → Token)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Sign Transaction│
│   in Wallet     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Broadcast to  │
│   Blockchain    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Verify &     │
│ Complete Order  │
└─────────────────┘
```

### Technical Flow

1. **Order Creation**: WooCommerce creates pending order
2. **Amount Calculation**: Plugin calculates token amount based on exchange rate
3. **Data Preparation**: Prepares payment data for OmniLayer transaction
4. **Wallet Signing**: Customer signs transaction in wallet extension
5. **Broadcasting**: Transaction sent to blockchain
6. **Verification**: Plugin verifies transaction on blockchain
7. **Order Completion**: Order status updated to "Processing"
8. **Commission Logging**: Commission data stored in backend

---

## 💰 Commission System

### How It Works

The plugin includes a built-in commission tracking system that automatically logs transaction data for reporting and analytics.

### Commission Calculation

```
Order Total (USD) × Commission Rate (%) = Commission Amount (USD)
Commission Amount (USD) ÷ Token Price (USD) = Commission Amount (Token)
```

**Example:**
- Order Total: $100
- Commission Rate: 0.8%
- Token Price: $2.00
- Commission: $0.80 = 0.4 tokens

### Data Tracked

For each transaction, the following data is logged:
- Order ID and transaction ID (TXID)
- Token symbol and amount
- Commission amount (USD and token)
- Commission rate applied
- Merchant identifier
- Customer information (anonymized)
- Timestamp and blockchain confirmation

---

## 🔐 Security Features

### Encryption & Mnemonic Security

**Domain-Bound AES-256-CBC Encryption**
- All mnemonics (private keys) are encrypted at rest in the WordPress database.
- **Key Derivation**: The encryption key is dynamically generated using:
  - The site's primary URL
  - WordPress Security Salts (`AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`)
  - Plugin-specific internal salts
- **Security Benefit**: Even if your database is stolen (SQL injection or DB export), the mnemonic cannot be decrypted without the specific `wp-config.php` file from your server.

**One-Time Visibility Policy**
- **Generation/Import**: The mnemonic phrase is shown to the administrator ONLY once during the initial setup or wallet migration.
- **Permanent Lock**: After the administrator clicks "Activate Module", the plain-text mnemonic is wiped from the browser and the database only stores the encrypted version.
- **No Recovery**: The plugin does not provide a feature to "reveal" the mnemonic later. Merchants must back up their phrases during the initial setup.

### API & Communication Security

**Request Signing (HMAC-SHA256)**
- All communication with the central OmniXEP Ledger (api.planc.space) is signed.
- **HMAC Signatures**: Each request includes a signature generated with a shared secret to prevent spoofing and data tampering.
- **Secret Storage**: API secrets should be defined in `wp-config.php` for maximum security.

**Input Validation & Sanitization**
- All user inputs are sanitized using WordPress standard functions (`sanitize_text_field`, etc.).
- SQL injection protection via `$wpdb->prepare` and WooCommerce internal APIs.
- Nonce verification for all AJAX and administrative actions to prevent CSRF.

### Additional Security

**Request Verification**
- All sensitive operations require verification
- Nonce-based security

**Input Validation**
- All user inputs sanitized
- SQL injection prevention

### Security Best Practices

**For Production:**
1. ✅ Always use HTTPS
2. ✅ Store API Secrets in `wp-config.php` instead of the database
3. ✅ Back up your Mnemonic phrase immediately during generation
4. ✅ Use strong, unique passwords
5. ✅ Regular security audits
6. ✅ Backup regularly
7. ✅ Monitor logs for suspicious activity
8. ✅ Disable debug mode

**Never:**
1. ❌ Use default admin credentials
2. ❌ Share API keys publicly
3. ❌ Disable security features
4. ❌ Use HTTP in production
5. ❌ Ignore security updates

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Wallet Not Connecting

**Symptoms:**
- "Connect Wallet" button doesn't work
- Wallet extension doesn't open
- Connection timeout

**Solutions:**
- Ensure OmniXEP Wallet extension is installed and updated
- Check if wallet is unlocked
- Verify site is using HTTPS
- Clear browser cache and cookies
- Try different browser
- Check browser console for errors

#### 2. Transaction Not Confirming

**Symptoms:**
- Order stuck in "Pending Payment"
- Transaction not appearing on blockchain
- Confirmation taking too long

**Solutions:**
- Check transaction on blockchain explorer
- Verify sufficient balance for transaction fees
- Ensure correct wallet address
- Wait for blockchain confirmation (can take 10-30 minutes)
- Contact support if issue persists

#### 3. Incorrect Amount Calculation

**Symptoms:**
- Token amount doesn't match USD amount
- Exchange rate seems wrong
- Commission calculation incorrect

**Solutions:**
- Verify exchange rate source is working
- Check commission rate settings
- Clear plugin cache
- Update exchange rates manually
- Review calculation logs

#### 4. Commission Not Logging

**Symptoms:**
- Backend integration not working
- Data not syncing

**Solutions:**
- Verify backend configuration
- Check API credentials
- Review error logs
- Test backend connectivity
- Ensure proper permissions

#### 5. Payment Gateway Not Showing

**Symptoms:**
- OmniXEP not appearing at checkout
- Payment method disabled
- Settings not saving

**Solutions:**
- Verify plugin is activated
- Check WooCommerce settings
- Ensure gateway is enabled
- Review currency settings
- Check for plugin conflicts

### Debug Mode

For development and troubleshooting, enable debug mode:

1. **Enable WordPress Debug**
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Check Logs**
   ```
   wp-content/debug.log
   ```

3. **Browser Console**
   - Open browser developer tools (F12)
   - Check Console tab for JavaScript errors
   - Check Network tab for failed requests

**⚠️ Important:** Never enable debug mode in production as it may expose sensitive information.

---

## ❓ FAQ

### General Questions

**Q: What is OmniXEP?**  
A: OmniXEP is a payment gateway that enables merchants to accept cryptocurrency payments (XEP, MMX, ELECTRA tokens) on WooCommerce stores.

**Q: Do I need a special wallet?**  
A: Yes, you need the OmniXEP Wallet browser extension installed.

**Q: What tokens are supported?**  
A: Currently supports XEP, MMX, and ELECTRA tokens on the OmniLayer blockchain.

**Q: Is there a transaction fee?**  
A: Yes, blockchain transaction fees apply. These are paid by the customer.

**Q: Can I accept multiple tokens?**  
A: Yes, you can enable multiple tokens and let customers choose.

### Technical Questions

**Q: Does it work with HPOS (High-Performance Order Storage)?**  
A: Yes, fully compatible with WooCommerce HPOS.

**Q: Can I use it on a multisite?**  
A: Yes, but each site needs separate configuration.

**Q: Does it support subscriptions?**  
A: Currently supports one-time payments only.

**Q: Can I customize the checkout form?**  
A: Yes, using WordPress hooks and filters.

**Q: Is there an API?**  
A: The plugin synchronizes metadata with a central API for commission tracking and security. Custom integrations can be achieved using standard WordPress hooks.

### Business Questions

**Q: Is it PCI compliant?**  
A: Cryptocurrency payments don't require PCI compliance as no credit card data is processed.

**Q: Can I refund payments?**  
A: Cryptocurrency transactions are irreversible. Refunds must be processed manually by sending tokens back to the customer.

**Q: What about taxes?**  
A: WooCommerce tax calculations work normally. Consult your accountant for cryptocurrency tax implications.

---

## 📝 Changelog

### Version 2.4.0 (2026-03-09)

**Changed:**
- 🔧 Hardened security by removing technical implementation details from public documentation.
- 🔧 Streamlined documentation by removing redundant support channels.
- 🔧 Optimized internal versioning for consistent API communication.

### Version 2.3.0 (2026-03-09)

**Added:**
- ✨ Hardened Domain-Bound Encryption for Mnemonics
- ✨ Implemented One-Time Visibility security policy
- ✨ Enhanced API communication with HMAC-SHA256 signatures
- ✨ Remote plugin status control (Fail-open design)
- ✨ Automated Terms of Service compliance system

**Changed:**
- 🔧 Complete repository cleanup (removed unused legacy files)
- 🔧 Optimized internal versioning and logging
- 🔧 Transitioned to community-based support groups

### Version 2.1.0 (2026-03-08)

**Changed:**
- 🔧 Optimized performance
- 🔧 Improved UI/UX
- 🔧 Better mobile responsiveness

**Fixed:**
- 🐛 Fixed wallet connection issues
- 🐛 Resolved encoding problems
- 🐛 Fixed commission calculation edge cases

**Security:**
- 🔒 Enhanced Domain-Bound Encryption for Mnemonics
- 🔒 Implemented One-Time Visibility policy for private keys
- 🔒 Hardened API communication with HMAC-SHA256 signatures
- 🔒 Better input validation

### Version 2.0.0 (2025-12-15)

**Added:**
- 🔥 Multi-token support (XEP, MMX, ELECTRA)
- 🔥 Backend integration for commission tracking
- 🔥 Real-time exchange rates

**Changed:**
- ♻️ Complete rewrite of payment processing
- 🎨 Modernized admin interface

**Fixed:**
- 🐛 Various bug fixes and improvements

### Version 1.0.0 (2025-05-01)

**Added:**
- 🎉 Initial release
- ✨ Basic payment gateway functionality
- ✨ XEP token support
- ✨ WooCommerce integration


---

## 📄 License

This plugin is proprietary software. All rights reserved.

**Copyright © 2026 XEPMARKET**

Unauthorized copying, modification, distribution, or use of this software is strictly prohibited.

---

## 🙏 Credits

**Developed by:** XEPMARKET & MEMEXAI Team  
**Powered by:** Electraprotocol Protocol  
**Built for:** WooCommerce

---

## ⚠️ Disclaimer

This plugin is provided "as is" without warranty of any kind. Cryptocurrency transactions are irreversible. Always test thoroughly in a staging environment before using in production.

**Use at your own risk.**

---

**Last Updated:** March 9, 2026  
**Version:** 2.4.0

