// ==================== WALLET DEBUG LOGGER ====================
// Anlık log takibi için konsola ve dosyaya yazan yardımcı fonksiyon

const WalletLogger = {
    logs: [],

    log: function (category, message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = {
            timestamp,
            category,
            message,
            data
        };

        this.logs.push(logEntry);

        // Konsola yazdır
        const logMessage = `[${timestamp}] [${category}] ${message}`;
        console.log(logMessage, data || '');

        // Chrome storage'a kaydet (son 100 log)
        if (this.logs.length > 100) {
            this.logs.shift();
        }

        try {
            chrome.storage.local.set({
                wallet_logs: this.logs
            });
        } catch (e) {
            console.error('Log kaydedilemedi:', e);
        }
    },

    error: function (category, message, error) {
        this.log(category, `ERROR: ${message}`, {
            error: error.message || error,
            stack: error.stack || ''
        });
    },

    success: function (category, message, data) {
        this.log(category, `SUCCESS: ${message}`, data);
    },

    getLogs: function () {
        return this.logs;
    },

    downloadLogs: function () {
        const logText = this.logs.map(log =>
            `[${log.timestamp}] [${log.category}] ${log.message}\n${log.data ? JSON.stringify(log.data, null, 2) : ''}\n`
        ).join('\n');

        const blob = new Blob([logText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wallet_debug_${Date.now()}.log`;
        a.click();
        URL.revokeObjectURL(url);
    }
};

// Sayfa yüklendiğinde eski logları yükle
chrome.storage.local.get(['wallet_logs'], (result) => {
    if (result.wallet_logs) {
        WalletLogger.logs = result.wallet_logs;
    }
});
