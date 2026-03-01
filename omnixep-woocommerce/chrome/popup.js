let sessionMnemonic = null;
let currentAccountIndex = 0;
let accountsData = [];
let currentBalances = [];
let autoRefreshInterval = null;
const AUTO_REFRESH_MS = 3000; // 3 saniye otomatik yenileme (hızlı güncelleme)
let lastKnownTxCount = 0; // Son bilinen işlem sayısı
let unseenTxCount = 0; // Görülmemiş yeni işlem sayısı
let sessionTimeoutHandle = null;    // Jeno
const SESSION_DURATION_MS = 3600000; // Jeno (1h)
const tokenInfoCache = new Map(); // Cache for token info to avoid repeated API calls

// Global Image Error Handler (CSP Compliant)
document.addEventListener('DOMContentLoaded', () => {
    // Image error fallback
    document.body.addEventListener('error', function (e) {
        if (e.target.tagName === 'IMG') {
            const currentSrc = e.target.src;
            if (currentSrc && !currentSrc.includes('img/generic.png') && !currentSrc.includes('img/omnixep.png')) {
                e.target.src = 'img/generic.png';
            }
            e.target.onerror = null;
        }
    }, true);

    init();
});

const el = (id) => document.getElementById(id);

function init() {
    initShaderBackground(); // Start shader
    loadLanguage(); // Load saved language or default

    // Jeno proposal:
    if (chrome.storage && chrome.storage.session) {
        chrome.storage.session.get('session', (sess) => {
            const s = sess && sess.session ? sess.session : null;

            chrome.storage.local.get(['encryptedMnemonic', 'accounts', 'activeAccountIndex'], (data) => {
                accountsData = data.accounts || [];
                currentAccountIndex = Number(data.activeAccountIndex || 0);

                if (s && s.mnemonic && s.expiry > Date.now()) {
                    // Valid session: restart a session in memory
                    startSession(s.mnemonic, s.expiry);
                    loadWalletMain();
                    return;
                }

                // Session expired or does not exist: cleanup
                if (chrome.storage && chrome.storage.session) {
                    chrome.storage.session.remove('session');
                }

                if (data.encryptedMnemonic) {
                    showView('viewLogin');
                } else {
                    showView('viewSetup');
                }
            });
        });
    } else {
        // Fallback: no chrome.storage.session -> “strong security” behavior but without popup survival
        chrome.storage.local.get(['encryptedMnemonic', 'accounts', 'activeAccountIndex'], (data) => {
            accountsData = data.accounts || [];
            currentAccountIndex = Number(data.activeAccountIndex || 0);

            if (data.encryptedMnemonic) {
                showView('viewLogin');
            } else {
                showView('viewSetup');
            }
        });
    }

    /*
        chrome.storage.local.get(['encryptedMnemonic', 'accounts', 'sessionData'], (data) => {
            // Oturum kontrolü (1 saat)
            if (data.sessionData && data.sessionData.mnemonic && data.sessionData.expiry > Date.now()) {
                sessionMnemonic = data.sessionData.mnemonic;
                accountsData = data.accounts || [];
                loadWalletMain();
                return; // Otomatik giriş başarılı
            }
    
            if (data.encryptedMnemonic) {
                showView('viewLogin');
                accountsData = data.accounts || [];
            } else {
                showView('viewSetup');
            }
        });
    */
    // end Jeno proposal

    // PASSWORD ENTER KEY (login)
    if (el('loginPassword')) {
        el('loginPassword').addEventListener('keyup', (event) => {
            if (event.key === 'Enter') {
                if (el('btnLogin')) el('btnLogin').click();
            }
        });
    }

    // Jeno proposal:
    if (el('btnLogin')) {
        el('btnLogin').addEventListener('click', () => {
            const pass = document.getElementById('loginPassword').value;
            chrome.storage.local.get(['encryptedMnemonic', 'accounts'], (data) => {
                try {
                    const decryptedMnemonic = WalletCore.decrypt(data.encryptedMnemonic, pass);
                    accountsData = data.accounts || [];

                    // Starts a session in memory (and in chrome.storage.session)
                    startSession(decryptedMnemonic);

                    loadWalletMain();
                } catch (e) {
                    alert(t('password_incorrect'));
                }
            });
        });
    }

    /*
        // LOGIN
        if (el('btnLogin')) {
            el('btnLogin').addEventListener('click', () => {
                const pass = document.getElementById('loginPassword').value;
                chrome.storage.local.get(['encryptedMnemonic', 'accounts'], (data) => {
                    try {
                        sessionMnemonic = WalletCore.decrypt(data.encryptedMnemonic, pass);
                        accountsData = data.accounts;
    
                        // 1 Saatlik Oturum Kaydet
                        const expiry = Date.now() + 3600000; // 1 saat
                        chrome.storage.local.set({
                            sessionData: { mnemonic: sessionMnemonic, expiry: expiry }
                        });
    
                        loadWalletMain();
                    } catch (e) {
                        alert(t('error_tx') + " " + e.message);
                    }
                });
            });
        }
    */
    // end Jeno proposal

    // YENİ CÜZDAN
    if (el('btnNewWallet')) {
        el('btnNewWallet').addEventListener('click', () => {
            document.getElementById('step1').style.display = 'none';
            document.getElementById('stepCreated').style.display = 'block';

            const mnemonic = WalletCore.generateMnemonic();
            sessionMnemonic = mnemonic;

            // Render mnemonic in chip style (same as Secret Recovery Phrase modal)
            const container = document.getElementById('mnemonicDisplay');
            if (container) {
                const words = mnemonic.split(' ');
                container.innerHTML = '';
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(2, 1fr)';
                container.style.gap = '8px';
                container.style.width = '100%';
                container.style.margin = '6px 0 12px 0';
                container.style.boxSizing = 'border-box';

                words.forEach((word, index) => {
                    const chip = document.createElement('div');
                    chip.style.background = '#1f1f1f';
                    chip.style.padding = '4px 8px';
                    chip.style.borderRadius = '8px';
                    chip.style.display = 'flex';
                    chip.style.alignItems = 'center';
                    chip.style.gap = '4px';
                    chip.style.fontSize = '11px';
                    chip.style.border = '1px solid #3a3a3a';

                    const num = document.createElement('span');
                    num.innerText = (index + 1) + '.';
                    num.style.color = '#00ffaa';
                    num.style.marginRight = '6px';
                    num.style.fontSize = '11px';
                    num.style.userSelect = 'none';

                    const txt = document.createElement('span');
                    txt.innerText = word;
                    txt.style.fontWeight = '600';
                    txt.style.color = '#ffffff';

                    chip.appendChild(num);
                    chip.appendChild(txt);
                    container.appendChild(chip);
                });

                // Store full mnemonic for copy button
                container.dataset.fullMnemonic = mnemonic;
            }
        });
    }

    // COPY mnemonic on setup screen
    const btnCopySetupMnemonic = document.getElementById('btnCopySetupMnemonic');
    if (btnCopySetupMnemonic) {
        btnCopySetupMnemonic.addEventListener('click', function () {
            const container = document.getElementById('mnemonicDisplay');
            const mnemonic = container ? container.dataset.fullMnemonic : null;

            if (mnemonic) {
                navigator.clipboard.writeText(mnemonic).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<span>✓</span> Copied';
                    this.style.background = '#00ffaa';
                    this.style.color = '#000';
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.background = '';
                        this.style.color = '';
                    }, 1500);
                });
            }
        });
    }
    document.getElementById('btnSaveNew').addEventListener('click', () => {
        const pass = document.getElementById('newWalletPassword').value;
        if (!pass) return alert(t('password_empty'));
        completeSetup(sessionMnemonic, pass);
    });

    // Go Back from created step to first step
    const btnBackFromCreated = document.getElementById('btnBackFromCreated');
    if (btnBackFromCreated) {
        btnBackFromCreated.addEventListener('click', () => {
            const step1 = document.getElementById('step1');
            const stepCreated = document.getElementById('stepCreated');
            const mnemonicDisplay = document.getElementById('mnemonicDisplay');

            if (step1 && stepCreated) {
                stepCreated.style.display = 'none';
                step1.style.display = 'block';
            }
            if (mnemonicDisplay) {
                mnemonicDisplay.innerHTML = '';
                delete mnemonicDisplay.dataset.fullMnemonic;
            }
            sessionMnemonic = null;
        });
    }

    // IMPORT
    document.getElementById('btnImportWallet').addEventListener('click', () => {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('stepImport').style.display = 'block';
    });

    document.getElementById('btnConfirmImport').addEventListener('click', () => {
        const words = document.getElementById('importWords').value.trim();
        const pass = document.getElementById('setupPassword').value;
        if (!WalletCore.validateMnemonic(words)) return alert(t('mnemonic_invalid'));
        if (!pass) return alert(t('password_empty'));
        completeSetup(words, pass);
    });

    // GERİ DÖN BUTONU (Import ekranından)
    if (document.getElementById('btnCancelImport')) {
        document.getElementById('btnCancelImport').addEventListener('click', () => {
            document.getElementById('stepImport').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
        });
    }

    // LOGIN SCREEN - Create New Wallet Button
    const btnNewWalletLogin = document.getElementById('btnNewWalletLogin');
    if (btnNewWalletLogin) {
        btnNewWalletLogin.addEventListener('click', () => {
            showView('viewSetup');
            document.getElementById('step1').style.display = 'none';
            document.getElementById('stepCreated').style.display = 'block';

            const mnemonic = WalletCore.generateMnemonic();
            sessionMnemonic = mnemonic;

            // Render mnemonic in chip style
            const container = document.getElementById('mnemonicDisplay');
            if (container) {
                const words = mnemonic.split(' ');
                container.innerHTML = '';
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(2, 1fr)';
                container.style.gap = '8px';
                container.style.width = '100%';
                container.style.margin = '6px 0 12px 0';
                container.style.boxSizing = 'border-box';

                words.forEach((word, index) => {
                    const chip = document.createElement('div');
                    chip.style.background = '#1f1f1f';
                    chip.style.padding = '4px 8px';
                    chip.style.borderRadius = '8px';
                    chip.style.display = 'flex';
                    chip.style.alignItems = 'center';
                    chip.style.gap = '4px';
                    chip.style.fontSize = '11px';
                    chip.style.border = '1px solid #3a3a3a';

                    const num = document.createElement('span');
                    num.innerText = (index + 1) + '.';
                    num.style.color = '#00ffaa';
                    num.style.marginRight = '6px';
                    num.style.fontSize = '11px';
                    num.style.userSelect = 'none';

                    const txt = document.createElement('span');
                    txt.innerText = word;
                    txt.style.fontWeight = '600';
                    txt.style.color = '#ffffff';

                    chip.appendChild(num);
                    chip.appendChild(txt);
                    container.appendChild(chip);
                });

                container.dataset.fullMnemonic = mnemonic;
            }
        });
    }

    // LOGIN SCREEN - Import Wallet Button
    const btnImportWalletLogin = document.getElementById('btnImportWalletLogin');
    if (btnImportWalletLogin) {
        btnImportWalletLogin.addEventListener('click', () => {
            showView('viewSetup');
            document.getElementById('step1').style.display = 'none';
            document.getElementById('stepImport').style.display = 'block';
        });
    }

    // SEKMELER (MetaMask Style)
    const tabAssets = document.getElementById('tabAssets');
    const tabActivity = document.getElementById('tabActivity');

    if (tabAssets) {
        tabAssets.addEventListener('click', () => {
            document.getElementById('sectionAssets').style.display = 'block';
            document.getElementById('sectionActivity').style.display = 'none';
            document.getElementById('sectionNFTs').style.display = 'none';
            document.getElementById('sectionSend').style.display = 'none';

            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
            tabAssets.classList.add('active');
        });
    }

    if (tabActivity) {
        tabActivity.addEventListener('click', async () => {
            document.getElementById('sectionAssets').style.display = 'none';
            document.getElementById('sectionActivity').style.display = 'block';
            document.getElementById('sectionSend').style.display = 'none';
            document.getElementById('sectionNFTs').style.display = 'none';

            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
            tabActivity.classList.add('active');

            // Clear notification badge when viewing activity
            clearActivityBadge();

            // Load activity
            await loadActivityList();
        });
    }

    // NFT Tab Handler
    const tabNFTs = document.getElementById('tabNFTs');
    if (tabNFTs) {
        tabNFTs.addEventListener('click', async () => {
            document.getElementById('sectionAssets').style.display = 'none';
            document.getElementById('sectionActivity').style.display = 'none';
            document.getElementById('sectionNFTs').style.display = 'block';
            document.getElementById('sectionSend').style.display = 'none';

            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
            tabNFTs.classList.add('active');

            // Load NFTs
            await loadNFTList();
        });
    }

    // ACTION BUTTONS (MetaMask Style)
    const btnActionSend = document.getElementById('btnActionSend');
    if (btnActionSend) {
        btnActionSend.addEventListener('click', () => {
            // Hide main elements, show send
            document.getElementById('sectionAssets').style.display = 'none';
            document.getElementById('sectionSend').style.display = 'block';

            // Hide other sections
            const balanceSection = document.querySelector('.mm-balance-section');
            const actionRow = document.querySelector('.mm-action-row');
            const tabRow = document.querySelector('.mm-tab-row');
            const topbar = document.querySelector('.mm-topbar');

            if (balanceSection) balanceSection.style.display = 'none';
            if (actionRow) actionRow.style.display = 'none';
            if (tabRow) tabRow.style.display = 'none';
            if (topbar) topbar.style.display = 'none';
        });
    }

    // REFRESH BUTTON
    const btnRefresh = document.getElementById('btnRefresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            // Add spin animation class
            const icon = btnRefresh.querySelector('.mm-action-icon');
            if (icon) {
                icon.style.transition = 'transform 0.5s ease-in-out';
                icon.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    icon.style.transform = 'rotate(0deg)';
                }, 500);
            }
            // Clear pending UTXOs on refresh to fix stuck transaction issues
            if (typeof WalletCore !== 'undefined' && WalletCore.clearPendingUTXOs) {
                WalletCore.clearPendingUTXOs();
            }
            refreshUI();
        });
    }



    // BACK BUTTON (from Send view)
    const btnBackToMain = document.getElementById('btnBackToMain');
    if (btnBackToMain) {
        btnBackToMain.addEventListener('click', () => {
            // Hide Send view
            document.getElementById('sectionSend').style.display = 'none';
            document.getElementById('sectionAssets').style.display = 'block';

            // Show main elements
            const balanceSection = document.querySelector('.mm-balance-section');
            const actionRow = document.querySelector('.mm-action-row');
            const tabRow = document.querySelector('.mm-tab-row');
            const topbar = document.querySelector('.mm-topbar');

            if (balanceSection) balanceSection.style.display = 'block';
            if (actionRow) actionRow.style.display = 'flex';
            if (tabRow) tabRow.style.display = 'flex';
            if (topbar) topbar.style.display = 'flex';

            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tabAssets').classList.add('active');
        });
    }

    // HESAP EKLEME & DEĞİŞTİRME




    // TRANSFER action (btnShowQr was legacy name? or used in logic)
    // Actually btnActionReceive opens QR. Check lines 1894 in HTML -> id="btnActionReceive"
    // Let's bind that one too if it exists.

    const openQrModal = () => {
        const modal = document.getElementById('modalQr');
        const canvas = document.getElementById('qrCanvas');
        const addrDisplay = document.getElementById('qrAddressDisplay');
        const currentAcc = accountsData.find(a => a.index === currentAccountIndex);

        if (currentAcc && canvas) {
            if (addrDisplay) addrDisplay.innerText = currentAcc.address;

            WalletCore.generateQR(canvas, currentAcc.address).then(success => {
                if (success) {
                    modal.style.display = 'flex';
                } else {
                    alert(t('qr_create_error'));
                }
            }).catch(e => {
                alert(t('error_tx') + " " + e.message);
                console.error(e);
            });
        }
    };

    if (document.getElementById('btnShowQr')) {
        document.getElementById('btnShowQr').addEventListener('click', openQrModal);
    }

    // Main Action Button "Receive"
    const btnActionReceive = document.getElementById('btnActionReceive');
    if (btnActionReceive) {
        btnActionReceive.addEventListener('click', openQrModal);
    }

    // New Copy Address Button inside QR Modal
    const btnCopyAddress = document.getElementById('btnCopyAddress');
    if (btnCopyAddress) {
        btnCopyAddress.addEventListener('click', function () {
            const currentAcc = accountsData.find(a => a.index === currentAccountIndex);
            if (currentAcc && currentAcc.address) {
                navigator.clipboard.writeText(currentAcc.address).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '✓ ' + t('copied');
                    this.style.background = '#00ffaa';
                    this.style.color = '#000';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.background = ''; // Reset to class default
                        this.style.color = '';
                    }, 1500);
                });
            }
        });
    }

    // QR KAPAT
    if (document.getElementById('btnCloseQr')) {
        document.getElementById('btnCloseQr').addEventListener('click', () => {
            document.getElementById('modalQr').style.display = 'none';
        });
    }

    // ====== ÜST BAR BUTONLARI (YENİ) ======

    // KOPYALA BUTONU
    const btnTopCopy = document.getElementById('btnTopCopy');
    if (btnTopCopy) {
        btnTopCopy.addEventListener('click', function () {
            console.log('Kopyala butonuna tıklandı');
            const currentAcc = accountsData.find(a => a.index === currentAccountIndex);
            if (currentAcc && currentAcc.address) {
                navigator.clipboard.writeText(currentAcc.address).then(() => {
                    const original = this.innerText;
                    this.innerText = '✓';
                    this.style.background = '#00ffaa';
                    setTimeout(() => {
                        this.innerText = original;
                        this.style.background = '#2a2a2a';
                    }, 1500);
                }).catch(err => {
                    alert(t('error_tx') + ": " + err.message);
                });
            }
        });
    }

    // QR BUTONU
    const btnTopQr = document.getElementById('btnTopQr');
    if (btnTopQr) {
        btnTopQr.addEventListener('click', function () {
            console.log('QR butonuna tıklandı');
            const modal = document.getElementById('modalQr');
            const canvas = document.getElementById('qrCanvas');
            const addrDisplay = document.getElementById('qrAddressDisplay'); // FIX: Elementi seç
            const currentAcc = accountsData.find(a => a.index === currentAccountIndex);

            if (currentAcc && canvas && modal) {
                if (addrDisplay) addrDisplay.innerText = currentAcc.address; // FIX: Adresi yazdır

                WalletCore.generateQR(canvas, currentAcc.address).then(success => {
                    if (success) {
                        modal.style.display = 'flex';
                    } else {
                        alert(t('qr_create_error'));
                    }
                }).catch(e => {
                    alert(t('error_tx') + " " + e.message);
                });
            }
        });
    }

    // ====== HAMBURGER MENÜ ======
    const btnHamburger = document.getElementById('btnHamburger');
    const hamburgerMenu = document.getElementById('hamburgerMenu');

    if (btnHamburger && hamburgerMenu) {
        // Toggle menu on click
        btnHamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = hamburgerMenu.style.display === 'block';
            hamburgerMenu.style.display = isVisible ? 'none' : 'block';
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!hamburgerMenu.contains(e.target) && e.target !== btnHamburger) {
                hamburgerMenu.style.display = 'none';
            }
        });
    }

    // MENU: Lock Wallet
    const menuLockWallet = document.getElementById('menuLockWallet');
    if (menuLockWallet) {
        menuLockWallet.addEventListener('click', () => {
            hamburgerMenu.style.display = 'none';
            // Jeno proposal:
            chrome.storage.local.remove('sessionData', () => {
                lockWallet();
            });

            /*
            chrome.storage.local.remove('sessionData', () => {
                sessionMnemonic = null;
                showView('viewLogin');
            });
            */
            // end Jeno proposal
        });
    }

    // MENU: Show Mnemonic
    const menuShowMnemonic = document.getElementById('menuShowMnemonic');
    const modalMnemonic = document.getElementById('modalMnemonic');

    if (menuShowMnemonic && modalMnemonic) {
        menuShowMnemonic.addEventListener('click', () => {
            hamburgerMenu.style.display = 'none';
            document.getElementById('mnemonicStep1').style.display = 'block';
            document.getElementById('mnemonicStep2').style.display = 'none';
            document.getElementById('mnemonicPassword').value = '';
            modalMnemonic.style.display = 'flex';
        });
    }

    // Verify Password and Show Mnemonic
    const btnVerifyMnemonic = document.getElementById('btnVerifyMnemonic');
    const mnemonicPasswordInput = document.getElementById('mnemonicPassword');
    if (btnVerifyMnemonic && mnemonicPasswordInput) {
        // Enter key handler - trigger SHOW button when Enter is pressed
        mnemonicPasswordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnVerifyMnemonic.click();
            }
        });

        // Click handler
        btnVerifyMnemonic.addEventListener('click', () => {
            const password = mnemonicPasswordInput.value;

            if (!password) {
                alert(t('password_empty') || 'Please enter your password');
                return;
            }

            chrome.storage.local.get('encryptedMnemonic', (result) => {
                try {
                    if (!result.encryptedMnemonic) {
                        console.error('No encryptedMnemonic found in storage');
                        alert('No encrypted wallet found. Please reset and recreate your wallet.');
                        return;
                    }
                    console.log('Attempting to decrypt mnemonic...');
                    const decrypted = WalletCore.decrypt(result.encryptedMnemonic, password);

                    if (!decrypted) {
                        console.error('Decryption returned empty result');
                        alert(t('password_incorrect') || 'Incorrect password!');
                        return;
                    }
                    // document.getElementById('mnemonicWordsDisplay').innerText = decrypted;

                    // MODERN GÖSTERİM (CHIPS)
                    const words = decrypted.split(' ');
                    const container = document.getElementById('mnemonicWordsDisplay');
                    container.innerHTML = '';
                    container.style.display = 'grid';
                    container.style.gridTemplateColumns = 'repeat(3, 1fr)';
                    container.style.gap = '8px';
                    container.style.width = '100%';
                    container.style.boxSizing = 'border-box';
                    // container.style.padding = '10px'; // Removed for alignment

                    words.forEach((word, index) => {
                        const chip = document.createElement('div');
                        chip.style.background = '#2a2a2a';
                        chip.style.padding = '6px 10px';
                        chip.style.borderRadius = '8px';
                        chip.style.fontSize = '13px';
                        chip.style.color = '#fff';
                        chip.style.display = 'flex';
                        chip.style.alignItems = 'center';
                        chip.style.border = '1px solid #333';

                        const num = document.createElement('span');
                        num.innerText = (index + 1) + '.';
                        num.style.color = '#666';
                        num.style.marginRight = '6px';
                        num.style.fontSize = '11px';
                        num.style.userSelect = 'none';

                        const txt = document.createElement('span');
                        txt.innerText = word;
                        txt.style.fontWeight = '500';

                        chip.appendChild(num);
                        chip.appendChild(txt);
                        container.appendChild(chip);
                    });

                    // Store for copy button
                    container.dataset.fullMnemonic = decrypted;
                    document.getElementById('mnemonicStep1').style.display = 'none';
                    document.getElementById('mnemonicStep2').style.display = 'block';
                } catch (e) {
                    alert(t('password_incorrect'));
                }
            });
        });
    }

    // Close Mnemonic Modal
    const btnCloseMnemonic = document.getElementById('btnCloseMnemonic');
    const btnCloseMnemonic2 = document.getElementById('btnCloseMnemonic2');
    const btnCopyMnemonic = document.getElementById('btnCopyMnemonic');

    if (btnCloseMnemonic) {
        btnCloseMnemonic.addEventListener('click', () => {
            modalMnemonic.style.display = 'none';
        });
    }

    if (btnCloseMnemonic2) {
        btnCloseMnemonic2.addEventListener('click', () => {
            document.getElementById('mnemonicWordsDisplay').innerText = '';
            modalMnemonic.style.display = 'none';
        });
    }

    if (btnCopyMnemonic) {
        btnCopyMnemonic.addEventListener('click', function () {
            const container = document.getElementById('mnemonicWordsDisplay');
            const mnemonic = container.dataset.fullMnemonic;

            if (mnemonic) {
                navigator.clipboard.writeText(mnemonic).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<span>✓</span> ' + t('copied');
                    this.style.background = '#00ffaa';
                    this.style.color = '#000';
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.background = ''; // reset
                        this.style.color = '';
                    }, 1500);
                });
            }
        });
    }

    // MENU: Language Selection
    const menuLanguage = document.getElementById('menuLanguage');
    const modalLanguage = document.getElementById('modalLanguage');

    if (menuLanguage && modalLanguage) {
        menuLanguage.addEventListener('click', () => {
            hamburgerMenu.style.display = 'none';
            modalLanguage.style.display = 'flex';
        });
    }

    // MENU: Connected Sites
    const menuConnectedSites = document.getElementById('menuConnectedSites');
    const modalConnectedSites = document.getElementById('modalConnectedSites');
    if (menuConnectedSites && modalConnectedSites) {
        menuConnectedSites.addEventListener('click', () => {
            hamburgerMenu.style.display = 'none';
            modalConnectedSites.style.display = 'flex';
            loadConnectedSites();
        });
    }

    const btnCloseLanguage = document.getElementById('btnCloseLanguage');
    if (btnCloseLanguage) {
        btnCloseLanguage.addEventListener('click', () => {
            modalLanguage.style.display = 'none';
        });
    }

    // Language item selection
    document.querySelectorAll('.mm-lang-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.mm-lang-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            // Language change logic can be implemented here
            const lang = item.dataset.lang;
            console.log('Selected language:', lang);
            // For now, just close the modal
            modalLanguage.style.display = 'none';
        });
    });

    // NFT Detail Modal
    const btnCloseNFTDetail = document.getElementById('btnCloseNFTDetail');
    if (btnCloseNFTDetail) {
        btnCloseNFTDetail.addEventListener('click', () => {
            document.getElementById('modalNFTDetail').style.display = 'none';
        });
    }

    // NFT Modal X button
    const btnNFTClose = document.getElementById('btnNFTClose');
    if (btnNFTClose) {
        btnNFTClose.addEventListener('click', () => {
            document.getElementById('modalNFTDetail').style.display = 'none';
        });
    }

    const btnNFTExplorer = document.getElementById('btnNFTExplorer');
    if (btnNFTExplorer) {
        btnNFTExplorer.addEventListener('click', () => {
            if (currentNFTPid !== null) {
                let explorerUrl;
                if (currentNFTIndex !== null) {
                    explorerUrl = `https://electraprotocol.network/omnixep/nft/${currentNFTPid}/${currentNFTIndex}`;
                } else {
                    explorerUrl = `https://electraprotocol.network/omnixep/contract/${currentNFTPid}`;
                }
                chrome.tabs.create({ url: explorerUrl });
            }
            document.getElementById('modalNFTDetail').style.display = 'none';
        });
    }

    // ŞİFREMİ UNUTTUM
    const btnForgotPassword = document.getElementById('btnForgotPassword');
    if (btnForgotPassword) {
        btnForgotPassword.addEventListener('click', () => {
            document.getElementById('modalReset').style.display = 'flex';
        });
    }

    const btnCancelReset = document.getElementById('btnCancelReset');
    if (btnCancelReset) {
        btnCancelReset.addEventListener('click', () => {
            document.getElementById('modalReset').style.display = 'none';
        });
    }

    const btnConfirmReset = document.getElementById('btnConfirmReset');
    if (btnConfirmReset) {
        btnConfirmReset.addEventListener('click', () => {
            chrome.storage.local.clear(() => {
                location.reload();
            });
        });
    }

    initSendScreen();
}

// Jeno proposal:
function completeSetup(mnemonic, password) {
    const encrypted = WalletCore.encrypt(mnemonic, password);
    const wallet = WalletCore.getAccountByIndex(mnemonic, 0);
    const initialAcc = [{ index: 0, name: "Account 1", address: wallet.address }];

    chrome.storage.local.set({ encryptedMnemonic: encrypted, accounts: initialAcc }, () => {
        accountsData = initialAcc;
        // New session in memory (and storage.session)
        startSession(mnemonic);
        loadWalletMain();
    });
}

/*
function completeSetup(mnemonic, password) {
    const encrypted = WalletCore.encrypt(mnemonic, password);
    const wallet = WalletCore.getAccountByIndex(mnemonic, 0);
    const initialAcc = [{ index: 0, name: "Ana Hesap", address: wallet.address }];

    chrome.storage.local.set({ encryptedMnemonic: encrypted, accounts: initialAcc }, () => {
        sessionMnemonic = mnemonic;
        accountsData = initialAcc;
        loadWalletMain();
    });
}
*/
// end Jeno proposal

function showView(id) {
    document.querySelectorAll('.view').forEach(e => e.style.display = 'none');
    document.getElementById(id).style.display = 'block';
}

function initSendScreen() {
    // Open Send Screen
    const btnActionSend = document.getElementById('btnActionSend');
    if (btnActionSend) {
        // Remove existing listeners by cloning (if needed) or just add new one
        // Assuming simple add is fine or it overrides functionality
        btnActionSend.addEventListener('click', () => {
            document.getElementById('sectionAssets').style.display = 'none';
            document.getElementById('sectionActivity').style.display = 'none';
            document.getElementById('sectionNFTs').style.display = 'none';
            document.getElementById('sectionSend').style.display = 'block';

            // Reset tabs
            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));

            loadSendTokens();
        });
    }

    // Toggle Send Screen visibility helper
    const btnBackToMain = document.getElementById('btnBackToMain');
    if (btnBackToMain) {
        btnBackToMain.addEventListener('click', () => {
            document.getElementById('sectionSend').style.display = 'none';
            document.getElementById('sectionAssets').style.display = 'block';
            document.getElementById('tabAssets').classList.add('active');
        });
    }

    // Gas Fee Constants (in XEP) - Increased for better reliability
    const XEP_FEE = 0.002;       // Native XEP transaction fee
    const TOKEN_FEE = 0.002;     // Token transaction fee (API handles this)

    // Update gas fee display based on selected token
    function updateGasFeeDisplay() {
        const gasFeeAmount = document.getElementById('gasFeeAmount');
        if (!gasFeeAmount) return;

        const selectToken = document.getElementById('selectToken');
        if (!selectToken) return;

        const pid = parseInt(selectToken.value);

        if (pid === 0) {
            // XEP - show simple fee
            gasFeeAmount.textContent = `~${XEP_FEE} XEP`;
        } else {
            // Token - show fee required
            gasFeeAmount.textContent = `~${TOKEN_FEE} XEP`;
        }
    }

    // Token Select Change
    const selectToken = document.getElementById('selectToken');
    if (selectToken) {
        selectToken.addEventListener('change', () => {
            updateAvailableBalance();
            updateGasFeeDisplay();
        });
    }

    // Max Button
    const btnMax = document.getElementById('btnMax');
    if (btnMax) {
        btnMax.addEventListener('click', () => {
            const opt = selectToken.options[selectToken.selectedIndex];
            if (opt) {
                let val = parseFloat(opt.dataset.balance);
                const pid = parseInt(opt.value);

                // Native XEP Fee Calculation - deduct fee from max amount
                if (pid === 0) {
                    val = val - XEP_FEE; // Subtract transaction fee
                    if (val < 0) val = 0;
                    // Ensure precision
                    val = parseFloat(val.toFixed(8));
                }
                // For tokens, use full balance (fee is paid separately in XEP)

                if (!isNaN(val)) document.getElementById('inputAmount').value = val;
            }
        });
    }

    // Initialize gas fee display
    updateGasFeeDisplay();

    // Send Logic
    const btnSend = document.getElementById('btnSendTransaction');
    if (btnSend) {
        btnSend.onclick = async () => {
            // Jeno proposal:
            if (!sessionMnemonic) {
                alert("Session closed. Please log in again.");
                lockWallet();
                return;
            }
            // end Jeno proposal
            const recipient = document.getElementById('inputTo').value;
            const amount = document.getElementById('inputAmount').value;
            const pid = document.getElementById('selectToken').value;

            if (!recipient || !amount) {
                alert(t('alert_fill_all_fields'));
                return;
            }

            // Validate address format first
            if (!WalletCore.validateAddress(recipient)) {
                alert(t('invalid_address_format'));
                return;
            }

            const status = document.getElementById('txStatus');
            status.innerText = t('verifying_address');
            status.style.color = "#ffff00";

            // Validate address online
            const isValidAddress = await WalletCore.validateAddressOnline(recipient);
            if (!isValidAddress) {
                status.innerText = t('error_invalid_recipient');
                status.style.color = "#ff4757";
                return;
            }

            status.innerText = t('processing');
            status.style.color = "#ffff00";

            try {
                const opt = document.getElementById('selectToken').options[document.getElementById('selectToken').selectedIndex];
                const rawAmount = parseFloat(amount);

                // Check if token uses decimals (8 decimals for XEP and divisible tokens)
                // API returns decimals as boolean, but dataset stores as string
                const hasDecimals = parseInt(pid) === 0 || opt?.dataset?.decimals === "true";

                console.log("SEND DEBUG - pid:", pid, "rawAmount:", rawAmount, "hasDecimals:", hasDecimals, "dataset.decimals:", opt?.dataset?.decimals);

                let res;

                if (parseInt(pid) === 0) {
                    // Native XEP -> Build transaction manually (API doesn't support pid=0)
                    const amountSatoshi = Math.round(rawAmount * 100000000);
                    console.log("SENDING XEP:", rawAmount, "XEP ->", amountSatoshi, "satoshi");
                    res = await WalletCore.sendNativeTransaction(sessionMnemonic, currentAccountIndex, recipient, amountSatoshi);
                } else {
                    // OmniXEP Token -> Check decimals field
                    let sendAmount;
                    if (hasDecimals) {
                        // Divisible token: convert to satoshi (8 decimals)
                        sendAmount = Math.round(rawAmount * 100000000);
                        console.log("SENDING DIVISIBLE TOKEN:", pid, rawAmount, "->", sendAmount, "satoshi");
                    } else {
                        // Non-divisible token: use raw amount
                        sendAmount = Math.round(rawAmount);
                        console.log("SENDING NON-DIVISIBLE TOKEN:", pid, "amount:", sendAmount);
                    }
                    res = await WalletCore.sendTransaction(sessionMnemonic, currentAccountIndex, recipient, sendAmount, parseInt(pid));
                }

                // Res can be an object {txid: ...} OR a string (txid)
                const txid = (typeof res === 'object' && res.txid) ? res.txid : (typeof res === 'string' ? res : null);

                // TXID geldiyse, API üzerinden gerçekten ağa kaydolmuş mı kontrol et
                // Mempool'a düşmesi biraz zaman alabilir, 3 deneme yapalım
                let txOk = false;
                if (txid) {
                    for (let attempt = 0; attempt < 3 && !txOk; attempt++) {
                        if (attempt > 0) await new Promise(r => setTimeout(r, 2000)); // 2 sn bekle
                        try {
                            const resCheck = await fetch(`https://api.omnixep.com/api/v2/transaction/${txid}`);
                            const jsonCheck = await resCheck.json();
                            txOk = !!(jsonCheck && jsonCheck.data && !jsonCheck.error);
                            console.log(`TX verify attempt ${attempt + 1}:`, txOk, jsonCheck);
                        } catch (e) {
                            console.error(`TX verify attempt ${attempt + 1} error:`, e);
                        }
                    }
                }

                if (txid && txOk) {
                    if (txid === "ALREADY_CONFIRMED") {
                        status.innerText = t('transaction_successful');
                    } else {
                        const shortId = txid.substring(0, 10);
                        const explorerUrl = `https://electraprotocol.network/transaction/${txid}`;
                        status.innerHTML = `${t('transaction_successful')} (<a href="${explorerUrl}" target="_blank" style="color:#00ff00;text-decoration:underline;">TXID: ${shortId}...</a>)`;
                    }
                    status.style.color = "#00ff00";

                    // OPTIMISTIC UPDATE: Bakiyeyi anında düş
                    // Bu, API'den yeni bakiye gelene kadar kullanıcıya anında feedback verir
                    const sentPid = parseInt(pid);
                    const sentAmount = parseFloat(amount);
                    const fee = sentPid === 0 ? 0.002 : 0; // XEP için fee düş

                    // currentBalances'ı güncelle
                    const balIndex = currentBalances.findIndex(b => b.propertyid === sentPid);
                    if (balIndex !== -1) {
                        currentBalances[balIndex].value = Math.max(0, currentBalances[balIndex].value - sentAmount - fee);
                    }

                    // XEP token gönderiminde de XEP bakiyesini düş (fee için)
                    if (sentPid !== 0) {
                        const xepIndex = currentBalances.findIndex(b => b.propertyid === 0);
                        if (xepIndex !== -1) {
                            currentBalances[xepIndex].value = Math.max(0, currentBalances[xepIndex].value - 0.002);
                        }
                    }

                    // Send ekranındaki available balance'ı güncelle
                    loadSendTokens();
                    updateAvailableBalance();

                    // Update lastTxId so our own tx doesn't trigger badge
                    const acc = accountsData.find(a => a.index === currentAccountIndex);
                    if (acc && txid && txid !== "ALREADY_CONFIRMED") {
                        sessionStorage.setItem('lastTxId_' + acc.address, txid);
                    }

                    // UI'ı yenile (arka planda gerçek veriyi çekecek)
                    refreshUI();
                    loadActivityList();

                    // 2 saniye sonra tekrar yenile ve ekranı kapat
                    setTimeout(() => {
                        refreshUI();
                        loadActivityList();

                        document.getElementById('inputAmount').value = '';
                        document.getElementById('inputTo').value = '';
                        document.getElementById('sectionSend').style.display = 'none';
                        document.getElementById('sectionAssets').style.display = 'block';
                    }, 2000);

                    // 5 ve 10 saniye sonra da yenile (işlem ağda yayılması için)
                    setTimeout(() => { refreshUI(); loadActivityList(); }, 5000);
                    setTimeout(() => { refreshUI(); loadActivityList(); }, 10000);
                } else {
                    status.innerText = "Error: " + ((res && res.error) || "Transaction failed or could not be recorded on network");
                    status.style.color = "#ff4757";
                }
            } catch (e) {
                status.innerText = "Error: " + e.message;
                status.style.color = "#ff4757";
            }
        };
    }
}

function loadSendTokens() {
    const select = document.getElementById('selectToken');
    if (!select) return;
    select.innerHTML = '';

    if (currentBalances.length === 0) {
        const opt = document.createElement('option');
        opt.innerText = t('loading');
        select.appendChild(opt);
        // Retry shortly?
        return;
    }

    // Sort logic (XEP first, MMX second) similar to refreshUI
    currentBalances.sort((a, b) => {
        const pidA = parseInt(a.propertyid || 0);
        const pidB = parseInt(b.propertyid || 0);

        const getPrio = (p) => {
            if (p === 0) return 0; // XEP first
            if (p === 199 || p === 228 || p === 278) return 1; // MMX/MEMEX second
            return 2; // Others
        };

        const prioA = getPrio(pidA);
        const prioB = getPrio(pidB);

        if (prioA !== prioB) return prioA - prioB;
        return pidA - pidB;
    });

    currentBalances.forEach(b => {
        if (b.value <= 0) return; // Hide zero balance? User might want to see them but can't send.

        const opt = document.createElement('option');
        opt.value = b.propertyid;
        opt.dataset.balance = b.value;
        opt.dataset.decimals = b.decimals;
        const name = b.name || (b.propertyid === 0 ? 'XEP' : (b.propertyid === 199 ? 'MEMEX' : `Token #${b.propertyid}`));
        opt.dataset.symbol = name;
        opt.innerText = `${name} (${b.value})`;
        select.appendChild(opt);
    });

    updateAvailableBalance();
}

function updateAvailableBalance() {
    const select = document.getElementById('selectToken');
    const lbl = document.getElementById('lblAvailable');
    if (select && lbl) {
        if (select.selectedIndex === -1 && select.options.length > 0) select.selectedIndex = 0;
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.balance) {
            const name = opt.dataset.symbol || '';
            lbl.innerText = `${opt.dataset.balance} ${name}`;
        } else {
            lbl.innerText = '-';
        }
    }
}

function loadWalletMain() {
    showView('viewMain');
    loadAccountSelector();
    setupAccountsUI();
    setupNFTUI();
    initSendScreen(); // Initialize Send screen functionality
    loadLanguage(); // Initialize Language
    refreshUI();

    // Start auto-refresh for faster balance updates
    startAutoRefresh();

    // Check for pending dApp requests (e.g., signMessage modal)
    setTimeout(checkPendingDAppRequests, 300);
}

function startAutoRefresh() {
    // Clear existing interval if any
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }

    // Auto-refresh every 10 seconds - silent update (no UI flicker)
    autoRefreshInterval = setInterval(async () => {
        await silentRefresh();
    }, AUTO_REFRESH_MS);
}

// Silent refresh - updates values without flickering, checks for new transactions
async function silentRefresh() {
    const acc = accountsData.find(a => a.index === currentAccountIndex);
    if (!acc) return;

    try {
        // Update balances silently (no UI flicker)
        await updateBalanceSilent(acc.address);

        // Check for new transactions
        const transactions = await WalletCore.getTransactions(acc.address, 5);
        if (transactions && transactions.length > 0) {
            const latestTxId = transactions[0].txid;
            const currentTxCount = transactions.length;

            // If we have new transactions
            if (lastKnownTxCount > 0 && currentTxCount > 0) {
                // Check if the latest tx is different (new tx arrived)
                const storedLatestTx = sessionStorage.getItem('lastTxId_' + acc.address);
                if (storedLatestTx && storedLatestTx !== latestTxId) {
                    unseenTxCount++;
                    updateActivityBadge();
                }
                sessionStorage.setItem('lastTxId_' + acc.address, latestTxId);
            } else {
                // First load - just store the latest
                sessionStorage.setItem('lastTxId_' + acc.address, latestTxId);
                lastKnownTxCount = currentTxCount;
            }
        }
    } catch (e) {
        console.error("Silent refresh error:", e);
    }
}

// Update all balances without UI flicker
async function updateBalanceSilent(address) {
    try {
        // Fetch new balances from API
        const [bals, utxos] = await Promise.all([
            WalletCore.getBalances(address).catch(e => []),
            WalletCore.getUTXOs(address).catch(e => [])
        ]);

        if (!bals || bals.length === 0) return;

        // Calculate XEP from UTXOs
        let xepFromUtxo = 0;
        if (Array.isArray(utxos)) {
            xepFromUtxo = utxos.reduce((sum, u) => sum + (u.value || u.satoshis || 0), 0) / 100000000;
        }

        // Normalize and update currentBalances
        const newBalances = bals.map(b => {
            const pid = b.propertyid !== undefined ? b.propertyid : (b.property_id !== undefined ? b.property_id : b.id);

            let amt = 0;
            if (b.balance !== undefined && b.balance > 0) {
                if (b.decimals === true || b.decimals === "true") {
                    amt = parseFloat(b.balance) / 100000000;
                } else {
                    amt = parseFloat(b.balance);
                }
            } else if (b.value !== undefined) {
                amt = parseFloat(b.value);
            } else if (b.total !== undefined) {
                amt = parseFloat(b.total) / 100000000;
            } else if (b.amount !== undefined) {
                amt = parseFloat(b.amount);
            }

            // XEP uses UTXO value
            if (parseInt(pid) === 0 && xepFromUtxo > 0) {
                amt = xepFromUtxo;
            }

            return {
                propertyid: parseInt(pid),
                name: b.name || (pid === 0 ? 'XEP' : (pid === 199 ? 'MEMEX' : `Token #${pid}`)),
                value: amt,
                decimals: b.decimals
            };
        });

        // Update each balance in the UI without full refresh
        newBalances.forEach(newBal => {
            // Find existing balance
            const existingIdx = currentBalances.findIndex(b => b.propertyid === newBal.propertyid);

            if (existingIdx !== -1) {
                const oldValue = currentBalances[existingIdx].value;
                currentBalances[existingIdx].value = newBal.value;

                // Update UI element if value changed
                if (Math.abs(oldValue - newBal.value) > 0.00000001) {
                    updateBalanceInUI(newBal.propertyid, newBal.value, newBal.name);
                }
            } else {
                // New token - add to list
                currentBalances.push(newBal);
            }
        });

        // Update total USD display
        updateTotalUsdDisplay();

    } catch (e) {
        console.error("Balance update error:", e);
    }
}

// Update a single balance in the UI without flicker
function updateBalanceInUI(propertyId, newValue, name) {
    // Find the balance row by property ID
    const balanceItems = document.querySelectorAll('.mm-token-row');

    balanceItems.forEach(item => {
        const pidAttr = item.getAttribute('data-pid');
        if (pidAttr && parseInt(pidAttr) === propertyId) {
            const valueEl = item.querySelector('.token-balance-value');
            if (valueEl) {
                const displayValue = newValue.toFixed(propertyId === 0 ? 8 : 2);
                if (valueEl.textContent !== displayValue) {
                    valueEl.textContent = displayValue;

                    // Flash animation for change
                    valueEl.style.transition = 'color 0.3s, transform 0.2s';
                    valueEl.style.color = '#00ff88';
                    valueEl.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        valueEl.style.color = '';
                        valueEl.style.transform = '';
                    }, 500);
                }
            }
        }
    });

    // Also update XEP amount in header if XEP
    if (propertyId === 0) {
        const xepDisplay = document.getElementById('xepAmountDisplay');
        if (xepDisplay) {
            const formattedXep = newValue.toFixed(4) + ' XEP';
            if (xepDisplay.textContent !== formattedXep) {
                xepDisplay.textContent = formattedXep;
            }
        }
    }
}

// Update total USD display based on currentBalances
function updateTotalUsdDisplay() {
    // This will be updated when prices are fetched
    // For now, just ensure the display is in sync with currentBalances
}

// Update activity badge with unseen count
function updateActivityBadge() {
    const activityTab = document.querySelector('.mm-tab[data-tab="activity"]');
    if (!activityTab) return;

    // Remove existing badge
    const existingBadge = activityTab.querySelector('.activity-badge');
    if (existingBadge) existingBadge.remove();

    if (unseenTxCount > 0) {
        const badge = document.createElement('span');
        badge.className = 'activity-badge';
        badge.innerText = unseenTxCount > 9 ? '9+' : unseenTxCount;
        badge.style.cssText = `
            position: absolute;
            top: -5px;
            right: -5px;
            background: #00ff00;
            color: #000;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 14px;
            text-align: center;
        `;
        activityTab.style.position = 'relative';
        activityTab.appendChild(badge);
    }
}

// Clear badge when activity tab is clicked
function clearActivityBadge() {
    unseenTxCount = 0;
    const badge = document.querySelector('.activity-badge');
    if (badge) badge.remove();
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

async function loadActivityList() {
    const acc = accountsData.find(a => a.index === currentAccountIndex);
    if (!acc) return;

    const list = document.getElementById('activityList');
    list.innerHTML = '<div class="mm-loading">' + t('loading') + '</div>';

    try {
        const transactions = await WalletCore.getTransactions(acc.address, 20);

        if (!transactions || transactions.length === 0) {
            list.innerHTML = '<div class="mm-no-activity">' + t('no_activity') + '</div>';
            return;
        }

        list.innerHTML = '';

        for (const tx of transactions) {
            const isSend = tx.sender === acc.address ||
                (Array.isArray(tx.sender) && tx.sender.includes(acc.address));

            // Determine amount and token name
            let amount = 0;
            let tokenName = 'XEP';

            if (tx.layer === 'OMNIXEP') {
                // For tokens with decimals, divide by 10^8
                if (tx.decimals === true) {
                    amount = (tx.amount_pid || 0) / 100000000;
                } else {
                    amount = tx.amount_pid || 0;
                }
                tokenName = tx.pid === 0 ? 'XEP' : (tx.pid === 199 ? 'MEMEX' : `#${tx.pid}`);
            } else {
                // Native XEP transaction - get amount from recipient object
                // recipient is an object like {"addr1": satoshi1, "addr2": satoshi2}
                if (isSend) {
                    // For sent transactions, find the recipient that is NOT our address
                    if (typeof tx.recipient === 'object' && tx.recipient !== null) {
                        for (const [addr, satoshi] of Object.entries(tx.recipient)) {
                            if (addr !== acc.address) {
                                amount = satoshi / 100000000;
                                break;
                            }
                        }
                    } else {
                        amount = (tx.amount_xep || 0) / 100000000;
                    }
                } else {
                    // For received transactions, find our address in recipients
                    if (typeof tx.recipient === 'object' && tx.recipient !== null) {
                        amount = (tx.recipient[acc.address] || 0) / 100000000;
                    } else {
                        amount = (tx.amount_xep || 0) / 100000000;
                    }
                }
                tokenName = 'XEP';
            }

            // Format address - for sends, show recipient that is NOT our address (skip change address)
            let otherAddr = '';
            if (isSend) {
                if (typeof tx.recipient === 'object' && tx.recipient !== null) {
                    for (const addr of Object.keys(tx.recipient)) {
                        if (addr !== acc.address) {
                            otherAddr = addr;
                            break;
                        }
                    }
                } else if (typeof tx.recipient === 'string') {
                    otherAddr = tx.recipient;
                }
            } else {
                // For receives, show sender
                if (Array.isArray(tx.sender)) {
                    otherAddr = tx.sender[0] || '';
                } else {
                    otherAddr = tx.sender || '';
                }
            }
            const shortAddr = otherAddr ? `${otherAddr.substring(0, 8)}...${otherAddr.substring(otherAddr.length - 6)}` : '';

            // Format time
            const date = new Date(tx.timestamp * 1000);
            const timeStr = date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' }) + ' ' +
                date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });

            const el = document.createElement('div');
            el.className = 'mm-activity-item';
            el.innerHTML = `
                <div class="mm-activity-icon ${isSend ? 'send' : 'receive'}">
                    ${isSend ? '↑' : '↓'}
                </div>
                <div class="mm-activity-info">
                    <div class="mm-activity-type">${isSend ? 'Sent' : 'Received'}</div>
                    <div class="mm-activity-addr">${isSend ? 'To: ' : 'From: '}${shortAddr}</div>
                </div>
                <div class="mm-activity-amount">
                    <div class="mm-activity-value ${isSend ? 'send' : 'receive'}">
                        ${isSend ? '-' : '+'}${amount.toLocaleString()} ${tokenName}
                    </div>
                    <div class="mm-activity-time">${timeStr}</div>
                </div>
            `;

            // Click to open in explorer
            el.onclick = () => {
                const explorerUrl = `https://electraprotocol.network/transaction/${tx.txid}`;
                chrome.tabs.create({ url: explorerUrl });
            };

            list.appendChild(el);
        }
    } catch (err) {
        console.error('Activity load error:', err);
        list.innerHTML = '<div class="mm-no-activity">Error loading data</div>';
    }
}

async function loadNFTList() {
    const acc = accountsData.find(a => a.index === currentAccountIndex);
    if (!acc) return;

    const grid = document.getElementById('nftGrid');
    grid.innerHTML = '<div class="mm-loading" style="grid-column: span 2;">' + t('loading') + '</div>';

    try {
        const nftBalances = await WalletCore.getNFTBalances(acc.address);

        if (!nftBalances || nftBalances.length === 0) {
            grid.innerHTML = '<div class="mm-no-nfts">' + t('no_nfts') + '</div>';
            return;
        }

        grid.innerHTML = '';

        for (const nft of nftBalances) {
            // Get token info for collection name and icon
            let collectionName = `NFT #${nft.property_id}`;
            let collectionIcon = null;

            try {
                const tokenInfo = await WalletCore.getTokenInfo(nft.property_id);
                if (tokenInfo) {
                    if (tokenInfo.name) collectionName = tokenInfo.name;
                    // Use icon_32, fallback to icon_16
                    const iconData = tokenInfo.icon_32 || tokenInfo.icon_16;
                    if (iconData) collectionIcon = `data:image/png;base64,${iconData}`;
                }
            } catch (e) { }

            // Get owned NFT indices from transaction history
            let ownedIndices = [];
            try {
                ownedIndices = await WalletCore.getOwnedNFTIndices(acc.address, nft.property_id);
                // Deduplicate indices
                ownedIndices = [...new Set(ownedIndices)];
                ownedIndices.sort((a, b) => a - b);
                console.log('NFT PID:', nft.property_id, 'Owned indices:', ownedIndices);
            } catch (e) {
                console.error('getOwnedNFTIndices error:', e);
            }

            // If no indices found, show collection icon with balance
            if (ownedIndices.length === 0) {
                const el = document.createElement('div');
                el.className = 'mm-nft-card';
                el.innerHTML = `
                    <img src="${collectionIcon || 'img/omnixep.png'}" class="mm-nft-image" onerror="this.src='img/omnixep.png'">
                    <div class="mm-nft-info">
                        <div class="mm-nft-name">${collectionName}</div>
                        <div class="mm-nft-collection">Adet: ${nft.balance || 1}</div>
                        <div class="mm-nft-id">PID: ${nft.property_id}</div>
                    </div>
                `;
                el.onclick = () => {
                    showNFTDetail(collectionIcon || 'img/omnixep.png', collectionName, null, nft.property_id, nft.balance || 1);
                };
                grid.appendChild(el);
                continue;
            }

            // For each owned NFT, create a card
            for (const nftIndex of ownedIndices) {
                let imageUrl = collectionIcon || 'img/omnixep.png';
                let nftData = null;

                try {
                    nftData = await WalletCore.getNFTDetail(nft.property_id, nftIndex);
                    if (nftData && nftData.grant_data) {
                        // grant_type: 0=JSON, 1=URL, 2=IMAGE, 4=TEXT
                        let grantUrl = null;
                        if (nftData.grant_type === 1 || nftData.grant_type === 2) {
                            grantUrl = nftData.grant_data;
                        } else if (nftData.grant_type === 0) {
                            try {
                                const parsed = JSON.parse(nftData.grant_data);
                                grantUrl = parsed.image || parsed.imageUrl || parsed.image_url || parsed.url || parsed.animation_url;
                            } catch (e) { }
                        }

                        if (grantUrl) {
                            // IPFS handling: support ipfs:// CID, ipfs/CID, or raw CID formats
                            if (grantUrl.startsWith('ipfs://')) {
                                grantUrl = grantUrl.replace('ipfs://', 'https://ipfs.io/ipfs/');
                            } else if (grantUrl.includes('ipfs/')) {
                                // Already has ipfs/ but might be relative or another gateway
                                if (!grantUrl.startsWith('http')) {
                                    grantUrl = 'https://ipfs.io/' + (grantUrl.startsWith('/') ? grantUrl.substring(1) : grantUrl);
                                }
                            } else if (/^(Qm[1-9A-HJ-NP-Za-km-z]{44}|ba[A-Za-z2-7]{57})$/.test(grantUrl)) {
                                // Raw CID (v0 or v1)
                                grantUrl = 'https://ipfs.io/ipfs/' + grantUrl;
                            }

                            if (grantUrl && !grantUrl.startsWith('blob:') && (grantUrl.startsWith('http') || grantUrl.startsWith('data:'))) {
                                imageUrl = grantUrl;
                            }
                        }
                    }
                } catch (e) { }

                const el = document.createElement('div');
                el.className = 'mm-nft-card';
                el.innerHTML = `
                    <img src="${imageUrl}" class="mm-nft-image" onerror="this.src='img/omnixep.png'">
                    <div class="mm-nft-info">
                        <div class="mm-nft-name">${collectionName}</div>
                        <div class="mm-nft-collection">#${nftIndex}</div>
                        <div class="mm-nft-id">PID: ${nft.property_id}</div>
                    </div>
                `;

                // Click to open detail modal
                el.onclick = () => {
                    showNFTDetail(imageUrl, collectionName, nftData, nft.property_id, 1, nftIndex);
                };

                grid.appendChild(el);
            } // end for nftIndex
        } // end for nftBalances
    } catch (err) {
        console.error('NFT load error:', err);
        grid.innerHTML = '<div class="mm-no-nfts">' + t('error_loading_nfts') + '</div>';
    }
}

// Global variables for NFT explorer link
let currentNFTPid = null;
let currentNFTIndex = null;

// Jeno proposal:
function startSession(mnemonic, existingExpiry = null) {
    // If we already have an expiry (restored session), we reuse it.
    const now = Date.now();
    const expiry = existingExpiry && existingExpiry > now
        ? existingExpiry
        : now + SESSION_DURATION_MS;

    sessionMnemonic = mnemonic;

    if (sessionTimeoutHandle) {
        clearTimeout(sessionTimeoutHandle);
    }

    const remaining = expiry - now;
    sessionTimeoutHandle = setTimeout(() => {
        lockWallet();
        alert("Your session has expired. Please log in again.");
    }, remaining);

    // IMPORTANT: session stored in extension memory, not on disk
    if (chrome.storage && chrome.storage.session) {
        chrome.storage.session.set({
            session: {
                mnemonic: mnemonic,
                expiry: expiry
            }
        });
    }
}

function lockWallet() {
    sessionMnemonic = null;

    if (sessionTimeoutHandle) {
        clearTimeout(sessionTimeoutHandle);
        sessionTimeoutHandle = null;
    }

    if (chrome.storage && chrome.storage.session) {
        chrome.storage.session.remove('session');
    }

    stopAutoRefresh();
    showView('viewLogin');
}
// end Jeno proposal


function showNFTDetail(imageUrl, name, nftData, pid, balance, nftIndex) {
    const modal = document.getElementById('modalNFTDetail');

    document.getElementById('nftDetailImage').src = imageUrl;
    document.getElementById('nftDetailImage').onerror = function () { this.src = 'img/omnixep.png'; };
    document.getElementById('nftDetailName').innerText = name;
    document.getElementById('nftDetailCollection').innerText = nftIndex ? `#${nftIndex}` : `Adet: ${balance}`;

    // Description from nftData
    let desc = '';
    if (nftData) {
        if (nftData.grant_data && nftData.grant_type === 4) {
            desc = nftData.grant_data; // TEXT type
        } else if (nftData.holder_data) {
            desc = nftData.holder_data;
        }
    }
    document.getElementById('nftDetailDesc').innerText = desc || t('no_description');

    document.getElementById('nftDetailPid').innerText = `PID: ${pid}`;
    document.getElementById('nftDetailId').innerText = nftIndex ? `#${nftIndex}` : (nftData ? `#${nftData.index || 1}` : '#1');

    currentNFTPid = pid;
    currentNFTIndex = nftIndex || (nftData ? nftData.index : null);
    modal.style.display = 'flex';
}

function loadAccountSelector() {
    const s = document.getElementById('accountSelector');
    if (s) {
        s.innerHTML = "";
        accountsData.forEach(a => {
            const o = document.createElement('option');
            o.value = a.index;
            o.innerText = a.name || `${t('account')} ${a.index + 1}`;
            if (a.index === currentAccountIndex) o.selected = true;
            s.appendChild(o);
        });
    }
    updateAccountDisplay();
}

function updateAccountDisplay() {
    const currentAcc = accountsData.find(a => a.index === currentAccountIndex);
    if (currentAcc) {
        const nameEl = document.getElementById('selectedAccountName');
        const avatarEl = document.querySelector('#btnOpenAccounts .mm-account-avatar-small');
        const name = currentAcc.name || `${t('account')} ${currentAcc.index + 1}`;

        if (nameEl) nameEl.innerText = name;
        if (avatarEl) avatarEl.innerText = name.substring(0, 2).toUpperCase();
    }
}

// =========================================================
// NEW: MULTI-ACCOUNT MANAGEMENT LOGIC
// =========================================================

function setupAccountsUI() {
    // Open Accounts Modal
    // Open Accounts Modal
    const btnOpen = document.getElementById('btnOpenAccounts');
    if (btnOpen) {
        btnOpen.onclick = null; // Clear prev
        btnOpen.onclick = () => {
            document.getElementById('modalAccounts').style.display = 'flex';
            loadAccountsList();
        };
    }

    // Close Accounts Modal
    const btnClose = document.getElementById('btnCloseAccounts');
    if (btnClose) {
        btnClose.onclick = null; // Clear prev
        btnClose.onclick = () => {
            document.getElementById('modalAccounts').style.display = 'none';
        };
    }

    // Add Account Button
    const btnAdd = document.getElementById('btnAddAccount');
    if (btnAdd) {
        btnAdd.onclick = null; // Clear prev
        btnAdd.onclick = handleAddNewAccount;
    }

    // Rename Modal Listeners
    const btnCancelRename = document.getElementById('btnCancelRename');
    if (btnCancelRename) {
        btnCancelRename.onclick = null;
        btnCancelRename.onclick = () => document.getElementById('modalRename').style.display = 'none';
    }

    const btnSaveName = document.getElementById('btnSaveName');
    if (btnSaveName) {
        btnSaveName.onclick = null;
        btnSaveName.onclick = handleSaveName;
    }
}

async function loadAccountsList() {
    const list = document.getElementById('listAccounts');
    if (!list) return;
    list.innerHTML = ''; // Clear

    for (const acc of accountsData) {
        const item = document.createElement('div');
        item.className = `mm-account-item ${acc.index === currentAccountIndex ? 'active' : ''}`;

        // Click on item to switch
        item.onclick = (e) => {
            // If clicked on options, don't switch (handled by stopPropagation in options)
            if (e.target.closest('.mm-account-options')) return;
            switchAccount(acc.index);
        };

        const name = acc.name || `${t('account')} ${acc.index + 1}`;
        // Balance logic...
        const bal = (acc.index === currentAccountIndex) ? (document.getElementById('totalBalanceDisplay') ? document.getElementById('totalBalanceDisplay').innerText : '...') : '...';

        // Avatar
        const avatar = document.createElement('div');
        avatar.className = 'mm-account-avatar';
        avatar.innerText = name.substring(0, 2).toUpperCase();

        // Info
        const info = document.createElement('div');
        info.className = 'mm-account-info';
        info.innerHTML = `
            <div class="mm-account-name">${name}</div>
            <div class="mm-account-balance">${acc.address.substring(0, 6)}...${acc.address.substring(acc.address.length - 4)}</div>
        `;

        // Options Button
        const optionsBtn = document.createElement('div');
        optionsBtn.className = 'mm-account-options';
        optionsBtn.innerText = '⋮';
        optionsBtn.title = 'Seçenekler';

        // Wrapper for relative positioning
        // optionsBtn already is relative in CSS

        // Dropdown Menu
        const menu = document.createElement('div');
        menu.id = `accMenu-${acc.index}`;
        menu.className = 'mm-account-menu-dropdown';
        menu.style.display = 'none';

        // Rename Option
        const renameOpt = document.createElement('div');
        renameOpt.innerText = '✏️ ' + t('rename');
        renameOpt.onclick = (e) => {
            e.stopPropagation(); // prevent item click
            renameAccountRequest(acc.index, name);
        };

        // Delete Option
        const deleteOpt = document.createElement('div');
        deleteOpt.innerText = '🗑️ ' + t('delete');
        deleteOpt.style.color = '#ff4444';
        deleteOpt.onclick = (e) => {
            e.stopPropagation(); // prevent item click
            deleteAccountRequest(acc.index);
        };

        menu.appendChild(renameOpt);
        menu.appendChild(deleteOpt);
        optionsBtn.appendChild(menu);

        // Toggle Menu
        optionsBtn.onclick = (e) => {
            e.stopPropagation();
            console.log('Toggle menu for', acc.index);
            // Hide all others
            document.querySelectorAll('.mm-account-menu-dropdown').forEach(m => {
                if (m !== menu) m.style.display = 'none';
            });
            // Toggle current
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        };

        item.appendChild(avatar);
        item.appendChild(info);
        item.appendChild(optionsBtn);
        list.appendChild(item);
    }
}

// Global click to close menus
document.onclick = function (e) {
    if (!e.target.closest('.mm-account-options')) {
        document.querySelectorAll('.mm-account-menu-dropdown').forEach(m => m.style.display = 'none');
    }
};

// Global click moved inside setup or init usually, but okay here.
// toggleAccountMenu function is no longer needed in window scope but kept for compat if referenced elsewhere
window.toggleAccountMenu = function (e, index, name) { /* Legacy/Unused now */ };

window.deleteAccountRequest = function (index) {
    // 1. Prevent deleting only account
    if (accountsData.length <= 1) return alert(t('alert_last_account'));
    // 2. Prevent deleting active account
    if (index === currentAccountIndex) return alert(t('alert_delete_active'));

    const confirmText = t('delete_account_confirm');

    if (confirm(confirmText)) {
        accountsData = accountsData.filter(a => a.index !== index);
        chrome.storage.local.set({ accounts: accountsData }, () => {
            loadAccountsList();
            loadAccountSelector(); // Sync dropdown
        });
    }
};

async function handleAddNewAccount() {
    if (!sessionMnemonic) return alert(t('alert_session_closed'));

    // Find next index
    // We should safely find max index
    let maxIndex = -1;
    accountsData.forEach(a => { if (a.index > maxIndex) maxIndex = a.index; });
    const newIndex = maxIndex + 1;

    const newWallet = WalletCore.getAccountByIndex(sessionMnemonic, newIndex);
    const baseName = t('account');
    const newName = `${baseName} ${newIndex + 1}`;

    accountsData.push({
        index: newIndex,
        name: newName,
        address: newWallet.address
    });

    // Save
    chrome.storage.local.set({ accounts: accountsData }, () => {
        loadAccountsList(); // Refresh list
        switchAccount(newIndex); // Switch to new
    });
}

function switchAccount(index) {
    const newIndex = Number(index);
    currentAccountIndex = newIndex;
    chrome.storage.local.set({ activeAccountIndex: newIndex }, () => {
        // Sync with background immediately
        const acc = accountsData.find(a => Number(a.index) === newIndex);
        if (acc) {
            console.log('Account switched to:', acc.address);
            // Broadcast event to all tabs via background
            chrome.runtime.sendMessage({
                type: 'ACCOUNT_CHANGED',
                address: acc.address
            });
        }
    });

    updateAccountDisplay();

    // Close modal
    document.getElementById('modalAccounts').style.display = 'none';

    // Refresh Main UI
    refreshUI();
}

let renamingAccountIndex = -1;
window.renameAccountRequest = function (index, currentName) {
    renamingAccountIndex = index;
    const modal = document.getElementById('modalRename');
    const input = document.getElementById('inputNewName');
    input.value = currentName;
    modal.style.display = 'flex';
};

function handleSaveName() {
    const input = document.getElementById('inputNewName');
    const newName = input.value.trim();
    if (!newName) return alert(t('alert_name_required'));

    if (renamingAccountIndex !== -1) {
        const acc = accountsData.find(a => a.index === renamingAccountIndex);
        if (acc) {
            acc.name = newName;
            chrome.storage.local.set({ accounts: accountsData }, () => {
                document.getElementById('modalRename').style.display = 'none';
                loadAccountsList(); // Refresh list inside modal
                updateAccountDisplay(); // Refresh top bar if active

                // Also update dropdown if it exists
                loadAccountSelector();
            });
        }
    }
}

function refreshUI() {
    const acc = accountsData.find(a => a.index === currentAccountIndex);

    // Safety check - if no account found, log error and return
    if (!acc) {
        console.error("refreshUI: No account found for index", currentAccountIndex, "accountsData:", accountsData);
        return;
    }

    // Safe check for addressDisplay (may not exist in current UI)
    const addrDisplay = document.getElementById('addressDisplay');
    if (addrDisplay) addrDisplay.innerText = acc.address;

    // Ağ Bilgisi - arka planda yükle
    WalletCore.getNetworkStats().then(s => {
        if (s) {
            const lblBlock = document.getElementById('lblBlock');
            const lblNodes = document.getElementById('lblNodes');
            const lblContracts = document.getElementById('lblContracts');
            if (lblBlock) lblBlock.innerText = s.last_block;
            if (lblNodes) lblNodes.innerText = s.nodes;
            if (lblContracts) lblContracts.innerText = s.contracts;
        }
    }).catch(e => console.error("Network stats error:", e));

    // Bakiyeler
    const list = document.getElementById('balanceList');
    if (!list) {
        console.error("refreshUI: balanceList element not found");
        return;
    }
    list.innerHTML = "<div style='text-align:center; padding:20px; color:#666;'>" + t('loading') + "</div>";

    // HIZLI BAKİYE YÜKLEMESİ: Önce bakiyeleri göster, fiyatları sonra yükle
    // Fiyat değişkenleri - başlangıçta 0, arka planda güncellenecek
    let cachedXepPrice = 0;
    let cachedXepChange = 0;
    let cachedMemexPrice = 0;
    let cachedMemexChange = 0;

    // Bakiyeleri hızlıca al ve göster
    Promise.all([
        WalletCore.getBalances(acc.address).catch(e => { console.error("Balance fetch error", e); return []; }),
        WalletCore.getUTXOs(acc.address).catch(e => { console.error("UTXO fetch error", e); return []; })
    ]).then(async ([bals, utxos]) => {
        list.innerHTML = "";

        try {
            // Toplam XEP bakiyesi (harcanabilir) UTXO'lardan hesaplanır
            let xepFromUtxo = 0;
            try {
                if (Array.isArray(utxos)) {
                    xepFromUtxo = utxos.reduce((sum, u) => sum + (u.value || u.satoshis || 0), 0) / 100000000;
                }
            } catch (e) {
                console.error('UTXO normalize error:', e);
            }

            // Normalize Data
            const normalizedBals = bals.map(b => {
                const pid = b.propertyid !== undefined ? b.propertyid : (b.property_id !== undefined ? b.property_id : b.id);

                let amt = 0;
                // API returns 'balance' field (in satoshi for XEP, direct for tokens)
                if (b.balance !== undefined && b.balance > 0) {
                    // Check if it has decimals flag (XEP uses satoshi)
                    if (b.decimals === true || b.decimals === "true") {
                        amt = parseFloat(b.balance) / 100000000;
                    } else {
                        amt = parseFloat(b.balance);
                    }
                } else if (b.value !== undefined) {
                    amt = parseFloat(b.value);
                } else if (b.total !== undefined) {
                    amt = parseFloat(b.total) / 100000000;
                } else if (b.amount !== undefined) {
                    amt = parseFloat(b.amount);
                }

                // XEP (pid 0) için UI'de gösterilen değeri UTXO toplamı ile eşitle
                if (parseInt(pid) === 0 && xepFromUtxo > 0) {
                    amt = xepFromUtxo;
                }

                return { propertyid: parseInt(pid), value: amt, decimals: b.decimals };
            });

            // Sort balances so that XEP is always first and MMX (MEMEX) always second
            normalizedBals.sort((a, b) => {
                const pidA = parseInt(a.propertyid || 0);
                const pidB = parseInt(b.propertyid || 0);

                const getPrio = (p) => {
                    if (p === 0) return 0; // XEP first
                    if (p === 199 || p === 228 || p === 278) return 1; // MMX/MEMEX second
                    return 2; // Others
                };

                const prioA = getPrio(pidA);
                const prioB = getPrio(pidB);

                if (prioA !== prioB) return prioA - prioB;
                return pidA - pidB;
            });

            // Deduplicate: merge tokens with same propertyid
            const deduplicatedBals = [];
            const seenPids = new Map();
            for (const b of normalizedBals) {
                if (seenPids.has(b.propertyid)) {
                    // Add balance to existing entry
                    const existing = seenPids.get(b.propertyid);
                    existing.value += b.value;
                } else {
                    const entry = { ...b };
                    deduplicatedBals.push(entry);
                    seenPids.set(b.propertyid, entry);
                }
            }

            currentBalances = deduplicatedBals;

            // RENDER LIST IMMEDIATELY (without prices first)
            if (deduplicatedBals.length === 0) { list.innerHTML = "<div style='color:#666;text-align:center;'>Bakiye Yok</div>"; return; }

            // Pre-fetch all token info in parallel (much faster than sequential)
            const tokenPids = deduplicatedBals
                .filter(b => b.propertyid !== 0 && b.propertyid !== 199)
                .map(b => b.propertyid);

            // Fetch token info for tokens not in cache
            const tokensToFetch = tokenPids.filter(pid => !tokenInfoCache.has(pid));
            if (tokensToFetch.length > 0) {
                const tokenInfoPromises = tokensToFetch.map(pid =>
                    WalletCore.getTokenInfo(pid).then(info => ({ pid, info })).catch(() => ({ pid, info: null }))
                );
                const results = await Promise.all(tokenInfoPromises);
                results.forEach(({ pid, info }) => {
                    tokenInfoCache.set(pid, info);
                });
            }

            // İlk render - fiyatlar henüz yüklenmedi, sadece bakiye miktarlarını göster
            renderBalanceList(deduplicatedBals, cachedXepPrice, cachedXepChange, cachedMemexPrice, cachedMemexChange);

            // Show XEP Amount immediately
            const xep = deduplicatedBals.find(b => b.propertyid === 0);
            const xepVal = xep ? xep.value : 0;
            const xepAmountDisplay = document.getElementById('xepAmountDisplay');
            if (xepAmountDisplay) {
                xepAmountDisplay.innerText = xepVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + " XEP";
            }

            // Set initial hero display to loading state
            const heroDisplay = document.getElementById('totalBalanceDisplay');
            if (heroDisplay) {
                heroDisplay.innerText = t('loading') + '...';
            }

            // ARKA PLANDA FİYATLARI YÜKLE VE UI'Yİ GÜNCELLE
            Promise.all([
                WalletCore.getXepPrice().catch(e => { console.error("Price fetch error", e); return null; }),
                WalletCore.getMemexPrice().catch(e => { console.error("Memex fetch error", e); return null; })
            ]).then(([priceData, memexPriceData]) => {
                cachedXepPrice = priceData ? priceData.usd : 0;
                cachedXepChange = priceData ? priceData.usd_24h_change : 0;
                cachedMemexPrice = memexPriceData ? memexPriceData.usd : 0;
                cachedMemexChange = memexPriceData ? memexPriceData.usd_24h_change : 0;

                // CALCULATE TOTAL PORTFOLIO VALUE & WEIGHTED CHANGE
                let totalCurrentUSD = 0;
                let totalPreviousUSD = 0;

                for (const b of deduplicatedBals) {
                    let tokenPrice = 0;
                    let tokenChange = 0;

                    if (b.propertyid === 0) { // XEP
                        tokenPrice = cachedXepPrice;
                        tokenChange = cachedXepChange;
                    } else if (b.propertyid === 199) { // MEMEX
                        // Skip price for MEMEX as requested
                        tokenPrice = 0;
                        tokenChange = 0;
                    }

                    const val = b.value;
                    if (tokenPrice > 0) {
                        const currentUSD = val * tokenPrice;
                        totalCurrentUSD += currentUSD;
                        const prevUSD = currentUSD / (1 + (tokenChange / 100));
                        totalPreviousUSD += prevUSD;
                    }
                }

                // Total Change %
                let totalChangePercent = 0;
                if (totalPreviousUSD > 0) {
                    totalChangePercent = ((totalCurrentUSD - totalPreviousUSD) / totalPreviousUSD) * 100;
                }

                // Update Hero Display (TOTAL VALUE)
                if (heroDisplay) {
                    heroDisplay.innerText = `$${totalCurrentUSD.toLocaleString('en-US', { minimumFractionDigits: 5, maximumFractionDigits: 5 })}`;

                    // Update Change Display
                    const changeDisplay = document.getElementById('balanceChange');
                    if (changeDisplay) {
                        if (totalPreviousUSD > 0 || totalCurrentUSD > 0) {
                            const sign = totalChangePercent >= 0 ? '+' : '';
                            changeDisplay.innerText = `${sign}${totalChangePercent.toFixed(2)}%`;
                            changeDisplay.className = `mm-change ${totalChangePercent >= 0 ? 'positive' : 'negative'}`;
                        } else {
                            changeDisplay.innerText = '';
                        }
                    }
                }

                // Re-render list with prices
                renderBalanceList(deduplicatedBals, cachedXepPrice, cachedXepChange, cachedMemexPrice, cachedMemexChange);
            });

        } catch (err) {
            console.error(err);
            list.innerHTML = `<div style="color:red; text-align:center; font-size:12px;">Error: ${err.message}</div>`;
        }
    });
}

// Bakiye listesini render eden yardımcı fonksiyon
function renderBalanceList(deduplicatedBals, price, change, memexPrice, memexChange) {
    const list = document.getElementById('balanceList');
    list.innerHTML = "";

    for (const b of deduplicatedBals) {
        const isXep = b.propertyid === 0;
        const isMemex = b.propertyid === 199;

        let name = isXep ? "XEP" : `Token #${b.propertyid}`;
        // Default icons
        let icon = isXep ? "img/xep_logo.png" : "img/omnixep.png";
        let isVerified = isXep; // XEP is always verified
        let isNftContract = false;

        if (isMemex) {
            name = "MEMEX";
            icon = "img/memex.png";
            isVerified = true; // MEMEX is verified
        } else if (!isXep) {
            // Use cached token info
            const info = tokenInfoCache.get(b.propertyid);
            if (info) {
                // Prefer ticker/symbol over full name for cleaner display
                if (info.ticker) {
                    name = info.ticker;
                } else if (info.symbol) {
                    name = info.symbol;
                } else if (info.name) {
                    name = info.name;
                }
                if (info.icon_32) icon = `data:image/png;base64,${info.icon_32}`;
                if (info.verified === true) isVerified = true;
                if (info.is_nft === true) isNftContract = true;
            }
        }

        // Skip NFT contracts in Assets list; they are shown in NFTs tab
        if (isNftContract) {
            continue;
        }

        b.name = name; // Store name

        const displayVal = b.value.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 4 });

        // Token-specific price
        let tokenPrice = 0;
        let tokenChange = 0;
        if (isXep) { tokenPrice = price; tokenChange = change; }
        else if (isMemex) { tokenPrice = 0; tokenChange = 0; }

        // USD Value calculation
        let usdValueText = "";
        if (tokenPrice > 0) {
            const usdVal = b.value * tokenPrice;
            usdValueText = `$${usdVal.toLocaleString('en-US', { minimumFractionDigits: 5, maximumFractionDigits: 5 })}`;
        }

        const changeClass = tokenChange >= 0 ? 'positive' : 'negative';
        const changeText = (tokenPrice > 0) ? `${tokenChange >= 0 ? '+' : ''}${tokenChange.toFixed(2)}%` : '';

        const verifiedBadge = isVerified ? '<span class="verified-badge" title="Verified">✓</span>' : '';

        const el = document.createElement('div');
        el.className = 'mm-token-item mm-token-row';
        el.setAttribute('data-pid', b.propertyid);
        el.innerHTML = `
            <img src="${icon}" class="mm-token-icon" onerror="this.src='img/omnixep.png'">
            <div class="mm-token-info">
                <div class="mm-token-name">${name}${verifiedBadge}</div>
                ${changeText ? `<div class="mm-token-change ${changeClass}">${changeText}</div>` : ''}
            </div>
            <div class="mm-token-balance">
                ${usdValueText ? `<div class="mm-token-value">${usdValueText}</div>` : ''}
                <div class="mm-token-amount"><span class="token-balance-value">${displayVal}</span> ${name}</div>
            </div>
        `;
        el.onclick = () => {
            document.getElementById('sectionAssets').style.display = 'none';
            document.getElementById('sectionSend').style.display = 'block';
            // Auto-select token in dropdown logic
            const select = document.getElementById('selectToken');
            loadSendTokens(); // Ensure populated
            setTimeout(() => {
                document.getElementById('selectToken').value = b.propertyid;
                updateAvailableBalance();
            }, 50);

            document.querySelectorAll('.mm-tab').forEach(t => t.classList.remove('active'));
        };
        list.appendChild(el);
    }
}

function initShaderBackground() {
    const canvas = document.getElementById('shaderCanvas');
    if (!canvas) return;

    const gl = canvas.getContext('webgl');
    if (!gl) {
        console.warn('WebGL not supported');
        return;
    }

    const vsSource = `
        attribute vec4 aVertexPosition;
        void main() {
            gl_Position = aVertexPosition;
        }
    `;

    const fsSource = `
        precision highp float;
        uniform vec2 iResolution;
        uniform float iTime;

        const float overallSpeed = 0.2;
        const float gridSmoothWidth = 0.015;
        const float axisWidth = 0.05;
        const float majorLineWidth = 0.025;
        const float minorLineWidth = 0.0125;
        const float majorLineFrequency = 5.0;
        const float minorLineFrequency = 1.0;
        const vec4 gridColor = vec4(0.5);
        const float scale = 5.0;
        const vec4 lineColor = vec4(0.4, 0.2, 0.8, 1.0);
        const float minLineWidth = 0.01;
        const float maxLineWidth = 0.2;
        const float lineSpeed = 1.0 * overallSpeed;
        const float lineAmplitude = 1.0;
        const float lineFrequency = 0.2;
        const float warpSpeed = 0.2 * overallSpeed;
        const float warpFrequency = 0.5;
        const float warpAmplitude = 1.0;
        const float offsetFrequency = 0.5;
        const float offsetSpeed = 1.33 * overallSpeed;
        const float minOffsetSpread = 0.6;
        const float maxOffsetSpread = 2.0;
        const int linesPerGroup = 16;

        #define drawCircle(pos, radius, coord) smoothstep(radius + gridSmoothWidth, radius, length(coord - (pos)))
        #define drawSmoothLine(pos, halfWidth, t) smoothstep(halfWidth, 0.0, abs(pos - (t)))
        #define drawCrispLine(pos, halfWidth, t) smoothstep(halfWidth + gridSmoothWidth, halfWidth, abs(pos - (t)))
        #define drawPeriodicLine(freq, width, t) drawCrispLine(freq / 2.0, width, abs(mod(t, freq) - (freq) / 2.0))

        float random(float t) {
            return (cos(t) + cos(t * 1.3 + 1.3) + cos(t * 1.4 + 1.4)) / 3.0;
        }

        float getPlasmaY(float x, float horizontalFade, float offset) {
            return random(x * lineFrequency + iTime * lineSpeed) * horizontalFade * lineAmplitude + offset;
        }

        void main() {
            vec2 fragCoord = gl_FragCoord.xy;
            vec4 fragColor;
            vec2 uv = fragCoord.xy / iResolution.xy;
            vec2 space = (fragCoord - iResolution.xy / 2.0) / iResolution.x * 2.0 * scale;

            float horizontalFade = 1.0 - (cos(uv.x * 6.28) * 0.5 + 0.5);
            float verticalFade = 1.0 - (cos(uv.y * 6.28) * 0.5 + 0.5);

            space.y += random(space.x * warpFrequency + iTime * warpSpeed) * warpAmplitude * (0.5 + horizontalFade);
            space.x += random(space.y * warpFrequency + iTime * warpSpeed + 2.0) * warpAmplitude * horizontalFade;

            vec4 lines = vec4(0.0);
            vec4 bgColor1 = vec4(0.1, 0.1, 0.3, 1.0);
            vec4 bgColor2 = vec4(0.3, 0.1, 0.5, 1.0);

            for(int l = 0; l < linesPerGroup; l++) {
                float normalizedLineIndex = float(l) / float(linesPerGroup);
                float offsetTime = iTime * offsetSpeed;
                float offsetPosition = float(l) + space.x * offsetFrequency;
                float rand = random(offsetPosition + offsetTime) * 0.5 + 0.5;
                float halfWidth = mix(minLineWidth, maxLineWidth, rand * horizontalFade) / 2.0;
                float offset = random(offsetPosition + offsetTime * (1.0 + normalizedLineIndex)) * mix(minOffsetSpread, maxOffsetSpread, horizontalFade);
                float linePosition = getPlasmaY(space.x, horizontalFade, offset);
                float line = drawSmoothLine(linePosition, halfWidth, space.y) / 2.0 + drawCrispLine(linePosition, halfWidth * 0.15, space.y);

                float circleX = mod(float(l) + iTime * lineSpeed, 25.0) - 12.0;
                vec2 circlePosition = vec2(circleX, getPlasmaY(circleX, horizontalFade, offset));
                float circle = drawCircle(circlePosition, 0.01, space) * 4.0;

                line = line + circle;
                lines += line * lineColor * rand;
            }

            fragColor = mix(bgColor1, bgColor2, uv.x);
            fragColor *= verticalFade;
            fragColor.a = 1.0;
            fragColor += lines;

            gl_FragColor = fragColor;
        }
    `;

    function loadShader(gl, type, source) {
        const shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            console.error('Shader compile error: ', gl.getShaderInfoLog(shader));
            gl.deleteShader(shader);
            return null;
        }
        return shader;
    }

    const vertexShader = loadShader(gl, gl.VERTEX_SHADER, vsSource);
    const fragmentShader = loadShader(gl, gl.FRAGMENT_SHADER, fsSource);
    const shaderProgram = gl.createProgram();
    gl.attachShader(shaderProgram, vertexShader);
    gl.attachShader(shaderProgram, fragmentShader);
    gl.linkProgram(shaderProgram);

    if (!gl.getProgramParameter(shaderProgram, gl.LINK_STATUS)) {
        console.error('Shader program link error: ', gl.getProgramInfoLog(shaderProgram));
        return null;
    }

    const positionBuffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
    const positions = [-1.0, -1.0, 1.0, -1.0, -1.0, 1.0, 1.0, 1.0];
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(positions), gl.STATIC_DRAW);

    const programInfo = {
        program: shaderProgram,
        attribLocations: {
            vertexPosition: gl.getAttribLocation(shaderProgram, 'aVertexPosition'),
        },
        uniformLocations: {
            resolution: gl.getUniformLocation(shaderProgram, 'iResolution'),
            time: gl.getUniformLocation(shaderProgram, 'iTime'),
        },
    };

    function resizeCanvas() {
        if (!canvas) return;
        const w = window.innerWidth;
        const h = window.innerHeight * 0.6; // Bottom 60%
        canvas.width = w;
        canvas.height = h;
        gl.viewport(0, 0, w, h);
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    console.log("Shader Init - Canvas Resized to", canvas.width, canvas.height);

    let startTime = Date.now();

    // Debug
    gl.clearColor(0.2, 0.0, 0.4, 0.0); // Transparent background for mask
    gl.clear(gl.COLOR_BUFFER_BIT);
    function render() {
        if (!canvas) return;
        if (canvas.offsetParent === null) {
            requestAnimationFrame(render);
            return;
        }

        const currentTime = (Date.now() - startTime) / 1000;
        // Fallback color (Deep purple) to see if context is alive
        gl.clearColor(0.1, 0.05, 0.2, 1.0);
        gl.clear(gl.COLOR_BUFFER_BIT);

        gl.useProgram(programInfo.program);
        gl.uniform2f(programInfo.uniformLocations.resolution, 360.0, 600.0); // Hardcode for popup
        gl.uniform1f(programInfo.uniformLocations.time, currentTime);

        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
        gl.vertexAttribPointer(programInfo.attribLocations.vertexPosition, 2, gl.FLOAT, false, 0, 0);
        gl.enableVertexAttribArray(programInfo.attribLocations.vertexPosition);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        requestAnimationFrame(render);
    }
    requestAnimationFrame(render);
}

// =========================================================
// LANGUAGE SUPPORT
// =========================================================

let currentLanguage = 'en'; // Default

function loadLanguage() {
    if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
        chrome.storage.local.get(['language'], (result) => {
            if (result.language) {
                updateLanguage(result.language);
            } else {
                updateLanguage('en'); // Default
            }
        });
    }

    // Bind Language Items
    document.querySelectorAll('.mm-lang-item').forEach(item => {
        item.onclick = () => {
            const lang = item.getAttribute('data-lang');
            updateLanguage(lang);
            document.getElementById('modalLanguage').style.display = 'none';
        };
    });
}

function updateLanguage(lang) {
    if (typeof TRANSLATIONS === 'undefined' || !TRANSLATIONS[lang]) return;
    currentLanguage = lang;
    const T = TRANSLATIONS[lang];

    // Update Text Content
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (T[key]) el.innerText = T[key];
    });

    // Update Placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (T[key]) el.placeholder = T[key];
    });

    // Update Titles
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (T[key]) el.title = T[key];
    });

    // Update Active State in Modal
    document.querySelectorAll('.mm-lang-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('data-lang') === lang) {
            item.classList.add('active');
        }
    });

    // Persist
    chrome.storage.local.set({ language: lang });
}

// Helper for dynamic strings
function t(key) {
    if (typeof TRANSLATIONS !== 'undefined' && TRANSLATIONS[currentLanguage] && TRANSLATIONS[currentLanguage][key]) {
        return TRANSLATIONS[currentLanguage][key];
    }
    return key; // Fallback to key or handle appropriately
}

function setupNFTUI() {
    const btnSendNFT = document.getElementById('btnSendNFT');
    if (btnSendNFT) {
        btnSendNFT.onclick = async () => {
            // Jeno proposal:
            if (!sessionMnemonic) {
                alert("Session closed. Please log in again.");
                lockWallet();
                return;
            }
            // end Jeno proposal
            const recipient = document.getElementById('nftRecipientAddress').value.trim();
            const status = document.getElementById('nftSendStatus');

            if (!recipient) {
                status.innerText = "Lütfen alıcı adresi girin";
                status.style.color = "red";
                return;
            }
            if (!currentNFTPid || currentNFTIndex === null) {
                status.innerText = "NFT bilgisi eksik";
                status.style.color = "red";
                return;
            }

            status.innerText = "İşlem hazırlanıyor...";
            status.style.color = "#aaa";

            try {
                // Send NFT (Start=End for single item)
                const txid = await WalletCore.sendNFT(sessionMnemonic, currentAccountIndex, recipient, currentNFTPid, currentNFTIndex, currentNFTIndex);

                if (txid) {
                    status.innerHTML = '<span style="color:#00ffaa;">' + t('success_tx') + ' ' + (typeof txid === 'string' ? txid.substring(0, 10) : 'OK') + '...</span>';
                    status.style.color = "#00ff00";
                    setTimeout(() => {
                        document.getElementById('modalNFTDetail').style.display = 'none';
                        refreshUI();
                    }, 2000);
                }
            } catch (e) {
                console.error(e);
                status.innerText = "Error: " + e.message;
                status.style.color = "red";
            }
        };
    }
}

// =========================================================
// DAPP CONNECTION HANDLING
// =========================================================

let currentDAppRequest = null;

// Check for pending dApp requests on popup open
function checkPendingDAppRequests() {
    chrome.storage.local.get(['pendingDAppRequest'], (data) => {
        if (data.pendingDAppRequest) {
            currentDAppRequest = data.pendingDAppRequest;

            // Clear the pending request from storage
            chrome.storage.local.remove('pendingDAppRequest');

            // Show appropriate modal based on action
            if (currentDAppRequest.action === 'connect') {
                showDAppConnectModal(currentDAppRequest);
            } else if (currentDAppRequest.action === 'signTransaction') {
                showDAppTransactionModal(currentDAppRequest);
            } else if (currentDAppRequest.action === 'signMessage') {
                showDAppMessageModal(currentDAppRequest);
            }
        }
    });
}

// Show connection request modal
function showDAppConnectModal(request) {
    const modal = document.getElementById('modalDAppConnect');
    const originEl = document.getElementById('dappOrigin');

    if (!modal || !originEl) return;

    originEl.textContent = request.origin || 'Unknown site';
    modal.style.display = 'flex';
}

// Show transaction request modal
function showDAppTransactionModal(request) {
    const modal = document.getElementById('modalDAppTransaction');
    if (!modal) return;

    const params = request.params || {};

    document.getElementById('txRequestOrigin').textContent = request.origin || 'Unknown site';
    document.getElementById('txRequestTo').textContent = params.to || '-';
    document.getElementById('txRequestAmount').textContent = params.amount || '0';

    // Determine asset name
    const pid = params.propertyId || 0;
    let assetName = 'XEP';
    if (pid === 199) assetName = 'MEMEX';
    else if (pid !== 0) assetName = `Token #${pid}`;
    document.getElementById('txRequestAsset').textContent = assetName;

    document.getElementById('txRequestStatus').textContent = '';
    modal.style.display = 'flex';
}

// Show message signing modal
function showDAppMessageModal(request) {
    const modal = document.getElementById('modalDAppMessage');
    if (!modal) return;

    document.getElementById('msgRequestOrigin').textContent = request.origin || 'Unknown site';
    document.getElementById('msgRequestMessage').textContent = request.params?.message || '';
    document.getElementById('msgRequestStatus').textContent = '';

    modal.style.display = 'flex';
}

// Setup dApp modal event listeners
function setupDAppModals() {
    // Connection modal buttons
    const btnDAppApprove = document.getElementById('btnDAppApprove');
    const btnDAppReject = document.getElementById('btnDAppReject');

    if (btnDAppApprove) {
        btnDAppApprove.addEventListener('click', async () => {
            if (!currentDAppRequest) return;

            // Get current address
            const acc = accountsData.find(a => Number(a.index) === Number(currentAccountIndex));
            const address = acc ? acc.address : null;

            // Send approval to background
            chrome.runtime.sendMessage({
                type: 'POPUP_RESPONSE',
                requestId: currentDAppRequest.requestId,
                approved: true,
                result: true,
                address: address
            });

            // Close modal
            document.getElementById('modalDAppConnect').style.display = 'none';
            currentDAppRequest = null;
        });
    }

    if (btnDAppReject) {
        btnDAppReject.addEventListener('click', () => {
            if (!currentDAppRequest) return;

            // Send rejection to background
            chrome.runtime.sendMessage({
                type: 'POPUP_RESPONSE',
                requestId: currentDAppRequest.requestId,
                approved: false,
                error: 'User rejected the request'
            });

            // Close modal
            document.getElementById('modalDAppConnect').style.display = 'none';
            currentDAppRequest = null;
        });
    }

    // Transaction modal buttons
    const btnTxApprove = document.getElementById('btnTxApprove');
    const btnTxReject = document.getElementById('btnTxReject');

    if (btnTxApprove) {
        btnTxApprove.addEventListener('click', async () => {
            if (!currentDAppRequest || !sessionMnemonic) {
                alert('Session expired. Please log in again.');
                return;
            }

            const params = currentDAppRequest.params || {};
            const status = document.getElementById('txRequestStatus');

            status.textContent = 'Processing...';
            status.style.color = '#ffaa00';

            try {
                // Validate address
                const isValidAddress = await WalletCore.validateAddressOnline(params.to);
                if (!isValidAddress) {
                    throw new Error('Invalid recipient address');
                }

                let res;
                const pid = params.propertyId || 0;
                const amount = parseFloat(params.amount);
                console.log('DAPP DEBUG - PID:', pid, 'Amount:', amount, 'Decimals:', params.decimals);

                // *** TOKEN METADATA & DECIMALS & BALANCE CHECK ***
                let decimals = params.decimals !== undefined ? parseInt(params.decimals) : null;
                console.log('Decimals from DApp:', decimals);

                if (pid !== 0 && (decimals === null || isNaN(decimals))) {
                    try {
                        const tokenInfo = await WalletCore.getTokenInfo(pid);
                        console.log('Token Info from API:', JSON.stringify(tokenInfo));

                        // API returns decimals as boolean (true = 8 decimals, false = 0)
                        // or as number, or as propertytype string
                        if (tokenInfo) {
                            if (tokenInfo.decimals === true || tokenInfo.divisible === true) {
                                decimals = 8;
                            } else if (tokenInfo.decimals === false || tokenInfo.divisible === false) {
                                decimals = 0;
                            } else if (typeof tokenInfo.decimals === 'number') {
                                decimals = tokenInfo.decimals;
                            } else if (tokenInfo.propertytype === 'divisible' || tokenInfo.type === 'divisible') {
                                decimals = 8;
                            } else {
                                decimals = 0;
                            }
                        } else {
                            decimals = 0;
                        }
                        console.log('Final determined decimals for PID', pid, ':', decimals);
                    } catch (e) {
                        console.log('Error fetching token info, defaulting to 0 decimals:', e);
                        decimals = 0;
                    }
                } else if (pid === 0) {
                    decimals = 8;
                }

                const address = WalletCore.getAccountByIndex(sessionMnemonic, currentAccountIndex).address;
                console.log('--- BALANCE CHECK START ---');
                console.log('Target:', { address, pid, amount, decimals });

                const balances = await WalletCore.getBalances(address);
                console.log('All Balances from API:', JSON.stringify(balances));

                const getPidFromBalance = (b) => (b.propertyid !== undefined) ? parseInt(b.propertyid) : ((b.property_id !== undefined) ? parseInt(b.property_id) : -1);
                const getValueFromBalance = (b) => {
                    const v = (b.value !== undefined) ? b.value : ((b.balance !== undefined) ? b.balance : 0);
                    return parseFloat(v);
                };

                const balanceObj = balances.find(b => getPidFromBalance(b) === parseInt(pid));
                console.log('Found Balance Object for PID', pid, ':', JSON.stringify(balanceObj));
                let foundBalance = balanceObj ? getValueFromBalance(balanceObj) : 0;
                let foundName = pid === 0 ? 'XEP' : (balanceObj?.name || balanceObj?.symbol || `Token ${pid}`);

                // Normalizasyon: API bakiyeyi satoshi formatında döndürüyor (örn: 499986880 = 4.99 LUCE)
                // Eğer token 8 decimal ise (API'den decimals: true veya token decimals === 8) normalize et
                const balanceIsDivisible = balanceObj?.decimals === true || decimals === 8;
                if (balanceIsDivisible && foundBalance >= 1) {
                    // Satoshi formatından human-readable'a çevir
                    console.log('Normalizing satoshi balance:', foundBalance, '/ 10^8');
                    foundBalance = foundBalance / 100000000;
                }
                console.log('Normalized Balance:', foundBalance);

                const hasSufficient = parseFloat(amount) <= foundBalance;
                console.log('Check Result:', { name: foundName, required: amount, available: foundBalance, ok: hasSufficient });

                if (!hasSufficient) {
                    const errMsg = `Insufficient ${foundName} balance. Required: ${amount} ${foundName}, Available: ${foundBalance} ${foundName}`;
                    console.error('BLOCKING TRANSACTION:', errMsg);
                    throw new Error(errMsg);
                }
                console.log('--- BALANCE CHECK PASSED ---');


                // *** İŞLEM GÖNDERİMİ ***
                if (pid === 0) {
                    // XEP için satoshi formatı gerekli
                    const amountSatoshi = Math.round(amount * 100000000);
                    res = await WalletCore.sendNativeTransaction(sessionMnemonic, currentAccountIndex, params.to, amountSatoshi);
                } else {
                    // Token transfer - API float format bekliyor (örn: 1311.63)
                    // Satoshi dönüşümü API tarafında yapılıyor
                    let tokenAmount;
                    if (decimals === 0) {
                        tokenAmount = Math.ceil(amount); // 0 decimal için yukarı yuvarla
                    } else {
                        tokenAmount = amount; // 8 decimal için float olarak gönder
                    }
                    console.log('Final Token Amount (float):', tokenAmount);
                    res = await WalletCore.sendTransaction(sessionMnemonic, currentAccountIndex, params.to, tokenAmount, pid);
                }

                const txid = res.txid || res.result || res;
                const txOk = txid && typeof txid === 'string' && txid.length > 10;

                if (txOk) {
                    status.textContent = 'Transaction sent!';
                    status.style.color = '#00ff00';

                    // Send success to background
                    chrome.runtime.sendMessage({
                        type: 'POPUP_RESPONSE',
                        requestId: currentDAppRequest.requestId,
                        approved: true,
                        result: txid
                    });

                    // Close modal after delay
                    setTimeout(() => {
                        document.getElementById('modalDAppTransaction').style.display = 'none';
                        currentDAppRequest = null;
                        refreshUI();
                    }, 2000);
                } else {
                    throw new Error('Transaction failed');
                }
            } catch (e) {
                status.textContent = 'Error: ' + e.message;
                status.style.color = '#ff4757';
            }
        });
    }

    if (btnTxReject) {
        btnTxReject.addEventListener('click', () => {
            if (!currentDAppRequest) return;

            // Send rejection to background
            chrome.runtime.sendMessage({
                type: 'POPUP_RESPONSE',
                requestId: currentDAppRequest.requestId,
                approved: false,
                error: 'User rejected the transaction'
            });

            // Close modal
            document.getElementById('modalDAppTransaction').style.display = 'none';
            currentDAppRequest = null;
        });
    }

    // Message signing modal buttons
    const btnMsgSign = document.getElementById('btnMsgSign');
    const btnMsgReject = document.getElementById('btnMsgReject');

    if (btnMsgSign) {
        btnMsgSign.addEventListener('click', async () => {
            if (!currentDAppRequest || !sessionMnemonic) {
                alert('Session expired. Please log in again.');
                return;
            }

            const status = document.getElementById('msgRequestStatus');
            status.textContent = 'Signing...';
            status.style.color = '#ffaa00';

            try {
                const message = currentDAppRequest.params?.message;
                if (!message) throw new Error('No message to sign');

                // Call WalletCore.signMessage
                const signature = await WalletCore.signMessage(sessionMnemonic, currentAccountIndex, message);

                status.textContent = 'Signed!';
                status.style.color = '#00ff00';

                // Send success to background
                chrome.runtime.sendMessage({
                    type: 'POPUP_RESPONSE',
                    requestId: currentDAppRequest.requestId,
                    approved: true,
                    result: signature
                });

                setTimeout(() => {
                    document.getElementById('modalDAppMessage').style.display = 'none';
                    currentDAppRequest = null;
                }, 1000);

            } catch (e) {
                console.error(e);
                status.textContent = 'Error: ' + e.message;
                status.style.color = '#ff4757';

                // Report error back to background to prevent timeout
                chrome.runtime.sendMessage({
                    type: 'POPUP_RESPONSE',
                    requestId: currentDAppRequest.requestId,
                    approved: false,
                    error: e.message
                });
            }
        });
    }

    if (btnMsgReject) {
        btnMsgReject.addEventListener('click', () => {
            if (!currentDAppRequest) return;
            // Send rejection
            chrome.runtime.sendMessage({
                type: 'POPUP_RESPONSE',
                requestId: currentDAppRequest.requestId,
                approved: false,
                error: 'User rejected the request'
            });
            document.getElementById('modalDAppMessage').style.display = 'none';
            currentDAppRequest = null;
        });
    }

    // Connected sites modal
    const btnCloseConnectedSites = document.getElementById('btnCloseConnectedSites');
    if (btnCloseConnectedSites) {
        btnCloseConnectedSites.addEventListener('click', () => {
            document.getElementById('modalConnectedSites').style.display = 'none';
        });
    }
}

// Load and display connected sites
function loadConnectedSites() {
    chrome.storage.local.get(['connectedSites'], (data) => {
        const sites = data.connectedSites || {};
        const list = document.getElementById('connectedSitesList');

        if (!list) return;

        const origins = Object.keys(sites);

        if (origins.length === 0) {
            list.innerHTML = `<div style="text-align:center; color:#666; padding: 20px 0;" data-i18n="no_connected_sites">${getTranslation('no_connected_sites')}</div>`;
            return;
        }

        list.innerHTML = '';

        origins.forEach(origin => {
            const site = sites[origin];
            const item = document.createElement('div');
            item.className = 'mm-site-item';

            item.innerHTML = `
                <div class="mm-site-info">
                    <div class="mm-site-origin" title="${origin}">${origin}</div>
                    <div class="mm-site-date">Connected: ${new Date(site.connectedAt).toLocaleDateString()}</div>
                </div>
                <button class="mm-site-disconnect" title="Disconnect">✕</button>
            `;

            const disconnectBtn = item.querySelector('.mm-site-disconnect');
            disconnectBtn.onclick = (e) => {
                e.stopPropagation();
                delete sites[origin];
                chrome.storage.local.set({ connectedSites: sites }, () => {
                    loadConnectedSites();
                });
            };

            list.appendChild(item);
        });
    });
}

// Initialize dApp handling on page load
document.addEventListener('DOMContentLoaded', () => {
    setupDAppModals();

    // Check for pending requests after a short delay (ensure UI is ready)
    setTimeout(checkPendingDAppRequests, 500);
});
