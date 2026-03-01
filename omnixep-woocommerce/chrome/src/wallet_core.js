// --- TARAYICI UYUMLULUĞU İÇİN GEREKLİ ---

// --- KÜTÜPHANELER ---
const bitcoin = require('bitcoinjs-lib');
const bip39 = require('bip39');
const bip32 = require('bip32'); // V5 sürümü
const CryptoJS = require("crypto-js");
const QRCode = require('qrcode');
const bitcoinMessage = require('bitcoinjs-message');

// --- XEP AĞ AYARLARI (Mainnet) ---
const XEP_NETWORK = {
    messagePrefix: '\x14XEP Signed Message:\n',
    bech32: 'ep',
    bip32: { public: 0x0488b21e, private: 0x0488ade4 },
    pubKeyHash: 0x37, // 'P' (Legacy)
    scriptHash: 0x89, // 'x' (P2SH - Wrapped SegWit) -> Decimal 137
    wif: 0xB7,
};

// --- API ADRESİ ---
const API_URL = "https://api.omnixep.com";

// --- PENDING UTXO TRACKER (Persistent via chrome.storage) ---
// Prevents replaying same inputs that are in pending/unconfirmed state
let pendingUTXOs = {}; // key: "txid:vout", value: timestamp
const PENDING_EXPIRY_MS = 10 * 60 * 1000; // 10 minutes (Prevents re-selecting recently used coins)

// Initialize from storage
if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
    chrome.storage.local.get(['pendingUTXOs'], (result) => {
        if (result.pendingUTXOs) {
            pendingUTXOs = result.pendingUTXOs;
            // console.log("LOADED PENDING UTXOs FROM STORAGE:", Object.keys(pendingUTXOs).length);
        }
    });
}

function savePendingUTXOs() {
    if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
        chrome.storage.local.set({ pendingUTXOs: pendingUTXOs });
    }
}

function markUTXOsAsPending(inputs) {
    inputs.forEach(inp => {
        const key = `${inp.txid}:${inp.vout}`;
        pendingUTXOs[key] = Date.now();
        console.log("MARKED UTXO AS PENDING:", key);
    });
    savePendingUTXOs();
}

function unmarkPendingUTXOs(inputs) {
    if (!inputs) return;
    inputs.forEach(inp => {
        const key = `${inp.txid}:${inp.vout}`;
        if (pendingUTXOs[key]) {
            delete pendingUTXOs[key];
            console.log("UNMARKED PENDING UTXO:", key);
        }
    });
    savePendingUTXOs();
}

function clearAllPendingUTXOs() {
    const count = Object.keys(pendingUTXOs).length;
    pendingUTXOs = {};
    savePendingUTXOs();
    console.log("CLEARED ALL PENDING UTXOs:", count);
    return count;
}

function isUTXOPending(txid, vout) {
    const key = `${txid}:${vout}`;
    const ts = pendingUTXOs[key];
    if (!ts) return false;
    // Expired?
    if (Date.now() - ts > PENDING_EXPIRY_MS) {
        delete pendingUTXOs[key];
        savePendingUTXOs();
        return false;
    }
    return true;
}

function filterPendingUTXOs(utxos) {
    const fresh = utxos.filter(u => !isUTXOPending(u.txid, u.vout));
    if (fresh.length < utxos.length) {
        console.log("FILTERED OUT", utxos.length - fresh.length, "PENDING UTXOs. Remaining:", fresh.length);
    }
    if (fresh.length === 0 && utxos.length > 0) {
        console.warn("ALL UTXOs ARE PENDING! Clearing expired ones...");
        // Force clear expired
        const now = Date.now();
        for (const key in pendingUTXOs) {
            if (now - pendingUTXOs[key] > PENDING_EXPIRY_MS) {
                delete pendingUTXOs[key];
            }
        }
        savePendingUTXOs();
        // Retry filter
        return utxos.filter(u => !isUTXOPending(u.txid, u.vout));
    }
    return fresh;
}

// Jeno proposal:
const ENC_CONFIG = {
    version: 1,
    iterations: 150000,          // iterations
    keySize: 256 / 32,           // 256 bits
    saltSizeBytes: 16,
    ivSizeBytes: 16
};

function deriveKey(password, salt, iterations) {
    return CryptoJS.PBKDF2(password, salt, {
        keySize: ENC_CONFIG.keySize,
        iterations: iterations
    });
}
// end Jeno proposal

module.exports = {
    // Clear pending UTXOs (for debugging/recovery)
    clearPendingUTXOs: clearAllPendingUTXOs,

    // ---------------------------------------------
    // 1. GÜVENLİK (Şifreleme/Çözme)
    // ---------------------------------------------

    // Jeno proposal:
    encrypt: function (data, password) {
        if (typeof data !== 'string') {
            data = String(data);
        }

        const salt = CryptoJS.lib.WordArray.random(ENC_CONFIG.saltSizeBytes);
        const iv = CryptoJS.lib.WordArray.random(ENC_CONFIG.ivSizeBytes);

        const key = deriveKey(password, salt, ENC_CONFIG.iterations);

        const encrypted = CryptoJS.AES.encrypt(data, key, { iv: iv });

        const payload = {
            v: ENC_CONFIG.version,
            iter: ENC_CONFIG.iterations,
            salt: CryptoJS.enc.Base64.stringify(salt),
            iv: CryptoJS.enc.Base64.stringify(iv),
            ct: encrypted.ciphertext.toString(CryptoJS.enc.Base64)
        };

        return JSON.stringify(payload); //return string as before
    },

    decrypt: function (encryptedData, password) {
        if (typeof encryptedData !== 'string') {
            throw new Error("Invalid encrypted data");
        }

        // 1) new version (JSON)
        if (encryptedData.trim().startsWith('{')) {
            let payload;
            try {
                payload = JSON.parse(encryptedData);
            } catch (e) {
                // If the JSON is corrupted, the data is considered invalid
                throw new Error("Invalid json data!");
            }

            if (!payload || !payload.salt || !payload.iv || !payload.ct) {
                throw new Error("Wrong Password!");
            }

            const iterations = payload.iter || ENC_CONFIG.iterations;

            const salt = CryptoJS.enc.Base64.parse(payload.salt);
            const iv = CryptoJS.enc.Base64.parse(payload.iv);
            const ctWA = CryptoJS.enc.Base64.parse(payload.ct);

            const key = deriveKey(password, salt, iterations);

            const cipherParams = CryptoJS.lib.CipherParams.create({
                ciphertext: ctWA
            });

            let decrypted;
            try {
                decrypted = CryptoJS.AES.decrypt(cipherParams, key, { iv: iv });
            } catch (e) {
                throw new Error("Wrong Password!");
            }

            const originalText = decrypted.toString(CryptoJS.enc.Utf8);
            if (!originalText) {
                throw new Error("Wrong Password!");
            }

            return originalText;
        }

        // 2) Legacy format (simple AES encryption without JSON wrapper)
        try {
            const bytes = CryptoJS.AES.decrypt(encryptedData, password);
            const originalText = bytes.toString(CryptoJS.enc.Utf8);
            if (!originalText) {
                throw new Error("Wrong Password!");
            }
            return originalText;
        } catch (e) {
            throw new Error("Wrong Password!");
        }
    },
    // end Jeno proposal

    /*
        encrypt: function (data, password) {
            return CryptoJS.AES.encrypt(data, password).toString();
        },
    
        decrypt: function (encryptedData, password) {
            try {
                const bytes = CryptoJS.AES.decrypt(encryptedData, password);
                const originalText = bytes.toString(CryptoJS.enc.Utf8);
                if (!originalText) throw new Error("Şifre Yanlış");
                return originalText;
            } catch (e) {
                throw new Error("Şifre Yanlış!");
            }
        },
    */
    // ---------------------------------------------
    // 2. CÜZDAN OLUŞTURMA & YÖNETİM
    // ---------------------------------------------
    generateMnemonic: function () {
        return bip39.generateMnemonic();
    },

    validateMnemonic: function (mnemonic) {
        return bip39.validateMnemonic(mnemonic);
    },

    // *** KRİTİK NOKTA: BURASI 'x' ADRESİ ÜRETİR ***
    getAccountByIndex: function (mnemonic, index) {
        const seed = bip39.mnemonicToSeedSync(mnemonic);
        const root = bip32.fromSeed(seed, XEP_NETWORK);

        // Yol: m / 49' / 597' / INDEX' (OmniXEP Mobile uyumlu)
        const path = `m/49'/597'/${index}'`;
        const child = root.derivePath(path);

        // P2SH içine gömülmüş P2WPKH (Wrapped SegWit)
        // Bu yapı Electra Protocol'de 'x' ile başlayan adres üretir.
        const p2sh = bitcoin.payments.p2sh({
            redeem: bitcoin.payments.p2wpkh({
                pubkey: child.publicKey,
                network: XEP_NETWORK
            }),
            network: XEP_NETWORK
        });

        return {
            index: index,
            address: p2sh.address,
            privateKeyWIF: child.toWIF()
        };
    },

    // Validate XEP address format (P2SH 'x', Legacy 'P', or Bech32 'ep1')
    validateAddress: function (address) {
        if (!address || typeof address !== 'string') return false;

        // Check length (typical XEP addresses are 34 chars for P/x, variable for bech32)
        if (address.length < 26 || address.length > 62) return false;

        // Check prefix
        const validPrefixes = ['P', 'x', 'ep1'];
        const hasValidPrefix = validPrefixes.some(p => address.startsWith(p));
        if (!hasValidPrefix) return false;

        // Basic character validation (alphanumeric, no 0/O/I/l for base58)
        if (address.startsWith('ep1')) {
            // Bech32: lowercase alphanumeric except 1, b, i, o
            return /^ep1[ac-hj-np-z02-9]+$/.test(address);
        } else {
            // Base58: no 0, O, I, l
            return /^[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]+$/.test(address);
        }
    },

    // Validate address via API (more thorough check)
    validateAddressOnline: async function (address) {
        try {
            const res = await fetch(`${API_URL}/api/v2/address/${address}/balances`);
            const json = await res.json();
            return !json.error; // If no error, address is valid
        } catch (e) {
            return false;
        }
    },

    // ---------------------------------------------
    // 3. API FONKSİYONLARI (Bakiye, Token, Stats)
    // ---------------------------------------------
    getNetworkStats: async function () {
        try {
            const res = await fetch(`${API_URL}/api/v2/networkstats`);
            const json = await res.json();
            return json.data;
        } catch (e) { return null; }
    },

    getTokenInfo: async function (propertyId) {
        if (propertyId === 0) return null;
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

            const res = await fetch(`${API_URL}/api/v2/omnixep/contracts/${propertyId}`, {
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const json = await res.json();
            if (json.data && json.data.length > 0) return json.data[0];
            return null;
        } catch (e) { return null; }
    },

    getBalances: async function (address) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

            const res = await fetch(`${API_URL}/api/v2/address/${address}/balances?_t=${Date.now()}`, {
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const json = await res.json();
            return json.data ? json.data.balances : [];
        } catch (e) {
            if (e.name === 'AbortError') {
                console.error("Balance fetch timeout");
            }
            return [];
        }
    },

    getTransactions: async function (address, count = 20) {
        try {
            const res = await fetch(`${API_URL}/api/v2/address/${address}/transactions?count=${count}&_t=${Date.now()}`);
            const json = await res.json();
            return json.data || [];
        } catch (e) { return []; }
    },

    getTransactionById: async function (txid) {
        try {
            const res = await fetch(`${API_URL}/api/v2/transaction/${txid}`);
            const json = await res.json();
            if (json.error || !json.data) return null;
            return json.data;
        } catch (e) { return null; }
    },

    // Get NFT balances for an address (grouped by PID)
    // Uses official OmniXEP NFT endpoint: /api/v2/omnixep/address/{address}/nfts
    // Returns array like: [{ property_id: 216, balance: 3 }, ...]
    getNFTBalances: async function (address) {
        try {
            const res = await fetch(`${API_URL}/api/v2/omnixep/address/${address}/nfts?_t=${Date.now()}`);
            const json = await res.json();
            if (!json.data || !Array.isArray(json.data)) return [];

            const byPid = new Map();
            for (const item of json.data) {
                const pid = item.pid;
                if (pid === undefined || pid === null) continue;
                const current = byPid.get(pid) || { property_id: pid, balance: 0 };
                current.balance += 1;
                byPid.set(pid, current);
            }

            return Array.from(byPid.values());
        } catch (e) {
            console.error('getNFTBalances error:', e);
            return [];
        }
    },

    // Get NFT details (image, data, etc.)
    getNFTDetail: async function (pid, nftId) {
        try {
            const res = await fetch(`${API_URL}/api/v2/omnixep/nft/${pid}/${nftId}`);
            const json = await res.json();
            return json.data || null;
        } catch (e) { return null; }
    },

    // Get owned NFT indices from transaction history
    getOwnedNFTIndices: async function (address, pid) {
        try {
            const res = await fetch(`${API_URL}/api/v2/address/${address}/transactions?count=100`);
            const json = await res.json();
            if (!json.data) return [];

            const ownedIndices = new Set();
            const sentIndices = new Set();

            // Go through transactions in order (newest first)
            for (const tx of json.data) {
                if (tx.pid === pid && (tx.type_str === '[NFT TRANSFER]' || tx.type_str === '[NFT GRANT]')) {
                    const start = tx.token_start;
                    const end = tx.token_end;

                    for (let i = start; i <= end; i++) {
                        if (tx.recipient === address) {
                            // Received this NFT
                            if (!sentIndices.has(i)) {
                                ownedIndices.add(i);
                            }
                        } else if (tx.sender === address) {
                            // Sent this NFT
                            sentIndices.add(i);
                            ownedIndices.delete(i);
                        }
                    }
                }
            }

            return Array.from(ownedIndices);
        } catch (e) { return []; }
    },

    getXepPrice: async function () {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

            const res = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=electra-protocol&vs_currencies=usd&include_24hr_change=true', {
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const json = await res.json();
            return json['electra-protocol']; // { usd: 0.00..., usd_24h_change: ... }
        } catch (e) { return null; }
    },

    // MEMEX (OMEMEX) Fiyatı - GeckoTerminal API
    getMemexPrice: async function () {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

            const res = await fetch('https://api.geckoterminal.com/api/v2/networks/omax-chain/pools/0xc84edbf1e3fef5e4583aaa0f818cdfebfcae095b', {
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const json = await res.json();
            if (json.data && json.data.attributes) {
                const attrs = json.data.attributes;
                return {
                    usd: parseFloat(attrs.base_token_price_usd),
                    usd_24h_change: parseFloat(attrs.price_change_percentage?.h24 || 0)
                };
            }
            return null;
        } catch (e) { return null; }
    },

    // ---------------------------------------------
    // 4. TRANSFER (İmzalama ve Gönderme)
    // ---------------------------------------------

    // Sign Message (Bitcoin Signed Message format)
    signMessage: function (mnemonic, index, message) {
        const wallet = this.getAccountByIndex(mnemonic, index);
        const keyPair = bitcoin.ECPair.fromWIF(wallet.privateKeyWIF, XEP_NETWORK);
        const privateKey = keyPair.privateKey;

        // Ensure message prefix matches XEP network
        // bitcoinjs-message defaults to Bitcoin prefix if not specified, 
        // but can take a third argument for simple signature.
        // However, bitcoinjs-message 'sign' function signature: sign(message, privateKey, compressed, messagePrefix)

        const messagePrefix = XEP_NETWORK.messagePrefix || '\x18Bitcoin Signed Message:\n';

        const signature = bitcoinMessage.sign(message, privateKey, keyPair.compressed, messagePrefix);
        return signature.toString('base64');
    },

    // Helper: Get UTXOs
    getUTXOs: async function (address) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

            // CORRECT ENDPOINT: /utxos (with 's')
            const res = await fetch(`${API_URL}/api/v2/address/${address}/utxos?_t=${Date.now()}`, {
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const json = await res.json();

            if (json.error) {
                console.error("UTXO API Error:", json.error);
                return [];
            }

            // Log raw API response for debugging
            // console.log("RAW UTXO API RESPONSE:", JSON.stringify(json.data?.[0] || {}, null, 2));

            // Normalize property names from API format
            const utxos = (json.data || []).map(u => ({
                txid: u.txid,
                vout: u.outputIndex ?? u.vout,  // API may use 'outputIndex' or 'vout'
                value: u.satoshis ?? u.value,   // API may use 'satoshis' or 'value'
                script: u.script,
                address: u.address,
                height: u.height,
                confirmations: u.confirmations ?? (u.height > 0 ? 1 : 0) // Track confirmations
            }));

            // Filter to only confirmed UTXOs (at least 1 confirmation)
            const confirmedUtxos = utxos.filter(u => u.confirmations > 0 || u.height > 0);

            console.log("FETCHED UTXOs:", utxos.length, "total,", confirmedUtxos.length, "confirmed, Total:", confirmedUtxos.reduce((s, u) => s + (u.value || 0), 0) / 100000000, "XEP");
            return confirmedUtxos;
        } catch (e) {
            console.error("Get UTXO Error:", e);
            return []; // Return empty instead of throwing to prevent UI hang
        }
    },

    // Native XEP Transaction (P2SH-P2WPKH)
    // XEP is native blockchain layer, requires client-side UTXO selection
    sendNativeTransaction: async function (mnemonic, index, toAddress, amountSatoshi) {
        const wallet = this.getAccountByIndex(mnemonic, index);
        const keyPair = bitcoin.ECPair.fromWIF(wallet.privateKeyWIF, XEP_NETWORK);
        const p2wpkh = bitcoin.payments.p2wpkh({ pubkey: keyPair.publicKey, network: XEP_NETWORK });
        const p2sh = bitcoin.payments.p2sh({ redeem: p2wpkh, network: XEP_NETWORK });

        // 1. Get UTXOs
        const utxos = await this.getUTXOs(wallet.address);
        const freshUTXOs = filterPendingUTXOs(utxos);

        // 2. Coin Selection
        let inputSum = 0;
        const inputs = [];
        const fee = 200000; // 0.002 XEP (optimized fee for reliability)
        const targetAmount = amountSatoshi + fee;

        // Sort by value asc - smallest first to avoid stale large UTXOs
        freshUTXOs.sort((a, b) => (a.value || 0) - (b.value || 0));

        // Add some randomness to prevent always selecting the same UTXOs
        // Shuffle UTXOs within similar value ranges to avoid deterministic selection
        for (let i = freshUTXOs.length - 1; i > 0; i--) {
            // Only shuffle with nearby UTXOs (within 10% value difference)
            const currentVal = freshUTXOs[i].value || 0;
            const prevVal = freshUTXOs[i - 1].value || 0;
            if (Math.abs(currentVal - prevVal) / currentVal < 0.1 && Math.random() > 0.5) {
                [freshUTXOs[i], freshUTXOs[i - 1]] = [freshUTXOs[i - 1], freshUTXOs[i]];
            }
        }

        console.log("FRESH UTXOS:", freshUTXOs.length, "items");

        for (const u of freshUTXOs) {
            const val = u.value || 0;

            if (!u.txid || u.vout === undefined || val === 0) {
                console.error("Invalid UTXO:", u);
                continue;
            }

            inputs.push(u);
            inputSum += val;

            if (inputSum >= targetAmount) break;
        }

        if (inputSum < targetAmount) {
            throw new Error(`Insufficient balance. Required: ${targetAmount / 100000000} XEP, Available: ${inputSum / 100000000} XEP`);
        }

        // 3. Build Transaction
        const txb = new bitcoin.TransactionBuilder(XEP_NETWORK);

        inputs.forEach(u => {
            txb.addInput(u.txid, u.vout);
        });

        // Convert address to output script (handles XEP's custom scriptHash prefix)
        const toOutputScript = bitcoin.address.toOutputScript(toAddress, XEP_NETWORK);
        txb.addOutput(toOutputScript, amountSatoshi);

        const change = inputSum - targetAmount;
        if (change > 546) {
            const changeOutputScript = bitcoin.address.toOutputScript(wallet.address, XEP_NETWORK);
            txb.addOutput(changeOutputScript, change);
        }

        // 4. Sign
        for (let i = 0; i < inputs.length; i++) {
            const u = inputs[i];
            txb.sign(i, keyPair, p2sh.redeem.output, null, u.value);
        }

        const signedTx = txb.build();
        const signedHex = signedTx.toHex();
        const txid = signedTx.getId();

        console.log("NATIVE XEP TX BUILT:", txid);

        // 5. Broadcast
        markUTXOsAsPending(inputs);

        try {
            return await this.broadcastRawTx(signedHex, txid);
        } catch (e) {
            console.error("Broadcast failed:", e);
            // Only unmark if it was NOT a double-spend error (keep them pending/blacklisted if spent)
            if (!e.message.includes('already spent')) {
                unmarkPendingUTXOs(inputs);
            }
            throw e;
        }
    },

    // Helper: Sign and Broadcast Transaction (Universal)
    _signAndBroadcast: async function (mnemonic, index, rawTxHex, inputsFromApi) {
        const wallet = this.getAccountByIndex(mnemonic, index);
        const keyPair = bitcoin.ECPair.fromWIF(wallet.privateKeyWIF, XEP_NETWORK);

        // Load transaction from hex
        const tx = bitcoin.Transaction.fromHex(rawTxHex);
        const txb = bitcoin.TransactionBuilder.fromTransaction(tx, XEP_NETWORK);

        // Prepare P2SH-P2WPKH data
        const p2wpkh = bitcoin.payments.p2wpkh({ pubkey: keyPair.publicKey, network: XEP_NETWORK });
        const p2sh = bitcoin.payments.p2sh({ redeem: p2wpkh, network: XEP_NETWORK });

        // Iterate and sign inputs
        for (let i = 0; i < tx.ins.length; i++) {
            const inputTxId = Buffer.from(tx.ins[i].hash).reverse().toString('hex');
            const inputVout = tx.ins[i].index;

            // Find matching input info from API response
            const match = inputsFromApi.find(inp => inp.txid === inputTxId && inp.vout === inputVout);
            if (!match) {
                console.error(`Missing input info for ${inputTxId}:${inputVout}`, inputsFromApi);
                throw new Error(`Input info not found for ${inputTxId}:${inputVout} - Cannot sign.`);
            }

            // Sign (SegWit requires value)
            txb.sign(i, keyPair, p2sh.redeem.output, null, match.amount);
        }

        const signedTx = txb.build();
        const signedHex = signedTx.toHex();
        const localTxid = signedTx.getId();

        console.log("SIGNED TX & BROADCASTING:", localTxid);

        // Broadcast
        return await this.broadcastRawTx(signedHex, localTxid);
    },

    broadcastRawTx: async function (signedHex, localTxid) {
        console.log("BROADCASTING TX:", localTxid);
        // console.log("RAW TX HEX:", signedHex.substring(0, 100) + "...");

        const resSend = await fetch(`${API_URL}/api/v2/sendrawtransaction`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ raw_tx: signedHex })
        });

        console.log("BROADCAST RESPONSE STATUS:", resSend.status);

        const jsonSend = await resSend.json();
        console.log("BROADCAST RESPONSE:", JSON.stringify(jsonSend));

        if (jsonSend.error) {
            console.error("BROADCAST ERROR:", jsonSend.error);

            const errorCode = jsonSend.error.code || jsonSend.error;

            // Check for fee-related errors first
            const errorMsg = jsonSend.error.message || '';
            if (errorMsg.includes('fee-not-enough') || errorMsg.includes('minfee')) {
                throw new Error("Transaction fee too low. Please try again.");
            }

            // -5300: "Transaction already in block chain" 
            // -5301: "Transaction already in mempool"
            // If the transaction is already there, it means our broadcast was successful (even if we think it failed).
            if (errorCode === -5300 || errorCode === -5301 || errorMsg.includes('already in')) {
                console.log("Transaction already exists in network/mempool. Treating as success.", localTxid);
                return localTxid;
            }

            // Other errors: throw
            const errMsg = typeof jsonSend.error === 'string' ? jsonSend.error : (jsonSend.error.message || JSON.stringify(jsonSend.error));
            throw new Error(errMsg);
        }

        if (!jsonSend.data) {
            console.error("BROADCAST: Server did not return txid, marking as failed.", jsonSend);
            throw new Error("Transaction could not be sent to network.");
        }

        console.log("BROADCAST SUCCESS, TXID:", jsonSend.data);
        return jsonSend.data; // Real txid
    },

    // Unified Send (XEP or Token)
    sendTransaction: async function (mnemonic, index, toAddress, amountSatoshi, propertyId) {
        // Prepare Payload
        const payload = {
            property_id: propertyId, // 0 for XEP
            sender: this.getAccountByIndex(mnemonic, index).address,
            recipient: toAddress,
            amount: amountSatoshi
        };

        // console.log("POST /rawsendtoken", JSON.stringify(payload, null, 2));

        // Try up to 2 times (initial + 1 retry) for UTXO insufficient errors
        let lastError = null;
        for (let attempt = 0; attempt < 2; attempt++) {
            if (attempt > 0) {
                console.log("Retrying sendTransaction after UTXO error, attempt:", attempt + 1);
                await new Promise(r => setTimeout(r, 1500)); // Wait 1.5s before retry
            }

            try {
                // Call API to get Raw TX
                const res = await fetch(`${API_URL}/api/v2/omnixep/rawsendtoken`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (json.error) {
                    const errorCode = json.error.code || json.error;
                    const errorMsg = json.error.message || JSON.stringify(json.error);

                    // If UTXO insufficient error, retry
                    if (errorCode === -5100 || errorMsg.includes('UTXO') || errorMsg.includes('insufficient')) {
                        lastError = new Error("Insufficient XEP balance for token transfer. Please add XEP to your account.");
                        continue; // Retry
                    }

                    throw new Error("Raw Tx Oluşturma Hatası: " + (typeof json.error === 'string' ? json.error : JSON.stringify(json.error)));
                }

                const { raw_tx, inputs } = json.data;
                if (!raw_tx || !inputs) throw new Error("API'den geçersiz yanıt (raw_tx veya inputs eksik).");

                // Sign & Broadcast
                return await this._signAndBroadcast(mnemonic, index, raw_tx, inputs);
            } catch (e) {
                lastError = e;
                // Only retry for UTXO related errors
                if (!e.message.includes('UTXO') && !e.message.includes('insufficient') && !e.message.includes('-5100')) {
                    throw e;
                }
            }
        }

        // If we get here, all retries failed
        throw lastError || new Error("Token transfer failed.");
    },

    // Send NFT
    sendNFT: async function (mnemonic, index, toAddress, propertyId, tokenStart, tokenEnd) {
        const payload = {
            property_id: propertyId,
            sender: this.getAccountByIndex(mnemonic, index).address,
            recipient: toAddress,
            token_start: tokenStart,
            token_end: tokenEnd
        };

        console.log("POST /rawsendnft", payload);

        const res = await fetch(`${API_URL}/api/v2/omnixep/rawsendnft`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.error) {
            throw new Error("Raw NFT Tx Oluşturma Hatası: " + (typeof json.error === 'string' ? json.error : JSON.stringify(json.error)));
        }

        const { raw_tx, inputs } = json.data;
        if (!raw_tx || !inputs) throw new Error("API'den geçersiz yanıt (raw_tx veya inputs eksik).");

        return await this._signAndBroadcast(mnemonic, index, raw_tx, inputs);
    },

    // Legacy Wrappers (Optional, for backward compat if popup.js uses them explicitly)
    sendXEP: async function (mnemonic, index, toAddress, amountSatoshi) {
        return this.sendTransaction(mnemonic, index, toAddress, amountSatoshi, 0);
    },

    sendToken: async function (mnemonic, index, toAddress, amountSatoshi, propertyId) {
        return this.sendTransaction(mnemonic, index, toAddress, amountSatoshi, propertyId);
    },

    // -----------------------
    // Internal cache for static data (5 minute TTL)
    // -----------------------
    _cache: {
        networkStats: { data: null, ts: 0 },
        contracts: { data: null, ts: 0 },
        contractsUpdateIndex: { data: null, ts: 0 }
    },

    // -----------------------
    // Generic GET helper with error handling
    // -----------------------
    _get: async function (url) {
        const res = await fetch(url);
        const json = await res.json();
        if (json.error) {
            throw new Error(typeof json.error === 'string' ? json.error : JSON.stringify(json.error));
        }
        return json.data;
    },

    // -----------------------
    // 1. Network Stats
    // -----------------------
    getNetworkStats: async function () {
        const now = Date.now();
        const cache = this._cache.networkStats;
        if (cache.data && now - cache.ts < 5 * 60 * 1000) {
            return cache.data;
        }
        const data = await this._get(`${API_URL}/api/v2/networkstats`);
        cache.data = data;
        cache.ts = now;
        return data;
    },

    // -----------------------
    // 2. Address Transactions
    // -----------------------
    getAddressTransactions: async function (address, pid = 0, page = 1, count = 100) {
        const url = `${API_URL}/api/v2/address/${address}/transactions?pid=${pid}&page=${page}&count=${count}`;
        return await this._get(url);
    },

    // -----------------------
    // 3. Address Balances
    // -----------------------
    getAddressBalances: async function (address) {
        const url = `${API_URL}/api/v2/address/${address}/balances`;
        return await this._get(url);
    },

    // -----------------------
    // 4. Address UTXOs
    // -----------------------
    getAddressUTXOs: async function (address) {
        const url = `${API_URL}/api/v2/address/${address}/utxos`;
        return await this._get(url);
    },

    // -----------------------
    // 5. Address NFTs (optional pid filter)
    // -----------------------
    getAddressNFTs: async function (address, pid = null, page = 1, count = 100) {
        let url = `${API_URL}/api/v2/omnixep/address/${address}/nfts?page=${page}&count=${count}`;
        if (pid !== null) url += `&pid=${pid}`;
        return await this._get(url);
    },

    // -----------------------
    // 6. Transaction Details
    // -----------------------
    getTransaction: async function (txid) {
        const url = `${API_URL}/api/v2/transaction/${txid}`;
        return await this._get(url);
    },

    // -----------------------
    // 7. Block Details
    // -----------------------
    getBlock: async function (blockHeight) {
        const url = `${API_URL}/api/v2/block/${blockHeight}`;
        return await this._get(url);
    },

    // -----------------------
    // 8. Contracts Update Index (cached)
    // -----------------------
    getContractsUpdateIndex: async function () {
        const now = Date.now();
        const cache = this._cache.contractsUpdateIndex;
        if (cache.data && now - cache.ts < 5 * 60 * 1000) {
            return cache.data;
        }
        const data = await this._get(`${API_URL}/api/v2/omnixep/contracts/updateindex`);
        cache.data = data;
        cache.ts = now;
        return data;
    },

    // -----------------------
    // 9. Contracts List (optional pid list)
    // -----------------------
    getContracts: async function (pidList = null, page = 1, count = 100) {
        let url = `${API_URL}/api/v2/omnixep/contracts?page=${page}&count=${count}`;
        if (pidList) {
            const pidParam = Array.isArray(pidList) ? pidList.join(',') : pidList;
            url += `&pid=${pidParam}`;
        }
        return await this._get(url);
    },

    // -----------------------
    // 10. Single Contract Detail
    // -----------------------
    getContract: async function (pid) {
        const url = `${API_URL}/api/v2/omnixep/contracts/${pid}`;
        return await this._get(url);
    },

    // -----------------------
    // 11. Raw Mint Token
    // -----------------------
    rawMintToken: async function (propertyId, sender, recipient, amount) {
        const payload = {
            property_id: propertyId,
            sender,
            recipient,
            amount
        };
        const res = await fetch(`${API_URL}/api/v2/omnixep/rawminttoken`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.error) {
            throw new Error(typeof json.error === 'string' ? json.error : JSON.stringify(json.error));
        }
        return json.data;
    },

    // -----------------------
    // 12. Raw Mint NFT
    // -----------------------
    rawMintNFT: async function (propertyId, sender, recipient, amount, data = '', data_type = 0) {
        const payload = {
            property_id: propertyId,
            sender,
            recipient,
            amount,
            data,
            data_type
        };
        const res = await fetch(`${API_URL}/api/v2/omnixep/rawmintnft`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.error) {
            throw new Error(typeof json.error === 'string' ? json.error : JSON.stringify(json.error));
        }
        return json.data;
    },

    // -----------------------
    // 13. UTXO State
    // -----------------------
    getUTXOState: async function (txid, vout) {
        const url = `${API_URL}/api/v2/utxos?txid=${txid}&vout=${vout}`;
        return await this._get(url);
    },

    // -----------------------
    // 14. Single NFT Details
    // -----------------------
    getNFT: async function (pid, id) {
        const url = `${API_URL}/api/v2/omnixep/nft/${pid}/${id}`;
        return await this._get(url);
    },

    // -----------------------
    // 15. Last Transactions (recent data)
    // -----------------------
    getLastTransactions: async function (count = 5) {
        const url = `${API_URL}/api/v2/lasttransactions?count=${count}`;
        return await this._get(url);
    },

    // -----------------------
    // 16. Last Blocks (recent data)
    // -----------------------
    getLastBlocks: async function (count = 5) {
        const url = `${API_URL}/api/v2/lastblocks?count=${count}`;
        return await this._get(url);
    },

    // QR Code Generate
    generateQR: async function (canvasElement, text) {
        try {
            await QRCode.toCanvas(canvasElement, text, { width: 150, margin: 1 });
            return true;
        } catch (err) {
            console.error("QR Generate Error:", err);
            throw err;
        }
    },

    // Clear stuck pending UTXOs (for debugging/recovery)
    clearPendingUTXOs: function () {
        return clearAllPendingUTXOs();
    }
};