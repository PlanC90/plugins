const express = require('express');
const { WalletCore } = require('./wallet-bundle.js');
const axios = require('axios');

const app = express();
app.use(express.json());

// POST /send-fee - Otomatik fee gönderimi
app.post('/send-fee', async (req, res) => {
    try {
        const { mnemonic, fromAddress, toAddress, amountSatoshi } = req.body;
        
        console.log(`FEE SERVICE: Sending ${amountSatoshi} satoshi from ${fromAddress} to ${toAddress}`);
        
        // WalletCore ile transaction oluştur
        const txResult = await WalletCore.sendXEP(mnemonic, 0, toAddress, amountSatoshi);
        
        if (txResult && txResult.txid) {
            console.log(`FEE SERVICE SUCCESS: TXID ${txResult.txid}`);
            res.json({ success: true, txid: txResult.txid });
        } else {
            console.log('FEE SERVICE FAILED: No TXID returned');
            res.json({ success: false, error: 'Transaction failed' });
        }
        
    } catch (error) {
        console.error('FEE SERVICE ERROR:', error.message);
        res.json({ success: false, error: error.message });
    }
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

const PORT = 3001;
app.listen(PORT, () => {
    console.log(`Auto-Fee Service running on port ${PORT}`);
});
