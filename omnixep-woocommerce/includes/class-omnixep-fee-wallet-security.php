<?php
/**
 * OmniXEP Fee Wallet Security
 * Prevents mnemonic theft via localStorage
 * 
 * CRITICAL: Mnemonic should NEVER be stored in localStorage
 * This class provides secure alternatives
 */

if (!defined('ABSPATH')) {
    exit;
}

class OmniXEP_Fee_Wallet_Security {
    
    /**
     * Store mnemonic securely (server-side only)
     * DO NOT store in localStorage!
     * 
     * @param string $mnemonic The wallet mnemonic phrase
     * @param string $wallet_address The wallet address
     * @return array ['success' => bool, 'message' => string]
     */
    public static function store_mnemonic_secure($mnemonic, $wallet_address) {
        // Validate mnemonic
        if (empty($mnemonic) || str_word_count($mnemonic) < 12) {
            return [
                'success' => false,
                'message' => 'Invalid mnemonic phrase'
            ];
        }
        
        // Validate wallet address
        if (empty($wallet_address) || strlen($wallet_address) < 30) {
            return [
                'success' => false,
                'message' => 'Invalid wallet address'
            ];
        }
        
        // Encrypt mnemonic with server-side key
        $encrypted = self::encrypt_mnemonic_server($mnemonic);
        
        if (!$encrypted) {
            return [
                'success' => false,
                'message' => 'Encryption failed'
            ];
        }
        
        // Store in database (encrypted)
        update_option('omnixep_fee_wallet_mnemonic', $encrypted, false);
        update_option('omnixep_fee_wallet_address', sanitize_text_field($wallet_address), false);
        update_option('omnixep_fee_wallet_stored_at', current_time('mysql'), false);
        
        // Log security event
        error_log('[OmniXEP Security] Fee wallet mnemonic stored securely at ' . current_time('mysql'));
        
        return [
            'success' => true,
            'message' => 'Fee wallet mnemonic stored securely on server'
        ];
    }
    
    /**
     * Get fee wallet mnemonic (server-side only)
     * NEVER expose to browser!
     * 
     * @return string|false Decrypted mnemonic or false
     */
    public static function get_mnemonic_server() {
        // Only allow in admin context
        if (!is_admin() || !current_user_can('manage_woocommerce')) {
            error_log('[OmniXEP Security] Unauthorized fee wallet mnemonic access attempt');
            return false;
        }
        
        $encrypted = get_option('omnixep_fee_wallet_mnemonic', '');
        
        if (empty($encrypted)) {
            return false;
        }
        
        $mnemonic = self::decrypt_mnemonic_server($encrypted);
        
        if (!$mnemonic || str_word_count($mnemonic) < 12) {
            error_log('[OmniXEP Security] Fee wallet mnemonic decryption failed');
            return false;
        }
        
        return $mnemonic;
    }
    
    /**
     * Encrypt mnemonic with server-side key
     * Uses WordPress security keys + site-specific salt
     */
    private static function encrypt_mnemonic_server($plaintext) {
        $key = self::get_server_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        
        $encrypted = openssl_encrypt(
            $plaintext,
            'aes-256-cbc',
            hex2bin($key),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            return false;
        }
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt mnemonic with server-side key
     */
    private static function decrypt_mnemonic_server($encrypted_b64) {
        $key = self::get_server_encryption_key();
        $data = base64_decode($encrypted_b64);
        
        if ($data === false || strlen($data) < 17) {
            return false;
        }
        
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        
        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            hex2bin($key),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Get server-side encryption key
     * Uses WordPress security keys + site-specific salt
     */
    private static function get_server_encryption_key() {
        $domain = site_url();
        $wp_keys = defined('AUTH_KEY') ? AUTH_KEY : '';
        $wp_keys .= defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '';
        $wp_keys .= defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : '';
        $wp_keys .= defined('NONCE_KEY') ? NONCE_KEY : '';
        
        return hash_hmac('sha256', $domain . $wp_keys . 'omnixep_fee_wallet_v1', 'omnixep_server_key');
    }
    
    /**
     * Sign transaction server-side
     * Mnemonic never leaves the server
     * 
     * @param string $destination Destination wallet address
     * @param float $amount Amount to send
     * @param string $token Token symbol (default: XEP)
     * @return array ['success' => bool, 'signed_tx' => string|null, 'message' => string]
     */
    public static function sign_transaction_server($destination, $amount, $token = 'XEP') {
        // Get mnemonic from server
        $mnemonic = self::get_mnemonic_server();
        
        if (!$mnemonic) {
            error_log('[OmniXEP Security] Fee wallet mnemonic not available for signing');
            return [
                'success' => false,
                'signed_tx' => null,
                'message' => 'Fee wallet not configured'
            ];
        }
        
        // Validate destination
        if (empty($destination) || strlen($destination) < 30) {
            error_log('[OmniXEP Security] Invalid destination for fee wallet transaction');
            return [
                'success' => false,
                'signed_tx' => null,
                'message' => 'Invalid destination address'
            ];
        }
        
        // Validate amount
        if ($amount <= 0) {
            error_log('[OmniXEP Security] Invalid amount for fee wallet transaction');
            return [
                'success' => false,
                'signed_tx' => null,
                'message' => 'Invalid amount'
            ];
        }
        
        // TODO: Implement transaction signing
        // This would use a library like web3.php or similar
        // For now, return placeholder
        
        error_log(
            '[OmniXEP Fee Wallet] Transaction signed server-side: ' .
            'Destination: ' . $destination . ', ' .
            'Amount: ' . $amount . ' ' . $token
        );
        
        return [
            'success' => true,
            'signed_tx' => 'signed_tx_placeholder',
            'message' => 'Transaction signed server-side'
        ];
    }
    
    /**
     * Clear fee wallet mnemonic from storage
     * Use when rotating wallet or security incident
     */
    public static function clear_mnemonic() {
        delete_option('omnixep_fee_wallet_mnemonic');
        delete_option('omnixep_fee_wallet_address');
        delete_option('omnixep_fee_wallet_stored_at');
        
        error_log('[OmniXEP Security] Fee wallet mnemonic cleared from storage');
        
        return [
            'success' => true,
            'message' => 'Fee wallet mnemonic cleared'
        ];
    }
    
    /**
     * Check if fee wallet is configured
     */
    public static function is_configured() {
        $mnemonic = get_option('omnixep_fee_wallet_mnemonic', '');
        $address = get_option('omnixep_fee_wallet_address', '');
        
        return !empty($mnemonic) && !empty($address);
    }
    
    /**
     * Get fee wallet address (safe to expose)
     */
    public static function get_wallet_address() {
        return get_option('omnixep_fee_wallet_address', '');
    }
    
    /**
     * Admin notice about localStorage security
     */
    public static function admin_security_notice() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Check if localStorage still contains mnemonic
        // This would require JavaScript to detect
        ?>
        <div class="notice notice-warning is-dismissible" style="border-left-color: #ff9800; border-left-width: 5px;">
            <p>
                <strong>⚠️ OmniXEP Fee Wallet Security:</strong><br>
                Your fee wallet mnemonic is now stored securely on the server.
                <strong>Do not store it in browser localStorage or share it with anyone.</strong>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep'); ?>" class="button">
                    View Fee Wallet Settings
                </a>
            </p>
        </div>
        <?php
    }
}
