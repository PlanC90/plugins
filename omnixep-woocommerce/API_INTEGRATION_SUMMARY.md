# API Integration Summary - Terms Acceptance

**Date:** February 26, 2026  
**Status:** ✅ IMPLEMENTED  
**Version:** 1.0

---

## ✅ What Was Implemented

### Plugin Side (WordPress):

1. **API Function Added** (`wc_omnixep_send_terms_acceptance_to_api`)
   - Collects all acceptance data
   - Formats JSON payload
   - Sends to API endpoint
   - Non-blocking request (doesn't slow down user)

2. **Acceptance Flow Updated**
   - When user clicks "I Accept"
   - Data saved locally (WordPress database)
   - Data sent to API (background)
   - User redirected to settings

3. **Data Collected:**
   - ✅ Terms version (2.3)
   - ✅ Acceptance timestamp
   - ✅ User information (ID, email, name)
   - ✅ IP address & user agent
   - ✅ Site information
   - ✅ Merchant profile (from invoice settings)
   - ✅ Wallet addresses
   - ✅ Technical info (versions)
   - ✅ Legal acknowledgments

---

## 📊 Data Flow

```
User Clicks "I Accept"
        ↓
WordPress Saves Locally
        ↓
Plugin Calls API Function
        ↓
JSON Payload Created
        ↓
POST to api.planc.space/api
        ↓
API Receives & Validates
        ↓
API Saves to Database
        ↓
API Returns Success
        ↓
User Redirected to Settings
```

---

## 📦 JSON Payload Example

```json
{
  "action": "terms_acceptance",
  "type": "legal_acceptance",
  
  "terms_version": "2.3",
  "terms_effective_date": "2026-02-26",
  
  "accepted_at": "2026-02-26 14:30:00",
  "accepted_by_user_id": 1,
  "accepted_by_email": "admin@xepmarket.com",
  "accepted_by_name": "Admin User",
  "accepted_from_ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  
  "site_url": "https://xepmarket.local",
  "site_name": "XEPMARKET",
  "merchant_id": "abc123...",
  
  "merchant_legal_name": "XEPMARKET Ltd",
  "merchant_country": "TR",
  "merchant_address": "Istanbul, Turkey",
  "merchant_email": "billing@xepmarket.com",
  "merchant_tax_id": "1234567890",
  "merchant_legal_type": "company",
  
  "merchant_wallet_address": "xHKHgwnN1QvCoSyGwpZtFtVqy6wWaRnnSZ",
  "fee_wallet_address": "xABC...",
  
  "plugin_version": "1.8.8",
  "wordpress_version": "6.4.2",
  "woocommerce_version": "8.5.0",
  "php_version": "8.1.0",
  
  "jurisdiction_accepted": "Republic of Türkiye",
  "courts_accepted": "Kırklareli Courts and Enforcement Offices",
  
  "acknowledged_software_only": true,
  "acknowledged_no_custody": true,
  "acknowledged_commission_rate": "0.8%",
  "acknowledged_liability_limit": "$100 USD",
  "acknowledged_security_responsibility": true,
  "acknowledged_regulatory_compliance": true
}
```

---

## 🔧 API Implementation Needed

### 1. Database Table:

```sql
CREATE TABLE omnixep_terms_acceptances (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    acceptance_id VARCHAR(64) UNIQUE NOT NULL,
    merchant_id VARCHAR(64) NOT NULL,
    
    terms_version VARCHAR(10) NOT NULL,
    accepted_at DATETIME NOT NULL,
    
    merchant_legal_name VARCHAR(255) NOT NULL,
    merchant_country VARCHAR(2) NOT NULL,
    merchant_email VARCHAR(255) NOT NULL,
    merchant_wallet_address VARCHAR(100) NOT NULL,
    
    -- ... (see full schema in API_TERMS_ACCEPTANCE_SPEC.md)
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_merchant_id (merchant_id),
    INDEX idx_accepted_at (accepted_at)
);
```

### 2. API Endpoint Handler:

```javascript
// api.planc.space/api
app.post('/api', async (req, res) => {
    const { action, type } = req.body;
    
    if (action === 'terms_acceptance' && type === 'legal_acceptance') {
        // Validate data
        // Generate acceptance_id
        // Insert into database
        // Return success
        
        return res.json({
            success: true,
            message: 'Terms acceptance recorded',
            data: {
                acceptance_id: 'abc123',
                merchant_id: req.body.merchant_id,
                terms_version: req.body.terms_version
            }
        });
    }
});
```

### 3. Validation Rules:

**Required Fields:**
- terms_version
- accepted_at
- merchant_id
- merchant_legal_name
- merchant_email
- merchant_wallet_address

**Format Validation:**
- Email: valid email format
- IP: valid IPv4/IPv6
- Wallet: starts with 'x', 34 chars
- Date: MySQL datetime format

---

## 📋 API Checklist

### Database Setup:
- [ ] Create `omnixep_terms_acceptances` table
- [ ] Add indexes for performance
- [ ] Set up backup strategy
- [ ] Configure retention policy (10 years)

### Endpoint Implementation:
- [ ] Add POST /api handler
- [ ] Implement validation logic
- [ ] Generate unique acceptance_id
- [ ] Insert data into database
- [ ] Return success/error response
- [ ] Add error logging

### Security:
- [ ] Add rate limiting (10 per hour per merchant)
- [ ] Validate IP addresses
- [ ] Validate email format
- [ ] Validate wallet addresses
- [ ] Sanitize all inputs
- [ ] Add CORS headers if needed

### Monitoring:
- [ ] Log all requests
- [ ] Track success/failure rates
- [ ] Monitor database size
- [ ] Set up alerts for errors
- [ ] Create analytics dashboard

### Testing:
- [ ] Test with valid data
- [ ] Test with missing fields
- [ ] Test with invalid formats
- [ ] Test duplicate submissions
- [ ] Test rate limiting
- [ ] Load testing

---

## 🧪 Testing the Integration

### From WordPress:

1. Go to OmniXEP settings
2. Click "Read & Accept Terms"
3. Accept terms
4. Check WordPress error log:
   ```
   === TERMS ACCEPTANCE API SYNC START ===
   Merchant: XEPMARKET Ltd
   Site: https://xepmarket.local
   Version: 2.3
   === TERMS ACCEPTANCE API SYNC SENT ===
   ```

### From API:

1. Check database for new record
2. Verify all fields populated
3. Check acceptance_id is unique
4. Verify timestamps are correct

### Manual Test (cURL):

```bash
curl -X POST https://api.planc.space/api \
  -H "Content-Type: application/json" \
  -d '{"action":"terms_acceptance","type":"legal_acceptance",...}'
```

---

## 📊 Data Fields Reference

### Critical Fields (Must Have):

| Field | Example | Purpose |
|-------|---------|---------|
| merchant_id | "abc123..." | Unique merchant identifier |
| merchant_legal_name | "XEPMARKET Ltd" | Legal entity name |
| merchant_email | "billing@xepmarket.com" | Contact email |
| merchant_wallet_address | "xHKH..." | Payment wallet |
| terms_version | "2.3" | Which version accepted |
| accepted_at | "2026-02-26 14:30:00" | When accepted |
| accepted_from_ip | "192.168.1.100" | Where accepted from |

### Legal Fields (Important):

| Field | Value | Purpose |
|-------|-------|---------|
| jurisdiction_accepted | "Republic of Türkiye" | Legal jurisdiction |
| courts_accepted | "Kırklareli Courts..." | Court jurisdiction |
| acknowledged_commission_rate | "0.8%" | Fee acknowledged |
| acknowledged_liability_limit | "$100 USD" | Liability acknowledged |

### Technical Fields (Useful):

| Field | Example | Purpose |
|-------|---------|---------|
| plugin_version | "1.8.8" | Plugin version |
| wordpress_version | "6.4.2" | WP version |
| php_version | "8.1.0" | PHP version |
| user_agent | "Mozilla/5.0..." | Browser info |

---

## 🔍 Monitoring Queries

### Check Recent Acceptances:

```sql
SELECT 
    merchant_legal_name,
    site_url,
    terms_version,
    accepted_at
FROM omnixep_terms_acceptances
ORDER BY accepted_at DESC
LIMIT 10;
```

### Count by Version:

```sql
SELECT 
    terms_version,
    COUNT(*) as count
FROM omnixep_terms_acceptances
GROUP BY terms_version;
```

### Find Merchants Needing Update:

```sql
SELECT 
    merchant_id,
    merchant_legal_name,
    site_url,
    terms_version,
    accepted_at
FROM omnixep_terms_acceptances
WHERE terms_version < '2.3'
ORDER BY accepted_at DESC;
```

---

## 🚨 Error Handling

### Plugin Side:

```php
// Non-blocking request - errors logged but don't stop user
if (is_wp_error($response)) {
    error_log('TERMS ACCEPTANCE API ERROR: ' . $response->get_error_message());
}
```

### API Side:

```javascript
// Return proper error codes
if (missingFields.length > 0) {
    return res.status(400).json({
        success: false,
        error: 'Missing required fields',
        code: 'MISSING_FIELDS',
        details: { missing_fields: missingFields }
    });
}
```

---

## 📈 Benefits

### For XEPMARKET:

1. ✅ **Legal Compliance**
   - Complete audit trail
   - Proof of acceptance
   - Dispute resolution evidence

2. ✅ **Analytics**
   - Track adoption rates
   - Monitor by country
   - Version tracking

3. ✅ **Customer Management**
   - Merchant database
   - Contact information
   - Wallet tracking

### For Merchants:

1. ✅ **Transparency**
   - Clear record of acceptance
   - Timestamped proof
   - Version tracking

2. ✅ **Security**
   - Acceptance verified
   - IP logged for security
   - Audit trail maintained

---

## 📞 Support

### Plugin Issues:
- Check WordPress error log
- Verify API endpoint is reachable
- Test with cURL manually

### API Issues:
- Check API logs
- Verify database connection
- Test endpoint directly
- Check rate limiting

### Data Issues:
- Verify all required fields sent
- Check data format
- Validate against schema

---

## ✅ Summary

**Status:** ✅ Plugin side COMPLETE, API side READY TO IMPLEMENT

**Plugin Changes:**
- ✅ API function added
- ✅ Acceptance flow updated
- ✅ Data collection complete
- ✅ Error logging added

**API Requirements:**
- [ ] Database table created
- [ ] Endpoint handler implemented
- [ ] Validation added
- [ ] Testing completed

**Next Steps:**
1. Create database table on api.planc.space
2. Implement POST /api handler
3. Test with real data
4. Monitor for errors
5. Set up analytics

---

**Last Updated:** February 26, 2026  
**Version:** 1.0  
**Author:** XEPMARKET & Ceyhun Yılmaz

