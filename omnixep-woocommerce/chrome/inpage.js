// OmniXEP Wallet - Inpage Provider Script
// This script is injected into web pages to provide the window.omnixep API

(function () {
    'use strict';

    // Prevent multiple injections
    if (window.omnixep) return;

    // Generate unique request IDs
    let requestId = 0;
    const pendingRequests = new Map();

    // Event emitter for wallet events
    const eventListeners = new Map();

    // Provider object that will be exposed to web pages
    const omnixepProvider = {
        isOmniXEP: true,
        version: '1.0.0',

        // Check if wallet is connected
        isConnected: false,
        connectedAddress: null,

        /**
         * Request connection to the wallet
         * @returns {Promise<boolean>} true if connected, false if rejected
         */
        connect: async function () {
            return sendRequest('connect', {});
        },

        /**
         * Disconnect from the wallet
         * @returns {Promise<boolean>} true if disconnected
         */
        disconnect: async function () {
            const result = await sendRequest('disconnect', {});
            this.isConnected = false;
            this.connectedAddress = null;
            return result;
        },

        /**
         * Get the connected wallet address
         * @returns {Promise<string|null>} wallet address or null if not connected
         */
        getAddress: async function () {
            if (!this.isConnected) {
                throw new Error('Wallet not connected. Call connect() first.');
            }
            return sendRequest('getAddress', {});
        },

        /**
         * Get wallet balances
         * @returns {Promise<Array>} array of balance objects
         */
        getBalances: async function () {
            if (!this.isConnected) {
                throw new Error('Wallet not connected. Call connect() first.');
            }
            return sendRequest('getBalances', {});
        },

        /**
         * Request transaction signing
         * @param {Object} transaction - Transaction object
         * @param {string} transaction.to - Recipient address
         * @param {number} transaction.amount - Amount to send
         * @param {number} transaction.propertyId - Property ID (0 for XEP, other for tokens)
         * @returns {Promise<string>} Transaction ID if successful
         */
        signTransaction: async function (transaction) {
            if (!this.isConnected) {
                throw new Error('Wallet not connected. Call connect() first.');
            }
            if (!transaction || !transaction.to || transaction.amount === undefined) {
                throw new Error('Invalid transaction object. Required: to, amount');
            }
            return sendRequest('signTransaction', {
                to: transaction.to,
                amount: transaction.amount,
                propertyId: transaction.propertyId || 0
            });
        },

        /**
         * Request message signing
         * @param {string} message - Message to sign
         * @returns {Promise<string>} Signature
         */
        signMessage: async function (message) {
            if (!this.isConnected) {
                throw new Error('Wallet not connected. Call connect() first.');
            }
            if (!message || typeof message !== 'string') {
                throw new Error('Invalid message. Must be a non-empty string.');
            }
            return sendRequest('signMessage', { message });
        },

        /**
         * Add event listener
         * @param {string} event - Event name (connect, disconnect, accountsChanged)
         * @param {Function} callback - Callback function
         */
        on: function (event, callback) {
            if (!eventListeners.has(event)) {
                eventListeners.set(event, []);
            }
            eventListeners.get(event).push(callback);
        },

        /**
         * Remove event listener
         * @param {string} event - Event name
         * @param {Function} callback - Callback function to remove
         */
        off: function (event, callback) {
            if (eventListeners.has(event)) {
                const listeners = eventListeners.get(event);
                const index = listeners.indexOf(callback);
                if (index > -1) {
                    listeners.splice(index, 1);
                }
            }
        }
    };

    /**
     * Send request to background script via content script
     */
    function sendRequest(method, params) {
        return new Promise((resolve, reject) => {
            const id = ++requestId;

            pendingRequests.set(id, { resolve, reject });

            // Send message to content script
            window.postMessage({
                type: 'OMNIXEP_REQUEST',
                id: id,
                method: method,
                params: params,
                origin: window.location.origin
            }, '*');

            // Timeout after 5 minutes (for user interaction)
            setTimeout(() => {
                if (pendingRequests.has(id)) {
                    pendingRequests.delete(id);
                    reject(new Error('Request timeout'));
                }
            }, 300000);
        });
    }

    /**
     * Emit event to listeners
     */
    function emitEvent(event, data) {
        if (eventListeners.has(event)) {
            eventListeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (e) {
                    console.error('OmniXEP event listener error:', e);
                }
            });
        }
    }

    // Listen for responses from content script
    window.addEventListener('message', function (event) {
        // Only accept messages from the same window
        if (event.source !== window) return;

        const data = event.data;

        // Handle response
        if (data.type === 'OMNIXEP_RESPONSE') {
            const pending = pendingRequests.get(data.id);
            if (pending) {
                pendingRequests.delete(data.id);

                if (data.error) {
                    pending.reject(new Error(data.error));
                } else {
                    // Update connection state
                    if (data.method === 'connect' && data.result === true) {
                        omnixepProvider.isConnected = true;
                        if (data.address) {
                            omnixepProvider.connectedAddress = data.address;
                        }
                        emitEvent('connect', { address: data.address });
                    } else if (data.method === 'disconnect') {
                        omnixepProvider.isConnected = false;
                        omnixepProvider.connectedAddress = null;
                        emitEvent('disconnect', {});
                    } else if (data.method === 'getAddress') {
                        omnixepProvider.connectedAddress = data.result;
                    }

                    pending.resolve(data.result);
                }
            }
        }

        // Handle events from wallet
        if (data.type === 'OMNIXEP_EVENT') {
            emitEvent(data.event, data.data);

            // Update internal state
            if (data.event === 'accountsChanged') {
                omnixepProvider.connectedAddress = data.data.address;
            } else if (data.event === 'disconnect') {
                omnixepProvider.isConnected = false;
                omnixepProvider.connectedAddress = null;
            }
        }
    });

    // Expose provider to window
    Object.defineProperty(window, 'omnixep', {
        value: omnixepProvider,
        writable: false,
        configurable: false
    });

    // Announce provider is ready
    window.dispatchEvent(new Event('omnixep#initialized'));

    console.log('OmniXEP Wallet Provider initialized');
})();
