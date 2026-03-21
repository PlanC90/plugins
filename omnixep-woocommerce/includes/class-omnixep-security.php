<?php
/**
 * OmniXEP Security Helper
 * Rate Limiting + Wallet Validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class OmniXEP_Security {
    
    /**
     * Check rate limit with exponential backoff
     * 
     * @param string $action Action name (e.g., 'mnemonic_access', 'broadcast_tx')
     * @param int $max_attempts Maximum attempts allowed
     * @param int $base_wait_seconds Base wait time in seconds
     * @param bool $exponential Use exponential backoff
     * @return array ['allowed' => bool, 'message' => string, 'wait_seconds' => int]
     */
    public static function check_rate_limit($action, $max_attempts = 5, $base_wait_seconds = 3600, $exponential = true) {
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();
        
        if (!$user_id) {
            return [
                'allowed' => false,
                'message' => 'User not authenticated',
                'wait_seconds' => 0
            ];
        }
        
        $cache_key = 'omnixep_ratelimit_blocked_' . $action . '_' . $user_id . '_' . md5($ip_address);
        $attempts_key = 'omnixep_ratelimit_attempts_' . $action . '_' . $user_id . '_' . md5($ip_address);
        $fail_key = 'omnixep_ratelimit_fails_' . $action . '_' . $user_id . '_' . md5($ip_address);
        
        // Get failed attempts count
        $failed_attempts = get_transient($fail_key);
        if ($failed_attempts === false) {
            $failed_attempts = 0;
        }
        
        // Calculate wait time
        if ($exponential) {
            // Exponential backoff: 60s, 120s, 240s, 480s, 960s, 1920s, 3600s (max 1 hour)
            $wait_time = pow(2, $failed_attempts) * $base_wait_seconds;
            if ($wait_time > 86400) { // Max 24 hours
                $wait_time = 86400;
            }
        } else {
            // Fixed wait time
            $wait_time = $base_wait_seconds;
        }
        
        // Check if actively blocked
        $is_blocked = get_transient($cache_key);
        if ($is_blocked !== false) {
            // Still blocked
            set_transient($fail_key, $failed_attempts + 1, $wait_time);
            
            error_log(
                '[OmniXEP Security] Rate limit blocked for action: ' . $action . 
                ', User: ' . $user_id . 
                ', IP: ' . $ip_address . 
                ', Failed attempts: ' . ($failed_attempts + 1) .
                ', Wait time: ' . $wait_time . 's'
            );
            
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Too many attempts. Please wait %d seconds before trying again.',
                    $wait_time
                ),
                'wait_seconds' => $wait_time
            ];
        }

        // Get current attempts in this window
        $attempts = get_transient($attempts_key);
        if ($attempts === false) {
            $attempts = 0;
        }
        $attempts++;
        
        if ($attempts > $max_attempts) {
            // Block them now
            set_transient($cache_key, true, $wait_time);
            set_transient($fail_key, $failed_attempts + 1, $wait_time);
            delete_transient($attempts_key);
            
            error_log(
                '[OmniXEP Security] Rate limit EXCEEDED for action: ' . $action . 
                ', User: ' . $user_id . 
                ', IP: ' . $ip_address . 
                ', Mode: Now Blocked'
            );
            
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Too many attempts. Please wait %d seconds before trying again.',
                    $wait_time
                ),
                'wait_seconds' => $wait_time
            ];
        }
        
        // Allowed - update attempts count
        set_transient($attempts_key, $attempts, $base_wait_seconds);
        
        return [
            'allowed' => true,
            'message' => 'Rate limit check passed',
            'wait_seconds' => 0
        ];
    }
    
    /**
     * Reset rate limit for an action (call after successful operation)
     */
    public static function reset_rate_limit($action) {
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();
        
        $cache_key = 'omnixep_ratelimit_blocked_' . $action . '_' . $user_id . '_' . md5($ip_address);
        $attempts_key = 'omnixep_ratelimit_attempts_' . $action . '_' . $user_id . '_' . md5($ip_address);
        $fail_key = 'omnixep_ratelimit_fails_' . $action . '_' . $user_id . '_' . md5($ip_address);
        
        delete_transient($cache_key);
        delete_transient($attempts_key);
        delete_transient($fail_key);
    }
    
    /**
     * Get client IP address
     */
    public static function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Validate wallet address format
     * 
     * @param string $address Wallet address to validate
     * @return bool True if valid XEP address format
     */
    public static function is_valid_wallet_address($address) {
        $address = trim($address);
        
        // XEP addresses typically start with 'x' and are 34 characters
        // Format: xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
        if (preg_match('/^x[a-zA-Z0-9]{33}$/', $address)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate transaction destination
     * Ensures transaction only goes to approved wallets (commission or merchant)
     * 
     * @param string $destination_address Destination wallet address
     * @param string|array $approved_addresses Approved wallet address(es)
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validate_transaction_destination($destination_address, $approved_addresses) {
        $destination_address = trim($destination_address);
        
        // Validate format
        if (!self::is_valid_wallet_address($destination_address)) {
            return [
                'valid' => false,
                'message' => 'Invalid destination wallet address format'
            ];
        }
        
        // Normalize to array
        if (!is_array($approved_addresses)) {
            $approved_addresses = array($approved_addresses);
        }
        
        // Filter out empty values and validate each
        $approved_addresses = array_filter(array_map('trim', $approved_addresses));
        
        foreach ($approved_addresses as $approved) {
            if (!self::is_valid_wallet_address($approved)) {
                continue; // Skip invalid approved addresses
            }
            if ($destination_address === $approved) {
                return [
                    'valid' => true,
                    'message' => 'Transaction destination validated'
                ];
            }
        }
        
        error_log(
            '[OmniXEP Security] Transaction destination mismatch! ' .
            'Destination: ' . $destination_address . 
            ', Approved: ' . implode(', ', $approved_addresses)
        );
        
        return [
            'valid' => false,
            'message' => 'Transaction destination does not match any approved wallet'
        ];
    }
    
    /**
     * Validate commission amount doesn't exceed expected rate
     * 
     * @param float $commission_usd Commission amount in USD
     * @param float $order_total Order total in USD
     * @param float $max_rate Maximum allowed commission rate (default 2%)
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validate_commission_amount($commission_usd, $order_total, $max_rate = 0.02) {
        if ($commission_usd < 0) {
            return [
                'valid' => false,
                'message' => 'Commission amount cannot be negative'
            ];
        }
        
        if ($order_total <= 0) {
            return [
                'valid' => false,
                'message' => 'Order total must be positive'
            ];
        }
        
        $commission_rate = $commission_usd / $order_total;
        $max_commission_usd = $order_total * $max_rate;
        
        if ($commission_usd > $max_commission_usd) {
            error_log(
                '[OmniXEP Security] Commission validation failed: ' .
                'Commission: ' . $commission_usd . ' USD, ' .
                'Rate: ' . ($commission_rate * 100) . '%, ' .
                'Max allowed: ' . ($max_rate * 100) . '%'
            );
            
            return [
                'valid' => false,
                'message' => sprintf(
                    'Commission exceeds safety limit: %.2f%% (max: %.2f%%)',
                    $commission_rate * 100,
                    $max_rate * 100
                )
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Commission amount validated'
        ];
    }
    
    /**
     * Get commission statistics for monitoring
     */
    public static function get_commission_stats() {
        $events = get_option('omnixep_security_events', []);
        
        $total_commission = 0;
        $commission_events = array_filter($events, function($e) {
            return strpos($e['event_type'], 'commission') !== false;
        });
        
        return [
            'total_events' => count($commission_events),
            'recent_events' => array_slice($commission_events, -10)
        ];
    }
    
    /**
     * Log security event for monitoring and auditing
     */
    public static function log_security_event($event_type, $details = []) {
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'details' => $details
        ];
        
        error_log('[OmniXEP Security Event] ' . json_encode($log_entry));
        
        // Also store in database for admin dashboard
        $events = get_option('omnixep_security_events', []);
        $events[] = $log_entry;
        
        // Keep only last 1000 events
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        update_option('omnixep_security_events', $events);
    }
    
    /**
     * Get security events for admin dashboard
     */
    public static function get_security_events($limit = 50) {
        $events = get_option('omnixep_security_events', []);
        return array_slice($events, -$limit);
    }
}
