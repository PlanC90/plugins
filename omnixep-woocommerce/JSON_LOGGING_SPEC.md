# OmniXEP JSON Structured Logging Specification

**Version:** 1.0  
**Date:** February 26, 2026  
**Status:** ✅ Implemented

---

## Overview

OmniXEP plugin artık hem human-readable text logs hem de machine-readable JSON structured logs üretiyor. JSON loglar `OMNIXEP_JSON_LOG:` prefix'i ile başlar ve programatik olarak parse edilebilir.

---

## Log Format

### General Format:
```
[Date Time UTC] OMNIXEP_JSON_LOG: {JSON_OBJECT}
```

### Example:
```
[26-Feb-2026 14:30:00 UTC] OMNIXEP_JSON_LOG: {"event":"terms_acceptance","version":"2.3","plugin_version":"1.8.8",...}
```

---

## Event Types

### 1. Terms Acceptance Event

**Event Name:** `terms_acceptance`

**When:** Kullanıcı sözleşmeyi kabul ettiğinde

**JSON Structure:**
```json
{
  "event": "terms_acceptance",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:00Z",
  "ip_address": "192.168.1.100",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "user_id": 1,
  "user_email": "admin@example.com",
  "user_name": "John Doe",
  "site_url": "https://example.com",
  "site_name": "Example Store",
  "status": "accepted_and_bound",
  "acceptance_method": "web_form",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| event | string | Always "terms_acceptance" |
| version | string | Terms version (e.g., "2.3") |
| plugin_version | string | Plugin version (e.g., "1.8.8") |
| timestamp | string | ISO 8601 UTC timestamp |
| ip_address | string | User's IP address |
| merchant_id | string | MD5 hash of site URL |
| user_id | integer | WordPress user ID |
| user_email | string | User's email address |
| user_name | string | User's display name |
| site_url | string | Full site URL |
| site_name | string | WordPress site name |
| status | string | Always "accepted_and_bound" |
| acceptance_method | string | Always "web_form" |
| user_agent | string | Browser user agent |

---

### 2. API Sync Attempt Event

**Event Name:** `api_sync_attempt`

**When:** Sözleşme API'ye gönderilmeye çalışıldığında

**JSON Structure:**
```json
{
  "event": "api_sync_attempt",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:01Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "merchant_name": "Example Company Ltd",
  "site_url": "https://example.com",
  "api_endpoint": "https://api.planc.space/api",
  "payload_size": 8456,
  "terms_text_size": 5234,
  "terms_checksum": "abc123def456",
  "user_email": "admin@example.com",
  "ip_address": "192.168.1.100"
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| event | string | Always "api_sync_attempt" |
| version | string | Terms version |
| plugin_version | string | Plugin version |
| timestamp | string | ISO 8601 UTC timestamp |
| merchant_id | string | MD5 hash of site URL |
| merchant_name | string | Merchant legal name |
| site_url | string | Full site URL |
| api_endpoint | string | API endpoint URL |
| payload_size | integer | Total payload size in bytes |
| terms_text_size | integer | Terms text size in bytes |
| terms_checksum | string | MD5 checksum of terms text |
| user_email | string | User's email |
| ip_address | string | User's IP address |

---

### 3. API Sync Success Event

**Event Name:** `api_sync_success`

**When:** API'ye başarıyla gönderildiğinde

**JSON Structure:**
```json
{
  "event": "api_sync_success",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:02Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "api_endpoint": "https://api.planc.space/api",
  "payload_size": 8456,
  "status": "success"
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| event | string | Always "api_sync_success" |
| version | string | Terms version |
| plugin_version | string | Plugin version |
| timestamp | string | ISO 8601 UTC timestamp |
| merchant_id | string | MD5 hash of site URL |
| api_endpoint | string | API endpoint URL |
| payload_size | integer | Payload size in bytes |
| status | string | Always "success" |

---

### 4. API Sync Error Event

**Event Name:** `api_sync_error`

**When:** API'ye gönderim başarısız olduğunda

**JSON Structure:**
```json
{
  "event": "api_sync_error",
  "version": "2.3",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T14:30:02Z",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  "error_message": "Connection timeout",
  "error_code": "http_request_failed",
  "status": "failed"
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| event | string | Always "api_sync_error" |
| version | string | Terms version |
| plugin_version | string | Plugin version |
| timestamp | string | ISO 8601 UTC timestamp |
| merchant_id | string | MD5 hash of site URL |
| error_message | string | Error description |
| error_code | string | Error code |
| status | string | Always "failed" |

---

### 5. Plugin Deactivation Event

**Event Name:** `plugin_deactivation`

**When:** Plugin deaktive edildiğinde

**JSON Structure:**
```json
{
  "event": "plugin_deactivation",
  "plugin_version": "1.8.8",
  "timestamp": "2026-02-26T16:00:00Z",
  "site_url": "https://example.com",
  "site_name": "Example Store",
  "previous_terms_status": "accepted",
  "previous_terms_version": "2.3",
  "previous_acceptance_date": "2026-02-26 14:30:00",
  "deactivated_by_user_id": 1,
  "deactivated_by_email": "admin@example.com",
  "ip_address": "192.168.1.100",
  "status": "terms_cleared_reacceptance_required"
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| event | string | Always "plugin_deactivation" |
| plugin_version | string | Plugin version |
| timestamp | string | ISO 8601 UTC timestamp |
| site_url | string | Full site URL |
| site_name | string | WordPress site name |
| previous_terms_status | string | "accepted" or "not_accepted" |
| previous_terms_version | string | Previous terms version |
| previous_acceptance_date | string | Previous acceptance date |
| deactivated_by_user_id | integer | User ID who deactivated |
| deactivated_by_email | string | Email of user who deactivated |
| ip_address | string | IP address |
| status | string | Always "terms_cleared_reacceptance_required" |

---

## How to Extract JSON Logs

### Method 1: Grep for JSON Logs

```bash
# Extract all JSON logs
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log

# Extract only terms acceptance events
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | grep "terms_acceptance"

# Extract only API sync errors
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | grep "api_sync_error"

# Extract only deactivation events
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | grep "plugin_deactivation"
```

### Method 2: Parse JSON with jq

```bash
# Extract JSON part and pretty print
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | sed 's/.*OMNIXEP_JSON_LOG: //' | jq '.'

# Filter by event type
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | sed 's/.*OMNIXEP_JSON_LOG: //' | jq 'select(.event == "terms_acceptance")'

# Get all merchant IDs
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | sed 's/.*OMNIXEP_JSON_LOG: //' | jq -r '.merchant_id' | sort -u

# Count events by type
grep "OMNIXEP_JSON_LOG:" wp-content/debug.log | sed 's/.*OMNIXEP_JSON_LOG: //' | jq -r '.event' | sort | uniq -c
```

### Method 3: Python Script

```python
#!/usr/bin/env python3
import json
import re

# Read log file
with open('wp-content/debug.log', 'r') as f:
    logs = f.readlines()

# Extract JSON logs
json_logs = []
for line in logs:
    if 'OMNIXEP_JSON_LOG:' in line:
        # Extract JSON part
        json_str = line.split('OMNIXEP_JSON_LOG: ')[1].strip()
        try:
            json_obj = json.loads(json_str)
            json_logs.append(json_obj)
        except json.JSONDecodeError:
            pass

# Print statistics
print(f"Total JSON logs: {len(json_logs)}")

# Count by event type
event_counts = {}
for log in json_logs:
    event = log.get('event', 'unknown')
    event_counts[event] = event_counts.get(event, 0) + 1

print("\nEvents by type:")
for event, count in event_counts.items():
    print(f"  {event}: {count}")

# Print all terms acceptances
print("\nTerms Acceptances:")
for log in json_logs:
    if log.get('event') == 'terms_acceptance':
        print(f"  {log['timestamp']} - {log['user_email']} - {log['site_url']}")
```

### Method 4: PHP Script

```php
<?php
// parse-omnixep-logs.php

$log_file = 'wp-content/debug.log';
$logs = file($log_file);

$json_logs = [];

foreach ($logs as $line) {
    if (strpos($line, 'OMNIXEP_JSON_LOG:') !== false) {
        // Extract JSON part
        $parts = explode('OMNIXEP_JSON_LOG: ', $line);
        if (isset($parts[1])) {
            $json_str = trim($parts[1]);
            $json_obj = json_decode($json_str, true);
            if ($json_obj) {
                $json_logs[] = $json_obj;
            }
        }
    }
}

echo "Total JSON logs: " . count($json_logs) . "\n\n";

// Count by event type
$event_counts = [];
foreach ($json_logs as $log) {
    $event = $log['event'] ?? 'unknown';
    $event_counts[$event] = ($event_counts[$event] ?? 0) + 1;
}

echo "Events by type:\n";
foreach ($event_counts as $event => $count) {
    echo "  $event: $count\n";
}

// Print terms acceptances
echo "\nTerms Acceptances:\n";
foreach ($json_logs as $log) {
    if ($log['event'] === 'terms_acceptance') {
        echo sprintf(
            "  %s - %s - %s\n",
            $log['timestamp'],
            $log['user_email'],
            $log['site_url']
        );
    }
}
```

---

## Log Analysis Examples

### Example 1: Complete Terms Acceptance Flow

```
[26-Feb-2026 14:30:00 UTC] === OMNIXEP TERMS ACCEPTANCE START ===
[26-Feb-2026 14:30:00 UTC] OMNIXEP_JSON_LOG: {"event":"terms_acceptance","version":"2.3","plugin_version":"1.8.8","timestamp":"2026-02-26T14:30:00Z","ip_address":"192.168.1.100","merchant_id":"5d41402abc4b2a76b9719d911017c592","user_id":1,"user_email":"admin@example.com","user_name":"John Doe","site_url":"https://example.com","site_name":"Example Store","status":"accepted_and_bound","acceptance_method":"web_form","user_agent":"Mozilla/5.0..."}
[26-Feb-2026 14:30:00 UTC] ✅ Terms acceptance saved to WordPress options
[26-Feb-2026 14:30:01 UTC] === TERMS ACCEPTANCE API SYNC START ===
[26-Feb-2026 14:30:01 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_attempt","version":"2.3","plugin_version":"1.8.8","timestamp":"2026-02-26T14:30:01Z","merchant_id":"5d41402abc4b2a76b9719d911017c592","merchant_name":"Example Company Ltd","site_url":"https://example.com","api_endpoint":"https://api.planc.space/api","payload_size":8456,"terms_text_size":5234,"terms_checksum":"abc123def456","user_email":"admin@example.com","ip_address":"192.168.1.100"}
[26-Feb-2026 14:30:02 UTC] ✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY
[26-Feb-2026 14:30:02 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_success","version":"2.3","plugin_version":"1.8.8","timestamp":"2026-02-26T14:30:02Z","merchant_id":"5d41402abc4b2a76b9719d911017c592","api_endpoint":"https://api.planc.space/api","payload_size":8456,"status":"success"}
[26-Feb-2026 14:30:02 UTC] === OMNIXEP TERMS ACCEPTANCE COMPLETED ===
```

### Example 2: API Sync Error

```
[26-Feb-2026 14:30:01 UTC] === TERMS ACCEPTANCE API SYNC START ===
[26-Feb-2026 14:30:01 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_attempt","version":"2.3",...}
[26-Feb-2026 14:30:05 UTC] ❌ TERMS ACCEPTANCE API ERROR: Connection timeout
[26-Feb-2026 14:30:05 UTC] OMNIXEP_JSON_LOG: {"event":"api_sync_error","version":"2.3","plugin_version":"1.8.8","timestamp":"2026-02-26T14:30:05Z","merchant_id":"5d41402abc4b2a76b9719d911017c592","error_message":"Connection timeout","error_code":"http_request_failed","status":"failed"}
```

### Example 3: Plugin Deactivation

```
[26-Feb-2026 16:00:00 UTC] === OMNIXEP PLUGIN DEACTIVATION START ===
[26-Feb-2026 16:00:01 UTC] ✅ Terms acceptance data cleared successfully
[26-Feb-2026 16:00:01 UTC] OMNIXEP_JSON_LOG: {"event":"plugin_deactivation","plugin_version":"1.8.8","timestamp":"2026-02-26T16:00:01Z","site_url":"https://example.com","site_name":"Example Store","previous_terms_status":"accepted","previous_terms_version":"2.3","previous_acceptance_date":"2026-02-26 14:30:00","deactivated_by_user_id":1,"deactivated_by_email":"admin@example.com","ip_address":"192.168.1.100","status":"terms_cleared_reacceptance_required"}
[26-Feb-2026 16:00:01 UTC] === OMNIXEP PLUGIN DEACTIVATION COMPLETED ===
```

---

## Integration with Log Management Systems

### Splunk

```spl
source="wp-content/debug.log" "OMNIXEP_JSON_LOG:"
| rex field=_raw "OMNIXEP_JSON_LOG: (?<json_data>.*)"
| spath input=json_data
| stats count by event
```

### Elasticsearch / Logstash

```ruby
filter {
  if [message] =~ /OMNIXEP_JSON_LOG:/ {
    grok {
      match => { "message" => "OMNIXEP_JSON_LOG: %{GREEDYDATA:json_data}" }
    }
    json {
      source => "json_data"
    }
  }
}
```

### CloudWatch Logs Insights

```
fields @timestamp, event, merchant_id, status
| filter @message like /OMNIXEP_JSON_LOG:/
| parse @message "OMNIXEP_JSON_LOG: *" as json_data
| stats count() by event
```

---

## Benefits of JSON Structured Logging

### 1. Machine Readable
- Easy to parse programmatically
- Can be imported into databases
- Compatible with log management systems

### 2. Queryable
- Filter by specific fields
- Aggregate statistics
- Generate reports

### 3. Standardized
- Consistent format across all events
- ISO 8601 timestamps
- Predictable field names

### 4. Extensible
- Easy to add new fields
- Backward compatible
- Version tracking

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
