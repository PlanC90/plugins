# Customer Feedback System - Bot Protection & Error Handling

**Date:** February 27, 2026  
**Status:** IMPLEMENTED ✅

---

## 🛡️ Bot Protection Mechanisms

### 1. Honeypot Field
**Location:** `footer.php`

```html
<!-- Hidden field that bots will fill but humans won't see -->
<input type="text" name="website" value="" 
       style="position: absolute; left: -9999px; width: 1px; height: 1px;" 
       tabindex="-1" autocomplete="off">
```

**How it works:**
- Field is hidden from users (positioned off-screen)
- Bots automatically fill all form fields
- If this field has a value, submission is rejected

**Detection:** WordPress plugin checks `$_POST['website']`

---

### 2. Time-Based Detection
**Location:** `footer.php` + `omnixep-woocommerce.php`

```javascript
// Set timestamp when form opens
document.getElementById('xep-form-loaded-at').value = Date.now();

// Check on submit (must be at least 3 seconds)
var timeDiff = Date.now() - formLoadedAt;
if (timeDiff < 3000) {
    // Rejected: submitted too fast
}
```

**How it works:**
- Records timestamp when form is opened
- Calculates time difference on submission
- Rejects if submitted in less than 3 seconds (bot behavior)

**Detection:** Both frontend JavaScript and backend PHP

---

### 3. Rate Limiting (API Level)
**Location:** `fapi/api/index.js`

```javascript
// Check submissions from same IP in last hour
const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
const recentFeedback = await db.collection('customer_feedback')
    .where('customer_ip', '==', customerIp)
    .where('created_at', '>', oneHourAgo)
    .count()
    .get();

if (recentFeedback.data().count >= 5) {
    return res.status(429).json({
        success: false,
        error: 'Rate limit exceeded',
        message: 'Too many submissions. Please try again later.'
    });
}
```

**How it works:**
- Tracks submissions by IP address
- Maximum 5 submissions per IP per hour
- Returns HTTP 429 (Too Many Requests) if exceeded

---

### 4. Category Validation
**Location:** `fapi/api/index.js`

```javascript
const validCategories = [
    'product_not_shipped',
    'refund_not_processed',
    'illegal_product',
    'ip_violation',
    'counterfeit',
    'false_advertising',
    'poor_quality',
    'damaged_product',
    'wrong_item',
    'other'
];

if (!validCategories.includes(data.category)) {
    return res.status(400).json({
        success: false,
        error: 'Invalid category'
    });
}
```

**How it works:**
- Only accepts predefined categories
- Rejects any custom/malicious category values

---

### 5. Description Length Limit
**Location:** `fapi/api/index.js`

```javascript
description: (data.description || '').substring(0, 2000), // Max 2000 chars
```

**How it works:**
- Limits description to 2000 characters
- Prevents spam with extremely long text

---

## 🔧 Error Handling Improvements

### 1. Better Error Messages
**Before:**
```javascript
messageDiv.innerHTML = 'An error occurred. Please try again.';
```

**After:**
```javascript
var errorMsg = data.message || data.error || 'An error occurred. Please try again.';
messageDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + errorMsg;
```

**Improvement:**
- Shows specific error from API
- Falls back to generic message if no error provided
- Includes icon for better UX

---

### 2. HTTP Status Code Handling
**Location:** `omnixep-woocommerce.php`

```php
$status_code = wp_remote_retrieve_response_code($response);

// Handle non-200 responses
if ($status_code !== 200) {
    error_log('OmniXEP Feedback API Error (HTTP ' . $status_code . '): ' . $body);
    
    if (!empty($result['error'])) {
        return array(
            'success' => false,
            'message' => $result['error'],
            'error' => $result['error']
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Server error. Please try again later.',
        'error' => 'HTTP ' . $status_code
    );
}
```

**Improvement:**
- Checks HTTP status code
- Logs errors for debugging
- Returns appropriate error message to user

---

### 3. Console Logging
**Location:** `footer.php`

```javascript
.catch(function(error) {
    console.error('Feedback submission error:', error);
    messageDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> Connection error. Please try again.';
});
```

**Improvement:**
- Logs errors to browser console for debugging
- Helps developers identify issues

---

## 🧪 Testing Bot Protection

### Test 1: Honeypot Field
```bash
# Fill the hidden "website" field
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -d "action=omnixep_submit_feedback" \
  -d "website=http://spam.com" \
  -d "category=other" \
  -d "description=test"

# Expected: "Invalid submission detected."
```

### Test 2: Fast Submission
```bash
# Submit immediately after loading (< 3 seconds)
# Expected: "Please take a moment to review your submission."
```

### Test 3: Rate Limiting
```bash
# Submit 6 times from same IP within 1 hour
# Expected on 6th attempt: "Too many submissions. Please try again later."
```

### Test 4: Invalid Category
```bash
curl -X POST https://api.planc.space/api \
  -H "Content-Type: application/json" \
  -d '{
    "action": "submit_feedback",
    "site_url": "https://test.com",
    "merchant_id": "test123",
    "category": "invalid_category",
    "description": "test"
  }'

# Expected: "Invalid category"
```

---

## 📊 Bot Protection Summary

| Protection Type | Location | Effectiveness | User Impact |
|----------------|----------|---------------|-------------|
| Honeypot | Frontend + Backend | High | None (invisible) |
| Time-based | Frontend + Backend | Medium | None (3s minimum) |
| Rate Limiting | API | High | Low (5/hour limit) |
| Category Validation | API | Medium | None (valid categories) |
| Length Limit | API | Low | None (2000 chars) |

---

## 🚀 Additional Bot Protection Ideas (Future)

### 1. Google reCAPTCHA v3
```javascript
// Add to form submission
grecaptcha.ready(function() {
    grecaptcha.execute('SITE_KEY', {action: 'submit_feedback'})
        .then(function(token) {
            formData.append('recaptcha_token', token);
            // Submit form
        });
});
```

### 2. User-Agent Validation
```php
// Block known bot user agents
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$bot_patterns = ['bot', 'crawler', 'spider', 'scraper'];
foreach ($bot_patterns as $pattern) {
    if (stripos($user_agent, $pattern) !== false) {
        // Reject submission
    }
}
```

### 3. JavaScript Challenge
```javascript
// Simple math challenge (invisible to users)
var challenge = Math.floor(Math.random() * 10) + 1;
var answer = challenge * 2;
formData.append('challenge', challenge);
formData.append('answer', answer);
```

### 4. Session-Based Tracking
```php
// Track form views in session
if (!isset($_SESSION['feedback_form_views'])) {
    $_SESSION['feedback_form_views'] = 0;
}
$_SESSION['feedback_form_views']++;

// Reject if too many views without submission
if ($_SESSION['feedback_form_views'] > 10) {
    // Suspicious behavior
}
```

---

## 📝 Error Messages Reference

| Error | Cause | Solution |
|-------|-------|----------|
| "Invalid submission detected" | Honeypot filled | User is likely a bot |
| "Please take a moment to review" | Submitted < 3 seconds | Wait longer before submitting |
| "Too many submissions" | 5+ in 1 hour | Wait 1 hour before trying again |
| "Invalid category" | Wrong category value | Use valid category from dropdown |
| "Missing required fields" | Empty required fields | Fill all required fields |
| "Connection error" | Network/server issue | Check internet connection |
| "Server error" | API error | Contact support |

---

## ✅ Implementation Checklist

- [x] Honeypot field added to form
- [x] Time-based detection (frontend)
- [x] Time-based detection (backend)
- [x] Rate limiting (API)
- [x] Category validation (API)
- [x] Description length limit (API)
- [x] Better error messages (frontend)
- [x] HTTP status code handling (backend)
- [x] Console error logging (frontend)
- [x] Error logging (backend)

---

## 🎯 Result

**Bot Protection Level:** 🟢 HIGH

The feedback system now has multiple layers of bot protection:
1. ✅ Honeypot (catches simple bots)
2. ✅ Time-based detection (catches fast bots)
3. ✅ Rate limiting (prevents spam)
4. ✅ Input validation (prevents malicious data)

**Error Handling:** 🟢 EXCELLENT

Users now see clear, specific error messages instead of generic "An error occurred" messages.

---

**Last Updated:** February 27, 2026  
**Status:** PRODUCTION READY ✅
