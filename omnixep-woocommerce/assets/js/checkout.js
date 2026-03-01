jQuery(function ($) {
    var omnixep_params = window.wc_omnixep_params || {};

    // Helper to set hidden inputs correctly
    function setHiddenInput($form, id, name, value) {
        if ($form.find('#' + id).length === 0) {
            $('<input>').attr({
                type: 'hidden',
                id: id,
                name: name,
                value: value
            }).appendTo($form);
        } else {
            $form.find('#' + id).val(value);
        }
    }

    // ============================================================
    // CLIENT-SIDE FORM VALIDATION
    // Validates all required fields BEFORE connecting to wallet
    // ============================================================
    function validateCheckoutForm($form) {
        var isValid = true;
        var firstInvalid = null;

        // Clear previous validation errors
        $form.find('.woocommerce-invalid').removeClass('woocommerce-invalid woocommerce-invalid-required-field');

        // Check all required fields
        $form.find('.validate-required').each(function () {
            var $field = $(this);
            var $input = $field.find('input, select, textarea').not('[type="hidden"]');

            if ($input.length === 0) return;

            var value = $input.val();

            // For select2/dropdowns
            if ($input.is('select')) {
                value = $input.val();
            }

            // Check if empty
            if (!value || value.trim() === '') {
                $field.addClass('woocommerce-invalid woocommerce-invalid-required-field');
                isValid = false;
                if (!firstInvalid) {
                    firstInvalid = $field;
                }
            }
        });

        // Also validate email format
        var $email = $form.find('#billing_email');
        if ($email.length > 0 && $email.val()) {
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test($email.val())) {
                $email.closest('.form-row').addClass('woocommerce-invalid woocommerce-invalid-email');
                isValid = false;
                if (!firstInvalid) {
                    firstInvalid = $email.closest('.form-row');
                }
            }
        }

        // Scroll to first invalid field
        if (!isValid && firstInvalid) {
            $('html, body').animate({
                scrollTop: firstInvalid.offset().top - 100
            }, 400);
        }

        return isValid;
    }

    // ============================================================
    // CHECKOUT FORM HANDLER
    // ============================================================
    $('form.checkout').on('checkout_place_order_omnixep', function () {
        var $form = $(this);

        // If TXID already exists, allow WooCommerce AJAX to proceed
        if ($form.find('#omnixep_txid').length > 0 && $form.find('#omnixep_txid').val()) {
            console.log('OmniXEP: TXID exists (' + $form.find('#omnixep_txid').val() + '), allowing WooCommerce submission.');
            return true;
        }

        // ── STEP 1: Validate form BEFORE connecting to wallet ──
        if (!validateCheckoutForm($form)) {
            console.log('OmniXEP: Form validation failed. Not connecting to wallet.');
            return false;
        }

        // Get selected token
        var selected_option = $('#omnixep-token-select option:selected');
        if (selected_option.length === 0 || !selected_option.val()) {
            alert('Please select a token.');
            return false;
        }

        var propertyId = parseInt(selected_option.val());
        var total_amount = parseFloat(selected_option.attr('data-amount'));
        var token_name = selected_option.data('name') || 'XEP';
        var decimals = parseInt(selected_option.attr('data-decimals')) || 0;
        var merchant = omnixep_params.merchant_address;

        // ── STEP 2: Validate via WooCommerce AJAX before wallet ──
        console.log('OmniXEP: Form looks valid. Validating with server...');
        $('#omnixep-processing-msg').text('⏳ Validating your details...').fadeIn();

        // Make an AJAX call to validate the checkout data
        var formData = $form.serialize() + '&omnixep_validate_only=1';

        $.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: formData,
            dataType: 'json',
            success: function (result) {
                // If WooCommerce returns an error, show it and DON'T connect wallet
                if (result.result === 'failure') {
                    console.log('OmniXEP: Server validation failed.', result);
                    $('#omnixep-processing-msg').fadeOut();

                    // Show WooCommerce error messages
                    if (result.messages) {
                        $('.woocommerce-notices-wrapper, .woocommerce-error').remove();
                        $form.prepend(result.messages);
                        $('html, body').animate({ scrollTop: $form.offset().top - 100 }, 400);
                    }
                    return;
                }

                // Server validation passed - NOW connect wallet
                console.log('OmniXEP: Server validation passed. Connecting wallet...');
                connectAndPay($form, merchant, total_amount, propertyId, decimals, token_name);
            },
            error: function () {
                // On AJAX error, still try wallet (fallback to client-side validation only)
                console.warn('OmniXEP: Server validation request failed, proceeding with client validation only.');
                connectAndPay($form, merchant, total_amount, propertyId, decimals, token_name);
            }
        });

        return false; // Always prevent default, we handle submission ourselves
    });

    // ============================================================
    // WALLET CONNECTION & PAYMENT
    // Only called AFTER validation passes
    // ============================================================
    function connectAndPay($form, merchant, total_amount, propertyId, decimals, token_name) {

        // ---- FLOW 1: Extension Wallet (window.omnixep - lowercase) ----
        if (window.omnixep) {
            console.log('OmniXEP: Extension detected (window.omnixep). Connecting...');
            $('#omnixep-processing-msg').text('⏳ Connecting to wallet...').fadeIn();

            window.omnixep.connect().then(function (isConnected) {
                if (!isConnected) {
                    alert('Please connect your OmniXEP Wallet to proceed.');
                    $('#omnixep-processing-msg').fadeOut();
                    return Promise.reject('Not connected');
                }

                console.log('OmniXEP: Connected. Requesting transaction sign...');
                $('#omnixep-processing-msg').text('⏳ Please confirm the payment in your wallet...');

                var merchant_payload = {
                    to: merchant.trim(),
                    amount: total_amount,
                    propertyId: parseInt(propertyId),
                    property_id: parseInt(propertyId),
                    decimals: decimals,
                    fee: 0.01
                };
                return window.omnixep.signTransaction(merchant_payload);

            }).then(function (txId) {
                console.log('OmniXEP: Wallet returned TXID:', txId);
                handleTxSuccess($form, txId, token_name, total_amount);
            }).catch(function (error) {
                console.error('OmniXEP Payment error:', error);
                $('#omnixep-processing-msg').fadeOut();
                if (error === 'Not connected') return;
                alert('Payment Error: ' + (error.message || error));
            });

            return;
        }

        // ---- FLOW 1b: New-style Extension (window.omniXep - camelCase) ----
        if (window.omniXep) {
            console.log('OmniXEP: Extension detected (window.omniXep). Sending request...');
            $('#omnixep-processing-msg').text('⏳ Please confirm the payment in your wallet...').fadeIn();

            // Wrap in an async IIFE so we can use modern await/try-catch
            (async function () {
                try {
                    const txId = await window.omniXep.request({
                        method: 'sendTransaction',
                        params: [{
                            pid: parseInt(propertyId),
                            recipient: merchant.trim(),
                            amount: total_amount
                        }]
                    });

                    console.log('OmniXEP: Payment done. TXID:', txId);
                    handleTxSuccess($form, txId, token_name, total_amount);
                } catch (error) {
                    console.error('OmniXEP Payment request failed:', error);
                    console.error('Error details:', {
                        code: error.code,
                        message: error.message,
                        data: error.data
                    });
                    $('#omnixep-processing-msg').fadeOut();

                    // Ignore user rejection code (4001) per JSON-RPC 2.0 specs
                    if (error && error.code === 4001) {
                        console.log('User rejected the payment request');
                        return;
                    }
                    
                    // Show detailed error message
                    let errorMsg = 'Payment Error: ';
                    if (error.message === 'Wallet locked') {
                        errorMsg += 'Please unlock your OmniXEP wallet extension first, then try again.';
                    } else {
                        errorMsg += (error.message || error);
                    }
                    alert(errorMsg);
                }
            })();

            return;
        }

        // ---- FLOW 2: Mobile Deep Link ----
        if (omnixep_params.is_mobile) {
            console.log('OmniXEP: Mobile detected, using deep link flow.');
            setHiddenInput($form, 'omnixep_mobile_pending', 'omnixep_mobile_pending', '1');
            setHiddenInput($form, 'omnixep_token_name', 'omnixep_token_name', token_name);
            setHiddenInput($form, 'omnixep_merchant_amount', 'omnixep_merchant_amount', total_amount.toString());
            setHiddenInput($form, 'omnixep_selected_pid', 'omnixep_selected_pid', propertyId.toString());
            setHiddenInput($form, 'omnixep_selected_decimals', 'omnixep_selected_decimals', decimals.toString());
            $form.submit();
            return;
        }

        // ---- FLOW 3: Check for iframe wallet ----
        if (window.frames['omnixep-wallet-iframe']) {
            console.log('OmniXEP: Checking iframe wallet...');
            $('#omnixep-processing-msg').text('⏳ Connecting to wallet...').fadeIn();
            
            // Try to access wallet API from iframe
            try {
                var iframeWindow = window.frames['omnixep-wallet-iframe'].contentWindow;
                if (iframeWindow.omnixep) {
                    console.log('OmniXEP: iframe wallet found, using it...');
                    
                    iframeWindow.omnixep.connect().then(function(isConnected) {
                        if (!isConnected) {
                            alert('Please connect your OmniXEP Wallet to proceed.');
                            $('#omnixep-processing-msg').fadeOut();
                            return Promise.reject('Not connected');
                        }
                        
                        $('#omnixep-processing-msg').text('⏳ Please confirm the payment in your wallet...');
                        
                        var merchant_payload = {
                            to: merchant.trim(),
                            amount: total_amount,
                            propertyId: parseInt(propertyId),
                            property_id: parseInt(propertyId),
                            decimals: decimals,
                            fee: 0.01
                        };
                        return iframeWindow.omnixep.signTransaction(merchant_payload);
                        
                    }).then(function(txId) {
                        console.log('OmniXEP: iframe wallet returned TXID:', txId);
                        handleTxSuccess($form, txId, token_name, total_amount);
                    }).catch(function(error) {
                        console.error('OmniXEP iframe wallet error:', error);
                        $('#omnixep-processing-msg').fadeOut();
                        if (error === 'Not connected') return;
                        alert('Payment Error: ' + (error.message || error));
                    });
                    
                    return;
                }
            } catch (e) {
                console.warn('OmniXEP: Cannot access iframe wallet (CORS?):', e);
            }
        }

        // ---- FLOW 4: Web Wallet (popup) ----
        if (omnixep_params.web_wallet_url && omnixep_params.web_wallet_url.trim() !== '') {
            console.log('OmniXEP: Opening web wallet at:', omnixep_params.web_wallet_url);
            $('#omnixep-processing-msg').text('⏳ Opening wallet...').fadeIn();
            
            openWebWallet(merchant, total_amount, propertyId, decimals, token_name, function(txId) {
                if (txId) {
                    handleTxSuccess($form, txId, token_name, total_amount);
                } else {
                    $('#omnixep-processing-msg').fadeOut();
                    console.log('OmniXEP: Payment cancelled or failed');
                }
            });
            return;
        }

        // ---- FLOW 4: No wallet found ----
        console.warn('OmniXEP: No wallet found. Checked: window.omnixep, window.omniXep, web wallet');
        
        // Show user-friendly message
        var errorMsg = 'OmniXEP Wallet not found!\n\n';
        errorMsg += 'Please choose one of the following options:\n';
        errorMsg += '• Install the OmniXEP browser extension\n';
        errorMsg += '• Use a mobile device with OmniXEP app\n';
        
        if (omnixep_params.web_wallet_url) {
            errorMsg += '• Contact site administrator to enable web wallet';
        }
        
        alert(errorMsg);
        $('#omnixep-processing-msg').fadeOut();
    }

    // ============================================================
    // WEB WALLET INTEGRATION
    // Opens web wallet in popup/iframe and handles payment
    // ============================================================
    function openWebWallet(merchant, amount, propertyId, decimals, tokenName, callback) {
        var walletUrl = omnixep_params.web_wallet_url;
        
        // Build payment request URL with parameters
        var paymentData = {
            to: merchant,
            amount: amount,
            propertyId: propertyId,
            decimals: decimals,
            token: tokenName,
            returnUrl: window.location.href
        };
        
        // Open wallet in popup
        var popupWidth = 400;
        var popupHeight = 700;
        var left = (screen.width - popupWidth) / 2;
        var top = (screen.height - popupHeight) / 2;
        
        var popup = window.open(
            walletUrl + '?payment=' + encodeURIComponent(JSON.stringify(paymentData)),
            'OmniXEP Wallet',
            'width=' + popupWidth + ',height=' + popupHeight + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
        );
        
        if (!popup) {
            alert('Please allow popups for this site to use the web wallet.');
            callback(null);
            return;
        }
        
        // Listen for payment result from popup
        window.addEventListener('message', function handleWalletMessage(event) {
            // Security: Check origin
            if (event.origin !== new URL(walletUrl).origin) {
                return;
            }
            
            if (event.data && event.data.type === 'OMNIXEP_PAYMENT') {
                window.removeEventListener('message', handleWalletMessage);
                
                if (event.data.success && event.data.txid) {
                    console.log('OmniXEP Web Wallet: Payment successful, TXID:', event.data.txid);
                    popup.close();
                    callback(event.data.txid);
                } else {
                    console.log('OmniXEP Web Wallet: Payment cancelled or failed');
                    popup.close();
                    callback(null);
                }
            }
        });
        
        // Check if popup was closed without payment
        var popupCheck = setInterval(function() {
            if (popup.closed) {
                clearInterval(popupCheck);
                console.log('OmniXEP Web Wallet: Popup closed');
                callback(null);
            }
        }, 500);
    }

    // ============================================================
    // HANDLE SUCCESSFUL TRANSACTION
    // ============================================================
    function handleTxSuccess($form, txId, token_name, total_amount) {
        if (txId && typeof txId === 'string' && txId.length > 10) {
            setHiddenInput($form, 'omnixep_txid', 'omnixep_txid', txId);
            setHiddenInput($form, 'omnixep_token_name', 'omnixep_token_name', token_name);
            setHiddenInput($form, 'omnixep_merchant_amount', 'omnixep_merchant_amount', total_amount);
            setHiddenInput($form, 'omnixep_platform', 'omnixep_platform', 'Web');

            $('#omnixep-processing-msg').text('✅ Payment confirmed! Creating your order...');
            $form.submit();
        } else {
            console.warn('OmniXEP: Empty or invalid TXID:', txId);
            $('#omnixep-processing-msg').fadeOut();
            alert('No transaction ID returned from wallet. Payment may have been cancelled.');
        }
    }
});
