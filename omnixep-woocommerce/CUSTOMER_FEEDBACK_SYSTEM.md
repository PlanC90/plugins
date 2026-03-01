# Customer Feedback & Complaint System

**Version:** 1.0  
**Date:** February 27, 2026

---

## Overview

Müşteri geri bildirim ve şikayet sistemi. Müşteriler footer'daki Electra Protocol logosu altından şikayet gönderebilir. Admin panelinde mağaza bazında şikayet istatistikleri görüntülenir.

---

## System Components

### 1. Frontend - Feedback Form (Footer)

**Location:** Footer, Electra Protocol logo altında

**Form Fields:**
- Order ID (optional)
- Complaint Category (dropdown)
- Description (textarea)
- Email (optional, for follow-up)

**Complaint Categories:**
```
- Product Not Shipped
- Refund Not Processed
- Illegal Product Sale
- Intellectual Property Violation
- Counterfeit Product
- False Advertising
- Poor Quality
- Damaged Product
- Wrong Item Received
- Other
```

**UI Design:**
- Collapsible section (click to expand)
- Modern, minimal design matching site theme
- AJAX submission (no page reload)
- Success/error messages
- reCAPTCHA v3 for spam protection (optional)

---

### 2. API Endpoint - Submit Feedback

**Endpoint:** `POST /api`

**Action:** `submit_feedback`

**Request Body:**
```json
{
  "action": "submit_feedback",
  "site_url": "https://example.com",
  "merchant_id": "5d41402abc...",
  "order_id": "12345",
  "category": "product_not_shipped",
  "description": "I ordered 2 weeks ago but haven't received my product",
  "customer_email": "customer@example.com",
  "customer_ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "submitted_at": "2026-02-27T14:30:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Feedback submitted successfully",
  "feedback_id": "fb_abc123...",
  "reference_number": "FB-2026-001234"
}
```

---

### 3. Firebase Structure

**Collection:** `customer_feedback`

```
customer_feedback/
├── {feedback_id}/
│   ├── feedback_id: string (unique)
│   ├── reference_number: string (FB-YYYY-NNNNNN)
│   ├── merchant_id: string
│   ├── site_url: string
│   ├── order_id: string (optional)
│   ├── category: string
│   ├── category_display: string
│   ├── description: string
│   ├── customer_email: string (optional)
│   ├── customer_ip: string
│   ├── user_agent: string
│   ├── status: string (new/reviewed/resolved/dismissed)
│   ├── severity: string (low/medium/high/critical)
│   ├── admin_notes: string (optional)
│   ├── resolved_at: timestamp (optional)
│   ├── resolved_by: string (optional)
│   ├── submitted_at: timestamp
│   ├── created_at: timestamp
│   └── updated_at: timestamp
```

**Indexes:**
- merchant_id + created_at (desc)
- category + created_at (desc)
- status + created_at (desc)

---

### 4. Admin Panel - Feedback Dashboard

**Location:** CeyhunFaturaRapor/panel/index.html

**New Tab:** "Şikayetler" (Complaints)

**Features:**

#### 4.1 Merchant Feedback Stats (Mağazalar Sekmesi)

Mağazalar tablosuna yeni kolonlar:
- **Şikayet Sayısı:** Toplam şikayet sayısı
- **Kritik:** Yüksek öncelikli şikayetler
- **Detay:** Şikayet kategorilerini göster butonu

**Popup Modal:**
```
Mağaza: example.com
Toplam Şikayet: 15

Kategori Dağılımı:
├─ Product Not Shipped: 5
├─ Refund Not Processed: 3
├─ Poor Quality: 4
├─ Damaged Product: 2
└─ Other: 1

Son Şikayetler:
├─ [2026-02-27] Product Not Shipped - "Order #12345..."
├─ [2026-02-26] Refund Not Processed - "Waiting 2 weeks..."
└─ [2026-02-25] Poor Quality - "Product broke after 1 day..."
```

#### 4.2 Complaints Tab (Yeni Sekme)

**Filters:**
- Merchant (dropdown)
- Category (dropdown)
- Status (new/reviewed/resolved/dismissed)
- Date range
- Severity (low/medium/high/critical)

**Table Columns:**
- Reference Number
- Date
- Merchant
- Category
- Order ID
- Status
- Severity
- Actions (View/Resolve/Dismiss)

**Actions:**
- **View:** Show full details in modal
- **Resolve:** Mark as resolved + add notes
- **Dismiss:** Mark as dismissed + add reason
- **Bulk Actions:** Resolve/dismiss multiple

**Stats Cards:**
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Total Complaints│ New (Unreviewed)│ High Priority   │ Resolved Today  │
│      247        │       18        │       5         │       12        │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

---

### 5. WordPress Plugin Integration

**File:** `wp-content/plugins/omnixep-woocommerce/omnixep-woocommerce.php`

**New Function:** `wc_omnixep_submit_feedback()`

```php
function wc_omnixep_submit_feedback($data) {
    $api_url = 'https://api.planc.space/api';
    $merchant_id = get_option('omnixep_merchant_id');
    
    $payload = array(
        'action' => 'submit_feedback',
        'site_url' => get_site_url(),
        'merchant_id' => $merchant_id,
        'order_id' => sanitize_text_field($data['order_id']),
        'category' => sanitize_text_field($data['category']),
        'description' => sanitize_textarea_field($data['description']),
        'customer_email' => sanitize_email($data['email']),
        'customer_ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'submitted_at' => current_time('c')
    );
    
    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($payload),
        'timeout' => 15
    ));
    
    if (is_wp_error($response)) {
        return array('success' => false, 'message' => 'Connection error');
    }
    
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

// AJAX Handler
add_action('wp_ajax_omnixep_submit_feedback', 'wc_omnixep_ajax_submit_feedback');
add_action('wp_ajax_nopriv_omnixep_submit_feedback', 'wc_omnixep_ajax_submit_feedback');

function wc_omnixep_ajax_submit_feedback() {
    check_ajax_referer('omnixep_feedback_nonce', 'nonce');
    
    $result = wc_omnixep_submit_feedback($_POST);
    wp_send_json($result);
}
```

---

### 6. Footer Integration

**File:** `wp-content/themes/XEPMARKET-ALFA/XEPMARKET-ALFA/footer.php`

**Location:** After Electra Protocol logo/link

```html
<!-- Customer Feedback Section -->
<div class="xep-feedback-section" style="margin-top: 30px;">
    <button type="button" class="xep-feedback-toggle" onclick="xepToggleFeedback()">
        <i class="fa-solid fa-comment-dots"></i>
        Report an Issue
    </button>
    
    <div id="xep-feedback-form-wrap" class="xep-feedback-form-wrap" style="display: none;">
        <form id="xep-feedback-form" class="xep-feedback-form">
            <h4>Report an Issue</h4>
            <p class="xep-feedback-desc">Help us improve by reporting any issues</p>
            
            <div class="xep-form-group">
                <label>Order ID (Optional)</label>
                <input type="text" name="order_id" placeholder="e.g., 12345">
            </div>
            
            <div class="xep-form-group">
                <label>Issue Category *</label>
                <select name="category" required>
                    <option value="">Select a category</option>
                    <option value="product_not_shipped">Product Not Shipped</option>
                    <option value="refund_not_processed">Refund Not Processed</option>
                    <option value="illegal_product">Illegal Product Sale</option>
                    <option value="ip_violation">Intellectual Property Violation</option>
                    <option value="counterfeit">Counterfeit Product</option>
                    <option value="false_advertising">False Advertising</option>
                    <option value="poor_quality">Poor Quality</option>
                    <option value="damaged_product">Damaged Product</option>
                    <option value="wrong_item">Wrong Item Received</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="xep-form-group">
                <label>Description *</label>
                <textarea name="description" rows="4" placeholder="Please describe the issue..." required></textarea>
            </div>
            
            <div class="xep-form-group">
                <label>Email (Optional, for follow-up)</label>
                <input type="email" name="email" placeholder="your@email.com">
            </div>
            
            <div class="xep-form-actions">
                <button type="button" class="xep-btn-cancel" onclick="xepToggleFeedback()">Cancel</button>
                <button type="submit" class="xep-btn-submit">Submit Report</button>
            </div>
            
            <div id="xep-feedback-message" class="xep-feedback-message"></div>
        </form>
    </div>
</div>
```

---

### 7. Severity Auto-Detection

API automatically assigns severity based on category:

```javascript
const severityMap = {
    'illegal_product': 'critical',
    'ip_violation': 'critical',
    'counterfeit': 'critical',
    'refund_not_processed': 'high',
    'product_not_shipped': 'high',
    'false_advertising': 'high',
    'damaged_product': 'medium',
    'wrong_item': 'medium',
    'poor_quality': 'medium',
    'other': 'low'
};
```

---

### 8. Email Notifications (Optional)

**Trigger:** New critical/high severity complaint

**Recipients:**
- Admin email (from .env)
- Merchant email (if available)

**Template:**
```
Subject: [URGENT] New Customer Complaint - {merchant_name}

A new customer complaint has been submitted:

Reference: {reference_number}
Merchant: {site_url}
Category: {category}
Severity: {severity}
Date: {submitted_at}

Description:
{description}

View in admin panel:
{admin_panel_url}

---
This is an automated message from XEPmarket Feedback System
```

---

### 9. Analytics & Reporting

**Metrics to Track:**
- Total complaints per merchant
- Complaints by category
- Average resolution time
- Complaint trends (daily/weekly/monthly)
- Top complained merchants
- Most common issues

**Charts:**
- Complaints over time (line chart)
- Category distribution (pie chart)
- Merchant ranking (bar chart)
- Resolution rate (gauge)

---

### 10. Security Measures

**Rate Limiting:**
- Max 5 submissions per IP per hour
- Max 10 submissions per merchant per day

**Spam Protection:**
- reCAPTCHA v3 (optional)
- Honeypot field
- User-Agent validation
- IP blacklist

**Data Validation:**
- Sanitize all inputs
- Max description length: 2000 chars
- Email format validation
- Category whitelist

**Privacy:**
- Store minimal customer data
- Hash IP addresses (optional)
- GDPR compliance
- Data retention policy (90 days)

---

### 11. Implementation Checklist

#### Phase 1: API & Database
- [ ] Add `submit_feedback` endpoint to fapi/api/index.js
- [ ] Add `submit_feedback` endpoint to CeyhunFaturaRapor/api/index.js
- [ ] Create Firebase indexes
- [ ] Add rate limiting
- [ ] Add spam protection
- [ ] Test API endpoint

#### Phase 2: WordPress Plugin
- [ ] Add feedback submission function
- [ ] Add AJAX handler
- [ ] Add nonce security
- [ ] Test submission from WordPress

#### Phase 3: Frontend Form
- [ ] Add feedback form to footer.php
- [ ] Add CSS styling
- [ ] Add JavaScript handlers
- [ ] Add form validation
- [ ] Add success/error messages
- [ ] Test form submission

#### Phase 4: Admin Panel
- [ ] Add "Şikayetler" tab
- [ ] Add feedback list table
- [ ] Add filters
- [ ] Add stats cards
- [ ] Add merchant feedback stats column
- [ ] Add feedback detail modal
- [ ] Add resolve/dismiss actions
- [ ] Test admin panel

#### Phase 5: Testing & Deployment
- [ ] End-to-end testing
- [ ] Load testing
- [ ] Security testing
- [ ] Deploy to production
- [ ] Monitor for issues

---

### 12. Future Enhancements

**v1.1:**
- Email notifications
- SMS notifications (Twilio)
- Webhook support
- Auto-response templates

**v1.2:**
- AI-powered category detection
- Sentiment analysis
- Auto-severity detection
- Duplicate detection

**v1.3:**
- Customer portal (track complaints)
- Merchant response system
- Escalation workflow
- SLA tracking

**v1.4:**
- Multi-language support
- File attachments (screenshots)
- Video evidence upload
- Live chat integration

---

## Category Translations

**English → Turkish:**
```
product_not_shipped → Ürün Gönderilmedi
refund_not_processed → Para İadesi Yapılmadı
illegal_product → Yasadışı Ürün Satışı
ip_violation → Fikri Mülkiyet İhlali
counterfeit → Sahte Ürün
false_advertising → Yanıltıcı Reklam
poor_quality → Düşük Kalite
damaged_product → Hasarlı Ürün
wrong_item → Yanlış Ürün
other → Diğer
```

---

## API Response Codes

```
200 - Success
400 - Bad Request (missing fields)
429 - Too Many Requests (rate limit)
500 - Internal Server Error
```

---

## Database Queries

**Get merchant feedback count:**
```javascript
const count = await db.collection('customer_feedback')
    .where('merchant_id', '==', merchantId)
    .count()
    .get();
```

**Get feedback by category:**
```javascript
const snapshot = await db.collection('customer_feedback')
    .where('merchant_id', '==', merchantId)
    .where('category', '==', 'product_not_shipped')
    .orderBy('created_at', 'desc')
    .limit(10)
    .get();
```

**Get unresolved feedback:**
```javascript
const snapshot = await db.collection('customer_feedback')
    .where('status', '==', 'new')
    .orderBy('created_at', 'desc')
    .get();
```

---

**End of Specification**
