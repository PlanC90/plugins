# OmniXEP Wallet - Chrome Extension

A secure, non-custodial browser extension wallet for Electra Protocol (XEP) and OmniXEP assets. OmniXEP Wallet allows users to manage their XEP, custom tokens (like MMX), and NFTs with an intuitive and modern interface.

## ✨ Features

- 🔐 **Enhanced Security**: AES-256 encryption for mnemonic phrases and private keys.
- 💰 **Asset Management**: Seamlessly send and receive XEP and OmniXEP (MEMEX, etc.) tokens.
- 🖼️ **NFT Gallery**: Built-in support for viewing and managing your NFT collection.
- 🌐 **Multi-Language**: Localized in over 20 languages for a global user base.
- 🔗 **DApp Connector**: Seamless integration with decentralized applications and exchanges.
- ⚡ **Lightning Fast**: Optimized for speed and low-latency blockchain interactions.

## 🚀 Installation

1. Download or clone this repository.
2. Open Google Chrome and navigate to `chrome://extensions/`.
3. Enable **"Developer mode"** using the toggle in the top-right corner.
4. Click the **"Load unpacked"** button.
5. Select the `chrome` directory from your project folder.
6. The OmniXEP Wallet icon should now appear in your extension toolbar.

---

## 🛠️ Developer Integration Guide

OmniXEP Wallet injects a global `window.omnixep` provider into every web page, allowing dApps to interact with the blockchain through the user's wallet.

### 1. Detect Wallet
```javascript
if (window.omnixep) {
    console.log('OmniXEP Wallet is detected');
} else {
    console.log('Please install OmniXEP Wallet');
}
```

### 2. Connect Account
```javascript
try {
    const isConnected = await window.omnixep.connect();
    if (isConnected) {
        const address = await window.omnixep.getAddress();
        console.log('Connected address:', address);
    }
} catch (error) {
    console.error('Connection failed:', error);
}
```

### 3. Send Transaction
```javascript
const txParams = {
    to: 'EXEP...address',
    amount: 100,
    propertyId: 199 // 0 for XEP, 199 for MEMEX
};

const txid = await window.omnixep.signTransaction(txParams);
console.log('Transaction Broadcasted:', txid);
```

---

## 📂 Project Structure

- `manifest.json`: Extension configuration (MV3).
- `popup.html` / `popup.js`: Main wallet interface and user logic.
- `background.js`: Service worker for background tasks and API management.
- `content.js` / `inpage.js`: Scripts for DApp communication and provider injection.
- `bundle.js`: Compiled core logic for cryptographic operations.
- `locales.js`: Translation strings for multi-language support.

## 🛡️ Security Best Practices

- **Private Keys**: Never shared with any third-party. Everything stays encrypted locally.
- **Auditing**: Core cryptographic functions are based on industry-standard libraries (BitcoinJS, BIP39).
- **Phishing Protection**: Verify the URL of the dApp you are connecting to.

## 📄 License & Support

- **Official Website**: [electraprotocol.com](https://www.electraprotocol.com)
- **Block Explorer**: [electraprotocol.network](https://electraprotocol.network)
- **Community**: Join our Telegram and Discord for support and updates.

---

*Built with ❤️ by the Electra Protocol Community.*
