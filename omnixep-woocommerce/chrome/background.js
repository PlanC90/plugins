// OmniXEP Wallet - Background Service Worker
// Handles dApp connection requests, permission management, and transaction signing

// Connected sites storage cache (optional, but we'll rely on storage mostly)
// let connectedSites = {}; // Removed to force storage usage
let pendingRequests = {};

// Helper: Get connected sites from storage
async function getConnectedSites() {
    return new Promise((resolve) => {
        chrome.storage.local.get(['connectedSites'], (data) => {
            resolve(data.connectedSites || {});
        });
    });
}

// Save connected sites to storage
function saveConnectedSites(sites) {
    chrome.storage.local.set({ connectedSites: sites });
}

// Listen for messages from content script
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.type === 'DAPP_REQUEST') {
        handleDAppRequest(message, sender)
            .then(response => sendResponse(response))
            .catch(error => sendResponse({ error: error.message }));
        return true; // Will respond asynchronously
    }

    if (message.type === 'POPUP_RESPONSE') {
        handlePopupResponse(message);
        return false;
    }

    if (message.type === 'ACCOUNT_CHANGED') {
        broadcastEvent('accountsChanged', { address: message.address });
        return false;
    }

    return false;
});

/**
 * Handle dApp requests
 */
async function handleDAppRequest(message, sender) {
    const { method, params, origin, requestId } = message;
    const tabId = sender.tab?.id;

    console.log('dApp Request:', method, 'from', origin);

    switch (method) {
        case 'connect':
            return handleConnect(origin, tabId, requestId);

        case 'disconnect':
            return handleDisconnect(origin);

        case 'getAddress':
            return handleGetAddress(origin);

        case 'getBalances':
            return handleGetBalances(origin);

        case 'signTransaction':
            return handleSignTransaction(origin, params, tabId, requestId);

        case 'signMessage':
            return handleSignMessage(origin, params, tabId, requestId);

        default:
            return { error: 'Unknown method: ' + method };
    }
}

/**
 * Handle connection request
 */
async function handleConnect(origin, tabId, requestId) {
    // Open popup for user approval EVERY TIME as per user request
    return openPopupForApproval('connect', {
        origin: origin,
        requestId: requestId,
        tabId: tabId
    });
}

/**
 * Handle disconnect request
 */
async function handleDisconnect(origin) {
    const sites = await getConnectedSites();
    if (sites[origin]) {
        delete sites[origin];
        saveConnectedSites(sites);
    }
    return { result: true };
}

/**
 * Handle get address request
 */
async function handleGetAddress(origin) {
    const sites = await getConnectedSites();
    if (!sites[origin]) {
        return { error: 'Site not connected' };
    }

    const session = await getSession();
    if (!session || !session.mnemonic) {
        return { error: 'Wallet locked' };
    }

    const address = await getActiveAddress(session);
    return { result: address };
}

/**
 * Handle get balances request
 */
async function handleGetBalances(origin) {
    const sites = await getConnectedSites();
    if (!sites[origin]) {
        return { error: 'Site not connected' };
    }

    const session = await getSession();
    if (!session || !session.mnemonic) {
        return { error: 'Wallet locked' };
    }

    try {
        const address = await getActiveAddress(session);
        const response = await fetch(`https://api.omnixep.com/api/v2/address/${address}/balances`);
        const data = await response.json();
        return { result: data.data?.balances || [] };
    } catch (e) {
        return { error: 'Failed to fetch balances' };
    }
}

/**
 * Handle transaction signing request
 */
async function handleSignTransaction(origin, params, tabId, requestId) {
    const sites = await getConnectedSites();
    if (!sites[origin]) {
        return { error: 'Site not connected' };
    }

    const session = await getSession();
    if (!session || !session.mnemonic) {
        return { error: 'Wallet locked' };
    }

    // Open popup for transaction approval
    return openPopupForApproval('signTransaction', {
        origin: origin,
        params: params,
        requestId: requestId,
        tabId: tabId
    });
}

/**
 * Handle message signing request
 */
async function handleSignMessage(origin, params, tabId, requestId) {
    const sites = await getConnectedSites();
    if (!sites[origin]) {
        return { error: 'Site not connected' };
    }

    const session = await getSession();
    if (!session || !session.mnemonic) {
        return { error: 'Wallet locked' };
    }

    // Open popup for message signing approval
    return openPopupForApproval('signMessage', {
        origin: origin,
        params: params,
        requestId: requestId,
        tabId: tabId
    });
}

/**
 * Open popup for user approval
 */
function openPopupForApproval(action, data) {
    return new Promise((resolve, reject) => {
        const requestId = data.requestId || Date.now().toString();

        // Store pending request
        pendingRequests[requestId] = {
            action: action,
            data: data,
            resolve: resolve,
            reject: reject
        };

        // Store request data for popup to read
        chrome.storage.local.set({
            pendingDAppRequest: {
                action: action,
                ...data
            }
        });

        // Try chrome.action.openPopup first, fallback to window.create
        chrome.action.openPopup().catch(() => {
            // Fallback: Open popup as a window (works reliably on all browsers)
            chrome.windows.create({
                url: chrome.runtime.getURL('popup.html'),
                type: 'popup',
                width: 380,
                height: 620,
                focused: true
            }).catch(() => {
                // Last resort: create a notification
                chrome.notifications.create({
                    type: 'basic',
                    iconUrl: 'img/omnixep.png',
                    title: 'OmniXEP Wallet',
                    message: `${data.origin} is requesting ${action}. Click extension icon to approve.`,
                    priority: 2
                });
            });
        });

        // Timeout after 5 minutes
        setTimeout(() => {
            if (pendingRequests[requestId]) {
                delete pendingRequests[requestId];
                resolve({ error: 'Request timeout - user did not respond' });
            }
        }, 300000);
    });
}

/**
 * Handle response from popup
 */
async function handlePopupResponse(message) {
    const { requestId, approved, result, error } = message;
    const pending = pendingRequests[requestId];

    if (!pending) {
        console.log('No pending request for ID:', requestId);
        return;
    }

    delete pendingRequests[requestId];

    if (approved && pending.action === 'connect') {
        // Add to connected sites
        const sites = await getConnectedSites();
        sites[pending.data.origin] = {
            connectedAt: Date.now(),
            permissions: ['read', 'sign']
        };
        saveConnectedSites(sites);
    }

    if (error) {
        pending.resolve({ error: error });
    } else {
        pending.resolve({ result: result, address: message.address });
    }
}

/**
 * Get current session from storage
 */
async function getSession() {
    return new Promise((resolve) => {
        chrome.storage.session.get(['session'], (data) => {
            resolve(data.session || null);
        });
    });
}

/**
 * Get active wallet address
 */
async function getActiveAddress(session) {
    return new Promise((resolve) => {
        chrome.storage.local.get(['accounts', 'activeAccountIndex'], (data) => {
            const accounts = data.accounts || [];
            // Parse active index safely as a number
            const activeIndex = (data.activeAccountIndex !== undefined && data.activeAccountIndex !== null)
                ? Number(data.activeAccountIndex)
                : 0;

            console.log('getActiveAddress - activeIndex:', activeIndex, 'accounts count:', accounts.length);

            // Search for the account by index
            let activeAccount = accounts.find(a => Number(a.index) === activeIndex);

            // Fallback strategy
            if (!activeAccount && accounts.length > 0) {
                activeAccount = accounts[0];
                console.log('getActiveAddress - Fallback to first account');
            }

            if (activeAccount) {
                resolve(activeAccount.address);
            } else {
                console.warn('getActiveAddress - No account found');
                resolve(null);
            }
        });
    });
}

// Handle extension icon click
chrome.action.onClicked.addListener((tab) => {
    chrome.action.openPopup();
});

// Clean up old connected sites (optional - remove sites older than 30 days)
async function cleanupOldConnections() {
    const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
    let changed = false;
    const sites = await getConnectedSites();

    for (const origin in sites) {
        if (sites[origin].connectedAt < thirtyDaysAgo) {
            delete sites[origin];
            changed = true;
        }
    }

    if (changed) {
        saveConnectedSites(sites);
    }
}

// Run cleanup on startup
cleanupOldConnections();

// Listen for storage changes to notify dApps when active account changes
chrome.storage.onChanged.addListener(async (changes, area) => {
    if (area === 'local' && (changes.activeAccountIndex || changes.accounts)) {
        const session = await getSession();
        if (session && session.mnemonic) {
            const address = await getActiveAddress(session);
            broadcastEvent('accountsChanged', { address: address });
        }
    }
});

/**
 * Broadcast event to all connected tabs
 */
async function broadcastEvent(event, data) {
    const sites = await getConnectedSites();
    const tabs = await chrome.tabs.query({});

    for (const tab of tabs) {
        try {
            const origin = new URL(tab.url).origin;
            if (sites[origin]) {
                chrome.tabs.sendMessage(tab.id, {
                    type: 'WALLET_EVENT',
                    event: event,
                    data: data
                }).catch(() => {
                    // Tab might not have content script injected yet or is special page
                });
            }
        } catch (e) {
            // Invalid URL or other tab error
        }
    }
}

console.log('OmniXEP Background Service Worker initialized');
