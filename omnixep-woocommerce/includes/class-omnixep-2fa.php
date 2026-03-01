<?php
/**
 * OmniXEP 2FA (Two-Factor Authentication)
 * Simple TOTP implementation for mnemonic viewing
 */

if (!defined('ABSPATH')) {
    exit;
}

class OmniXEP_2FA {
    
    /**
     * Check if 2FA is enabled for current user
     */
    public static function is_enabled($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $enabled = get_user_meta($user_id, 'omnixep_2fa_enabled', true);
        return $enabled === 'yes';
    }
    
    /**
     * Get user's 2FA secret
     */
    public static function get_secret($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return get_user_meta($user_id, 'omnixep_2fa_secret', true);
    }
    
    /**
     * Generate new 2FA secret
     */
    public static function generate_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Verify TOTP code
     */
    public static function verify_code($secret, $code, $window = 1) {
        if (empty($secret) || empty($code)) {
            return false;
        }
        
        $code = str_replace(' ', '', $code);
        
        // Check current time and adjacent windows
        $timestamp = time();
        for ($i = -$window; $i <= $window; $i++) {
            $time_slice = floor($timestamp / 30) + $i;
            if (self::generate_totp($secret, $time_slice) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for given time
     */
    private static function generate_totp($secret, $time_slice) {
        // Decode base32 secret
        $secret_key = self::base32_decode($secret);
        
        // Pack time
        $time = pack('N*', 0) . pack('N*', $time_slice);
        
        // Generate HMAC
        $hash = hash_hmac('sha1', $time, $secret_key, true);
        
        // Extract dynamic binary code
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private static function base32_decode($secret) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $decoded = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $decoded .= str_pad(decbin(strpos($chars, $secret[$i])), 5, '0', STR_PAD_LEFT);
        }
        
        $bytes = '';
        for ($i = 0; $i < strlen($decoded); $i += 8) {
            $bytes .= chr(bindec(substr($decoded, $i, 8)));
        }
        
        return $bytes;
    }
    
    /**
     * Get QR code URL for Google Authenticator
     */
    public static function get_qr_code_url($secret, $account_name, $issuer = 'OmniXEP') {
        $url = 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($account_name) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);
    }
    
    /**
     * Enable 2FA for user
     */
    public static function enable($user_id, $secret) {
        update_user_meta($user_id, 'omnixep_2fa_enabled', 'yes');
        update_user_meta($user_id, 'omnixep_2fa_secret', $secret);
    }
    
    /**
     * Disable 2FA for user
     */
    public static function disable($user_id) {
        update_user_meta($user_id, 'omnixep_2fa_enabled', 'no');
        delete_user_meta($user_id, 'omnixep_2fa_secret');
    }
}
