# OmniXEP Plugin - Logging System Documentation

**Version:** 1.0  
**Date:** February 26, 2026  
**Status:** ✅ Fully Implemented

---

## Overview

OmniXEP plugin, tüm kritik işlemleri detaylı bir şekilde loglar. Bu loglar WordPress'in `error_log` sistemini kullanır ve `wp-content/debug.log` dosyasına yazılır.

---

## Log Kategorileri

### 1. Terms Acceptance Logs (Sözleşme Onayı)

#### 1.1 Acceptance Start
```
=== OMNIXEP TERMS ACCEPTANCE START ===
Date: 2026-02-26 14:30:00
User ID: 1
User Email: admin@example.com
User Name: John Doe
IP Address: 192.168.1.100
User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
Site URL: https://example.com
Terms Version: 2.3
```

#### 1.2 Acceptance Success
```
✅ Terms acceptance saved to WordPress options
=== OMNIXEP TERMS ACCEPTANCE COMPLETED ===
```

#### 1.3 Acceptance Failure
```
⚠️ OMNIXEP TERMS ACCEPTANCE FAILED: Checkbox not checked
User ID: 1
IP: 192.168.1.100
```

---

### 2. API Sync Logs (API Senkronizasyonu)

#### 2.1 API Sync Start
```
=== TERMS ACCEPTANCE API SYNC START ===
Timestamp: 2026-02-26 14:30:00
Merchant: Example Company Ltd
Site: https://example.com
Merchant ID: 5d41402abc4b2a76b9719d911017c592
Version: 2.3
Language: en
Text Size: 5234 bytes
Checksum: abc123def456789
User: John Doe (admin@example.com)
IP: 192.168.1.100
API Endpoint: https://api.planc.space/api
```

#### 2.2 API Sync Success
```
✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY
Request sent to: https://api.planc.space/api
Payload size: 8456 bytes
=== TERMS ACCEPTANCE API SYNC END ===
```

#### 2.3 API Sync Error
```
❌ TERMS ACCEPTANCE API ERROR: Connection timeout
Error Code: http_request_failed
=== TERMS ACCEPTANCE API SYNC END ===
```

---

### 3. Plugin Deactivation Logs

#### 3.1 Deactivation Start
```
=== OMNIXEP PLUGIN DEACTIVATION START ===
Timestamp: 2026-02-26 16:00:00
Site: https://example.com
Site Name: Example Store
Previous Terms Status: ACCEPTED
Previous Terms Version: 2.3
Previous Acceptance Date: 2026-02-26 14:30:00
Previous Accepted By: John Doe (admin@example.com)
Deactivated By User ID: 1
Deactivated By: John Doe (admin@example.com)
IP Address: 192.168.1.100
```

#### 3.2 Deactivation Complete
```
✅ Terms acceptance data cleared successfully
⚠️ User must re-accept terms on reactivation
=== OMNIXEP PLUGIN DEACTIVATION COMPLETED ===
```

---

### 4. Existing Terms Sync Logs

#### 4.1 Sync Start
```
=== EXISTING TERMS ACCEPTANCE SYNC START ===
Timestamp: 2026-02-26 14:35:00
Original Acceptance Date: 2026-02-26 14:30:00
User ID: 1
IP: 192.168.1.100
Site: https://example.com
```

#### 4.2 Sync Complete
```
✅ EXISTING TERMS ACCEPTANCE SYNCED TO API SUCCESSFULLY
=== EXISTING TERMS ACCEPTANCE SYNC END ===
```

---

### 5. Security Logs

#### 5.1 Fee Wallet Balance Check
```
OmniXEP Security: Fee wallet balance (75000 XEP) exceeds limit (50000 XEP). 
Attempting to transfer 25000 XEP to merchant wallet.
```

#### 5.2 Mobile Callback Security
```
OmniXEP Security: Invalid mobile callback parameters
OmniXEP Security: Invalid TXID format in mobile callback
OmniXEP Security: Rate limit exceeded for mobile callback. IP: 192.168.1.100
OmniXEP Security: Order key mismatch in mobile callback. Order: 123
```

#### 5.3 TXID Validation
```
OmniXEP Security: Invalid TXID format. Order: 123
OmniXEP Security: TXID replay attempt in mobile save. TXID: abc123... already used by Order #122
```

---

### 6. Transaction Logs

#### 6.1 Mobile Callback Success
```
OmniXEP Mobile Callback: TXID saved for Order #123 - abc123def456... (Platform: android)
```

#### 6.2 Smart Polling
```
OmniXEP Smart Polling: Order #123 is already being verified. Skipping.
OmniXEP Smart Polling: Invalid TXID format for #123
OmniXEP Smart Polling: Order #123 completed. Status: processing
```

---

### 7. Cron Job Logs

#### 7.1 Cron Start
```
OmniXEP Cron: Starting execution at 2026-02-26 14:30:00
OmniXEP Cron: Found 5 pending orders.
```

#### 7.2 Order Processing
```
OmniXEP Cron: Checking Order #123 (TXID: abc123def456...)
OmniXEP Cron: Order #123 failed - No TXID
OmniXEP Cron: Order #123 failed - Timeout
OmniXEP Cron: Order #123 status changed from pending to processing
```

---

### 8. API Error Logs

#### 8.1 Balance Fetch Errors
```
[OmniXEP] API Error (OmniXEP API): Connection timeout
[OmniXEP] API Error (ElectrumX): Invalid response
[OmniXEP] Failed to find balance for address: xHKHgwnN1QvCoSyGwpZtFtVqy6wWaRnnSZ
```

#### 8.2 Price Fetch Errors
```
OmniXEP Price Fetch Error (CoinGecko): Rate limit exceeded
```

---

## How to Enable Logging

### Step 1: Enable WordPress Debug Mode

Edit `wp-config.php`:

```php
// Enable WP_DEBUG mode
define('WP_DEBUG', true);

// Enable Debug logging to the /wp-content/debug.log file
define('WP_DEBUG_LOG', true);

// Disable display of errors and warnings
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

### Step 2: Check Log File Location

Logs are written to:
```
/wp-content/debug.log
```

### Step 3: Set Proper Permissions

```bash
chmod 644 wp-content/debug.log
```

---

## How to View Logs

### Method 1: Via FTP/File Manager

1. Connect to your server via FTP or cPanel File Manager
2. Navigate to `wp-content/debug.log`
3. Download and open with text editor

### Method 2: Via SSH

```bash
# View last 100 lines
tail -n 100 wp-content/debug.log

# View last 100 lines and follow new entries
tail -f wp-content/debug.log

# Search for specific terms
grep "OMNIXEP" wp-content/debug.log

# Search for terms acceptance logs
grep "TERMS ACCEPTANCE" wp-content/debug.log

# Search for errors
grep "ERROR" wp-content/debug.log

# Search for today's logs
grep "$(date +%Y-%m-%d)" wp-content/debug.log
```

### Method 3: Via WordPress Plugin

Install a log viewer plugin:
- WP Log Viewer
- Debug Log Manager
- Simple History

---

## Log Analysis Examples

### Example 1: Find All Terms Acceptances

```bash
grep "TERMS ACCEPTANCE START" wp-content/debug.log
```

**Output:**
```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
[26-Feb-2026 16:00:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
```

### Example 2: Find Failed Acceptances

```bash
grep "ACCEPTANCE FAILED" wp-content/debug.log
```

**Output:**
```
[26-Feb-2026 15:00:00 UTC] ⚠️ OMNIXEP TERMS ACCEPTANCE FAILED: Checkbox not checked
```

### Example 3: Find API Sync Errors

```bash
grep "API ERROR" wp-content/debug.log
```

**Output:**
```
[26-Feb-2026 14:30:05 UTC] ❌ TERMS ACCEPTANCE API ERROR: Connection timeout
```

### Example 4: Find Plugin Deactivations

```bash
grep "PLUGIN DEACTIVATION" wp-content/debug.log
```

**Output:**
```
[26-Feb-2026 16:00:00 UTC] === OMNIXEP PLUGIN DEACTIVATION START ===
[26-Feb-2026 16:00:01 UTC] === OMNIXEP PLUGIN DEACTIVATION COMPLETED ===
```

### Example 5: Track Specific User's Actions

```bash
grep "User ID: 1" wp-content/debug.log | grep "OMNIXEP"
```

### Example 6: Find Security Issues

```bash
grep "OmniXEP Security" wp-content/debug.log
```

---

## Log Rotation

### Automatic Log Rotation (Recommended)

Create a cron job to rotate logs weekly:

```bash
# Edit crontab
crontab -e

# Add this line (runs every Sunday at 2 AM)
0 2 * * 0 cd /path/to/wordpress/wp-content && mv debug.log debug.log.$(date +\%Y\%m\%d) && touch debug.log && chmod 644 debug.log
```

### Manual Log Rotation

```bash
# Backup current log
cp wp-content/debug.log wp-content/debug.log.backup

# Clear log file
> wp-content/debug.log

# Or delete and recreate
rm wp-content/debug.log
touch wp-content/debug.log
chmod 644 wp-content/debug.log
```

---

## Log Monitoring & Alerts

### Monitor for Errors (Bash Script)

```bash
#!/bin/bash
# monitor-omnixep-logs.sh

LOG_FILE="/path/to/wordpress/wp-content/debug.log"
EMAIL="admin@example.com"

# Check for errors in last 5 minutes
ERRORS=$(tail -n 1000 "$LOG_FILE" | grep -c "OMNIXEP.*ERROR")

if [ $ERRORS -gt 0 ]; then
    echo "Found $ERRORS OmniXEP errors in the last 1000 log entries" | mail -s "OmniXEP Error Alert" "$EMAIL"
fi
```

### Run Every 5 Minutes

```bash
crontab -e

# Add this line
*/5 * * * * /path/to/monitor-omnixep-logs.sh
```

---

## Log Statistics

### Count Terms Acceptances

```bash
grep -c "TERMS ACCEPTANCE START" wp-content/debug.log
```

### Count API Sync Successes

```bash
grep -c "API SYNC SENT SUCCESSFULLY" wp-content/debug.log
```

### Count API Sync Errors

```bash
grep -c "API ERROR" wp-content/debug.log
```

### Count Plugin Deactivations

```bash
grep -c "PLUGIN DEACTIVATION START" wp-content/debug.log
```

### Count Security Issues

```bash
grep -c "OmniXEP Security" wp-content/debug.log
```

---

## Log Format

All OmniXEP logs follow this format:

```
[Date Time UTC] Log Message
```

**Example:**
```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
```

---

## Log Levels

### ✅ Success (Green)
```
✅ Terms acceptance saved to WordPress options
✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY
```

### ⚠️ Warning (Yellow)
```
⚠️ OMNIXEP TERMS ACCEPTANCE FAILED: Checkbox not checked
⚠️ User must re-accept terms on reactivation
```

### ❌ Error (Red)
```
❌ TERMS ACCEPTANCE API ERROR: Connection timeout
```

### ℹ️ Info (Blue)
```
=== OMNIXEP TERMS ACCEPTANCE START ===
=== OMNIXEP TERMS ACCEPTANCE COMPLETED ===
```

---

## Privacy & GDPR Compliance

### Personal Data in Logs

Logs contain the following personal data:
- User ID
- User email
- User name
- IP address
- User agent

### Data Retention

- Keep logs for 90 days maximum
- Rotate logs weekly
- Delete old logs automatically

### Data Access

- Only administrators should have access to logs
- Protect log files with proper permissions (644)
- Never expose logs publicly

---

## Troubleshooting

### Problem: No Logs Appearing

**Solution:**
1. Check if WP_DEBUG is enabled in wp-config.php
2. Check if WP_DEBUG_LOG is enabled
3. Check file permissions on debug.log (should be 644)
4. Check if web server has write permissions to wp-content/

### Problem: Log File Too Large

**Solution:**
1. Implement log rotation
2. Clear old logs
3. Reduce log verbosity (if needed)

### Problem: Can't Find Specific Log Entry

**Solution:**
1. Use grep with correct search terms
2. Check date format
3. Search for partial strings

---

## Best Practices

### 1. Regular Monitoring
- Check logs daily for errors
- Set up automated alerts
- Monitor API sync success rate

### 2. Log Rotation
- Rotate logs weekly
- Keep backups for 90 days
- Archive important logs

### 3. Security
- Protect log files from public access
- Don't log sensitive data (passwords, private keys)
- Sanitize user input before logging

### 4. Performance
- Don't log excessively
- Use non-blocking API calls
- Implement log rotation to prevent large files

---

## Log Examples

### Complete Terms Acceptance Flow

```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
[26-Feb-2026 14:30:00 UTC] Date: 2026-02-26 14:30:00
[26-Feb-2026 14:30:00 UTC] User ID: 1
[26-Feb-2026 14:30:00 UTC] User Email: admin@example.com
[26-Feb-2026 14:30:00 UTC] User Name: John Doe
[26-Feb-2026 14:30:00 UTC] IP Address: 192.168.1.100
[26-Feb-2026 14:30:00 UTC] User Agent: Mozilla/5.0...
[26-Feb-2026 14:30:00 UTC] Site URL: https://example.com
[26-Feb-2026 14:30:00 UTC] Terms Version: 2.3
[26-Feb-2026 14:30:00 UTC] ✅ Terms acceptance saved to WordPress options
[26-Feb-2026 14:30:00 UTC] === TERMS ACCEPTANCE API SYNC START ===
[26-Feb-2026 14:30:00 UTC] Timestamp: 2026-02-26 14:30:00
[26-Feb-2026 14:30:00 UTC] Merchant: Example Company Ltd
[26-Feb-2026 14:30:00 UTC] Site: https://example.com
[26-Feb-2026 14:30:00 UTC] Merchant ID: 5d41402abc4b2a76b9719d911017c592
[26-Feb-2026 14:30:00 UTC] Version: 2.3
[26-Feb-2026 14:30:00 UTC] Language: en
[26-Feb-2026 14:30:00 UTC] Text Size: 5234 bytes
[26-Feb-2026 14:30:00 UTC] Checksum: abc123def456
[26-Feb-2026 14:30:00 UTC] User: John Doe (admin@example.com)
[26-Feb-2026 14:30:00 UTC] IP: 192.168.1.100
[26-Feb-2026 14:30:00 UTC] API Endpoint: https://api.planc.space/api
[26-Feb-2026 14:30:01 UTC] ✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY
[26-Feb-2026 14:30:01 UTC] Request sent to: https://api.planc.space/api
[26-Feb-2026 14:30:01 UTC] Payload size: 8456 bytes
[26-Feb-2026 14:30:01 UTC] === TERMS ACCEPTANCE API SYNC END ===
[26-Feb-2026 14:30:01 UTC] === OMNIXEP TERMS ACCEPTANCE COMPLETED ===
```

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
