// OmniXEP Wallet - Content Script
// Bridges communication between web page (inpage.js) and background script

(function () {
    'use strict';

    // Inject the inpage script into the web page
    function injectScript() {
        try {
            const script = document.createElement('script');
            script.src = chrome.runtime.getURL('inpage.js');
            script.onload = function () {
                this.remove();
            };
            (document.head || document.documentElement).appendChild(script);
        } catch (e) {
            console.error('OmniXEP: Failed to inject inpage script', e);
        }
    }

    // Inject script as early as possible
    injectScript();

    // Listen for messages from the inpage script
    window.addEventListener('message', async function (event) {
        // Security: Only accept messages from the same window and origin
        if (event.source !== window || event.origin !== window.location.origin) return;

        const data = event.data;

        // Handle requests from inpage script
        if (data.type === 'OMNIXEP_REQUEST') {
            try {
                // Forward request to background script
                const response = await chrome.runtime.sendMessage({
                    type: 'DAPP_REQUEST',
                    method: data.method,
                    params: data.params,
                    origin: data.origin,
                    requestId: data.id
                });

                // Send response back to inpage script
                window.postMessage({
                    type: 'OMNIXEP_RESPONSE',
                    id: data.id,
                    method: data.method,
                    result: response.result,
                    error: response.error,
                    address: response.address
                }, '*');
            } catch (e) {
                // Send error response
                window.postMessage({
                    type: 'OMNIXEP_RESPONSE',
                    id: data.id,
                    method: data.method,
                    error: e.message || 'Unknown error'
                }, '*');
            }
        }
    });

    // Listen for events from background script
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        if (message.type === 'WALLET_EVENT') {
            // Forward event to inpage script
            window.postMessage({
                type: 'OMNIXEP_EVENT',
                event: message.event,
                data: message.data
            }, '*');
        }
        return false;
    });

    console.log('OmniXEP Content Script loaded');
})();
