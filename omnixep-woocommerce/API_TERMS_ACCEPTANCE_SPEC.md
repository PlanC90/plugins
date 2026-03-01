# API Specification: Terms of Service Acceptance

**Endpoint:** `https://api.planc.space/api`  
**Method:** POST  
**Content-Type:** application/json; charset=utf-8  
**Version:** 1.0  
**Date:** February 26, 2026

---

## Overview

When a merchant accepts the OmniXEP Terms of Service (v2.3), the plugin sends acceptance data to the central API for legal record-keeping and compliance tracking.

---

## Request Headers

```http
POST /api HTTP/1.1
Host: api.planc.space
Content-Type: application/json; charset=utf-8
X-OmniXEP-Source: WooCommerce-Terms
X-OmniXEP-Version: 1.8.8
```

---

## Request Body (JSON)

### Complete Payload Structure:

```json
{
  "action": "terms_acceptance",
  "type": "legal_acceptance",
  
  "terms_text": "# OmniXEP WooCommerce Payment Gateway - Terms of Service\n\n**Version:** 2.3...",
  "terms_language": "en",
  "terms_file_size": 15234,
  "terms_checksum": "abc123def456...",
  
  "terms_version": "2.3",
  "terms_effective_date": "2026-02-26",
  
  "accepted_at": "2026-02-26 14:30:00",
  "accepted_by_user_id": 1,
  "accepted_by_email": "admin@example.com",
  "accepted_by_name": "John Doe",
  "accepted_from_ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
  "user_locale": "en_US",
  
  "site_url": "https://example.com",
  "site_name": "Example Store",
  "site_language": "en-US",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592",
  
  "merchant_legal_name": "Example Company Ltd",
  "merchant_country": "TR",
  "merchant_address": "123 Main St, Istanbul, Turkey",
  "merchant_email": "billing@example.com",
  "merchant_tax_id": "1234567890",
  "merchant_legal_type": "company",
  
  "merchant_wallet_address": "xHKHgwnN1QvCoSyGwpZtFtVqy6wWaRnnSZ",
  "fee_wallet_address": "xABCDefgh1234567890abcdefghijklmnop",
  
  "plugin_version": "1.8.8",
  "wordpress_version": "6.4.2",
  "woocommerce_version": "8.5.0",
  "php_version": "8.1.0",
  "server_software": "Apache/2.4.41",
  
  "jurisdiction_accepted": "Republic of Türkiye",
  "courts_accepted": "Kırklareli Courts and Enforcement Offices",
  
  "acknowledged_software_only": true,
  "acknowledged_no_custody": true,
  "acknowledged_commission_rate": "0.8%",
  "acknowledged_liability_limit": "$100 USD",
  "acknowledged_security_responsibility": true,
  "acknowledged_regulatory_compliance": true,
  
  "acceptance_method": "web_form",
  "acceptance_page": "wp-admin/admin.php?page=omnixep-terms",
  "checkbox_confirmed": true
}
```

---

## Field Descriptions

### Core Fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `action` | string | Yes | Always "terms_acceptance" |
| `type` | string | Yes | Always "legal_acceptance" |

### Terms Information:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `terms_version` | string | Yes | Terms version accepted (e.g., "2.3") |
| `terms_effective_date` | string | Yes | Date terms became effective (YYYY-MM-DD) |

### Acceptance Information:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `accepted_at` | string | Yes | MySQL datetime when accepted |
| `accepted_by_user_id` | integer | Yes | WordPress user ID who accepted |
| `accepted_by_email` | string | Yes | Email of user who accepted |
| `accepted_by_name` | string | Yes | Display name of user |
| `accepted_from_ip` | string | Yes | IP address of acceptance |
| `user_agent` | string | Yes | Browser user agent string |

### Site Information:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `site_url` | string | Yes | Full site URL |
| `site_name` | string | Yes | WordPress site name |
| `merchant_id` | string | Yes | MD5 hash of site URL (unique ID) |

### Merchant Profile:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `merchant_legal_name` | string | Yes | Legal name or company name |
| `merchant_country` | string | Yes | Country code (e.g., "TR", "US") |
| `merchant_address` | string | Yes | Full billing address |
| `merchant_email` | string | Yes | Billing email address |
| `merchant_tax_id` | string | No | Tax ID or VAT number |
| `merchant_legal_type` | string | Yes | "individual" or "company" |

### Wallet Information:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `merchant_wallet_address` | string | Yes | Main wallet for receiving payments |
| `fee_wallet_address` | string | Yes | Wallet for paying commissions |

### Technical Information:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `plugin_version` | string | Yes | OmniXEP plugin version |
| `wordpress_version` | string | Yes | WordPress version |
| `woocommerce_version` | string | Yes | WooCommerce version |
| `php_version` | string | Yes | PHP version |

### Legal Acknowledgments:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `jurisdiction_accepted` | string | Yes | "Republic of Türkiye" |
| `courts_accepted` | string | Yes | "Kırklareli Courts and Enforcement Offices" |
| `acknowledged_software_only` | boolean | Yes | Always true |
| `acknowledged_no_custody` | boolean | Yes | Always true |
| `acknowledged_commission_rate` | string | Yes | "0.8%" |
| `acknowledged_liability_limit` | string | Yes | "$100 USD" |
| `acknowledged_security_responsibility` | boolean | Yes | Always true |
| `acknowledged_regulatory_compliance` | boolean | Yes | Always true |

---

## Response Format

### Success Response:

```json
{
  "success": true,
  "message": "Terms acceptance recorded successfully",
  "data": {
    "acceptance_id": "abc123def456",
    "merchant_id": "5d41402abc4b2a76b9719d911017c592",
    "terms_version": "2.3",
    "recorded_at": "2026-02-26T14:30:00Z"
  }
}
```

**HTTP Status:** 200 OK

### Error Response:

```json
{
  "success": false,
  "error": "Invalid merchant data",
  "code": "INVALID_DATA",
  "details": {
    "missing_fields": ["merchant_legal_name", "merchant_email"]
  }
}
```

**HTTP Status:** 400 Bad Request

---

## Database Schema (Recommended)

### Table: `omnixep_terms_acceptances`

```sql
CREATE TABLE omnixep_terms_acceptances (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Unique Identifiers
    acceptance_id VARCHAR(64) UNIQUE NOT NULL,
    merchant_id VARCHAR(64) NOT NULL,
    
    -- Terms Information
    terms_version VARCHAR(10) NOT NULL,
    terms_effective_date DATE NOT NULL,
    
    -- Acceptance Information
    accepted_at DATETIME NOT NULL,
    accepted_by_user_id INT NOT NULL,
    accepted_by_email VARCHAR(255) NOT NULL,
    accepted_by_name VARCHAR(255) NOT NULL,
    accepted_from_ip VARCHAR(45) NOT NULL,
    user_agent TEXT,
    
    -- Site Information
    site_url VARCHAR(500) NOT NULL,
    site_name VARCHAR(255) NOT NULL,
    
    -- Merchant Profile
    merchant_legal_name VARCHAR(255) NOT NULL,
    merchant_country VARCHAR(2) NOT NULL,
    merchant_address TEXT NOT NULL,
    merchant_email VARCHAR(255) NOT NULL,
    merchant_tax_id VARCHAR(100),
    merchant_legal_type ENUM('individual', 'company') NOT NULL,
    
    -- Wallet Information
    merchant_wallet_address VARCHAR(100) NOT NULL,
    fee_wallet_address VARCHAR(100) NOT NULL,
    
    -- Technical Information
    plugin_version VARCHAR(20) NOT NULL,
    wordpress_version VARCHAR(20) NOT NULL,
    woocommerce_version VARCHAR(20) NOT NULL,
    php_version VARCHAR(20) NOT NULL,
    
    -- Legal Acknowledgments
    jurisdiction_accepted VARCHAR(100) NOT NULL,
    courts_accepted VARCHAR(255) NOT NULL,
    acknowledged_software_only BOOLEAN DEFAULT TRUE,
    acknowledged_no_custody BOOLEAN DEFAULT TRUE,
    acknowledged_commission_rate VARCHAR(10) NOT NULL,
    acknowledged_liability_limit VARCHAR(20) NOT NULL,
    acknowledged_security_responsibility BOOLEAN DEFAULT TRUE,
    acknowledged_regulatory_compliance BOOLEAN DEFAULT TRUE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_merchant_id (merchant_id),
    INDEX idx_site_url (site_url(255)),
    INDEX idx_accepted_at (accepted_at),
    INDEX idx_terms_version (terms_version),
    INDEX idx_merchant_email (merchant_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API Implementation Guide

### 1. Endpoint Handler (Node.js/Express Example):

```javascript
app.post('/api', async (req, res) => {
    try {
        const { action, type } = req.body;
        
        // Route to terms acceptance handler
        if (action === 'terms_acceptance' && type === 'legal_acceptance') {
            return await handleTermsAcceptance(req, res);
        }
        
        // Other actions...
        
        res.status(400).json({
            success: false,
            error: 'Unknown action',
            code: 'UNKNOWN_ACTION'
        });
    } catch (error) {
        console.error('API Error:', error);
        res.status(500).json({
            success: false,
            error: 'Internal server error',
            code: 'INTERNAL_ERROR'
        });
    }
});
```

### 2. Terms Acceptance Handler:

```javascript
async function handleTermsAcceptance(req, res) {
    const data = req.body;
    
    // Validate required fields
    const requiredFields = [
        'terms_version',
        'accepted_at',
        'merchant_id',
        'merchant_legal_name',
        'merchant_email',
        'merchant_wallet_address'
    ];
    
    const missingFields = requiredFields.filter(field => !data[field]);
    
    if (missingFields.length > 0) {
        return res.status(400).json({
            success: false,
            error: 'Missing required fields',
            code: 'MISSING_FIELDS',
            details: { missing_fields: missingFields }
        });
    }
    
    // Generate unique acceptance ID
    const acceptanceId = generateAcceptanceId(data.merchant_id, data.accepted_at);
    
    // Check for duplicate
    const existing = await db.query(
        'SELECT id FROM omnixep_terms_acceptances WHERE acceptance_id = ?',
        [acceptanceId]
    );
    
    if (existing.length > 0) {
        return res.status(200).json({
            success: true,
            message: 'Terms acceptance already recorded',
            data: {
                acceptance_id: acceptanceId,
                merchant_id: data.merchant_id,
                terms_version: data.terms_version,
                recorded_at: new Date().toISOString()
            }
        });
    }
    
    // Insert into database
    await db.query(`
        INSERT INTO omnixep_terms_acceptances (
            acceptance_id, merchant_id, terms_version, terms_effective_date,
            accepted_at, accepted_by_user_id, accepted_by_email, accepted_by_name,
            accepted_from_ip, user_agent, site_url, site_name,
            merchant_legal_name, merchant_country, merchant_address,
            merchant_email, merchant_tax_id, merchant_legal_type,
            merchant_wallet_address, fee_wallet_address,
            plugin_version, wordpress_version, woocommerce_version, php_version,
            jurisdiction_accepted, courts_accepted,
            acknowledged_software_only, acknowledged_no_custody,
            acknowledged_commission_rate, acknowledged_liability_limit,
            acknowledged_security_responsibility, acknowledged_regulatory_compliance
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [
        acceptanceId,
        data.merchant_id,
        data.terms_version,
        data.terms_effective_date,
        data.accepted_at,
        data.accepted_by_user_id,
        data.accepted_by_email,
        data.accepted_by_name,
        data.accepted_from_ip,
        data.user_agent,
        data.site_url,
        data.site_name,
        data.merchant_legal_name,
        data.merchant_country,
        data.merchant_address,
        data.merchant_email,
        data.merchant_tax_id || null,
        data.merchant_legal_type,
        data.merchant_wallet_address,
        data.fee_wallet_address,
        data.plugin_version,
        data.wordpress_version,
        data.woocommerce_version,
        data.php_version,
        data.jurisdiction_accepted,
        data.courts_accepted,
        data.acknowledged_software_only,
        data.acknowledged_no_custody,
        data.acknowledged_commission_rate,
        data.acknowledged_liability_limit,
        data.acknowledged_security_responsibility,
        data.acknowledged_regulatory_compliance
    ]);
    
    // Log for audit
    console.log('Terms Acceptance Recorded:', {
        acceptance_id: acceptanceId,
        merchant: data.merchant_legal_name,
        site: data.site_url,
        version: data.terms_version
    });
    
    // Send success response
    res.status(200).json({
        success: true,
        message: 'Terms acceptance recorded successfully',
        data: {
            acceptance_id: acceptanceId,
            merchant_id: data.merchant_id,
            terms_version: data.terms_version,
            recorded_at: new Date().toISOString()
        }
    });
}

function generateAcceptanceId(merchantId, acceptedAt) {
    const crypto = require('crypto');
    const data = `${merchantId}-${acceptedAt}-${Date.now()}`;
    return crypto.createHash('sha256').update(data).digest('hex').substring(0, 32);
}
```

---

## Security Considerations

### 1. **Rate Limiting:**
```javascript
// Limit to 10 acceptances per merchant per hour
const rateLimit = require('express-rate-limit');

const termsLimiter = rateLimit({
    windowMs: 60 * 60 * 1000, // 1 hour
    max: 10,
    keyGenerator: (req) => req.body.merchant_id,
    message: 'Too many acceptance requests'
});

app.post('/api', termsLimiter, handleRequest);
```

### 2. **IP Validation:**
```javascript
// Validate IP format
function isValidIP(ip) {
    const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/;
    const ipv6Regex = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;
    return ipv4Regex.test(ip) || ipv6Regex.test(ip);
}
```

### 3. **Email Validation:**
```javascript
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
```

### 4. **Wallet Address Validation:**
```javascript
function isValidXEPAddress(address) {
    // XEP addresses start with 'x' and are 34 characters
    return /^x[a-zA-Z0-9]{33}$/.test(address);
}
```

---

## Testing

### cURL Example:

```bash
curl -X POST https://api.planc.space/api \
  -H "Content-Type: application/json" \
  -H "X-OmniXEP-Source: WooCommerce-Terms" \
  -H "X-OmniXEP-Version: 1.8.8" \
  -d '{
    "action": "terms_acceptance",
    "type": "legal_acceptance",
    "terms_version": "2.3",
    "terms_effective_date": "2026-02-26",
    "accepted_at": "2026-02-26 14:30:00",
    "accepted_by_user_id": 1,
    "accepted_by_email": "admin@example.com",
    "accepted_by_name": "John Doe",
    "accepted_from_ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0",
    "site_url": "https://example.com",
    "site_name": "Example Store",
    "merchant_id": "5d41402abc4b2a76b9719d911017c592",
    "merchant_legal_name": "Example Company",
    "merchant_country": "TR",
    "merchant_address": "123 Main St",
    "merchant_email": "billing@example.com",
    "merchant_tax_id": "1234567890",
    "merchant_legal_type": "company",
    "merchant_wallet_address": "xHKHgwnN1QvCoSyGwpZtFtVqy6wWaRnnSZ",
    "fee_wallet_address": "xABCDefgh1234567890abcdefghijklmnop",
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
  }'
```

---

## Monitoring & Analytics

### Useful Queries:

```sql
-- Total acceptances by version
SELECT terms_version, COUNT(*) as count
FROM omnixep_terms_acceptances
GROUP BY terms_version;

-- Acceptances by country
SELECT merchant_country, COUNT(*) as count
FROM omnixep_terms_acceptances
GROUP BY merchant_country
ORDER BY count DESC;

-- Recent acceptances
SELECT merchant_legal_name, site_url, accepted_at
FROM omnixep_terms_acceptances
ORDER BY accepted_at DESC
LIMIT 10;

-- Merchants who need to re-accept (old version)
SELECT merchant_id, merchant_legal_name, site_url, terms_version
FROM omnixep_terms_acceptances
WHERE terms_version < '2.3'
ORDER BY accepted_at DESC;
```

---

## Compliance & Legal

### Data Retention:
- Keep acceptance records for **10 years** minimum
- Required for legal disputes and audits
- Comply with GDPR right to access

### Privacy:
- IP addresses are personal data (GDPR)
- Provide data export on request
- Allow deletion after retention period

### Audit Trail:
- Log all API requests
- Track failed attempts
- Monitor for suspicious patterns

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

