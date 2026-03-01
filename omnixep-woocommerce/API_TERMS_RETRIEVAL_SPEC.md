# API Specification: Terms Retrieval (Sözleşme Çekme)

**Endpoint:** `https://api.planc.space/api/terms`  
**Method:** GET  
**Purpose:** Mağaza için kaydedilmiş sözleşme metnini çekmek  
**Date:** February 26, 2026

---

## Problem

Panel'de "Sözleşme ön izleme" açıldığında:
```
Bu mağaza için kayıtlı sözleşme metni bulunmuyor. Sözleşme mağaza kayıtlı 
(API) arasında terms_text alanı ile Firebase'e yazılır ve buradan gösterilir.
```

Bu mesaj, API'de sözleşme metnini çekecek endpoint olmadığını gösteriyor.

---

## Solution: Terms Retrieval Endpoint

### Endpoint 1: Get Terms by Merchant ID

**URL:** `GET /api/terms?merchant_id={merchant_id}`

**Request:**
```http
GET /api/terms?merchant_id=5d41402abc4b2a76b9719d911017c592 HTTP/1.1
Host: api.planc.space
X-OmniXEP-Source: Admin-Panel
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "merchant_id": "5d41402abc4b2a76b9719d911017c592",
    "site_url": "https://example.com",
    "site_name": "Example Store",
    "terms_version": "2.3",
    "terms_language": "en",
    "terms_text": "# OmniXEP WooCommerce Payment Gateway - Terms of Service\n\n**Version:** 2.3...",
    "terms_file_size": 5234,
    "terms_checksum": "abc123def456",
    "accepted_at": "2026-02-26 14:30:00",
    "accepted_by_email": "admin@example.com",
    "accepted_by_name": "John Doe",
    "accepted_from_ip": "192.168.1.100"
  }
}
```

**Response (Not Found):**
```json
{
  "success": false,
  "error": "No terms acceptance found for this merchant",
  "code": "TERMS_NOT_FOUND",
  "merchant_id": "5d41402abc4b2a76b9719d911017c592"
}
```

---

### Endpoint 2: Get Terms by Site URL

**URL:** `GET /api/terms?site_url={site_url}`

**Request:**
```http
GET /api/terms?site_url=https://example.com HTTP/1.1
Host: api.planc.space
X-OmniXEP-Source: Admin-Panel
```

**Response:** Same as Endpoint 1

---

### Endpoint 3: Get Latest Terms for Merchant

**URL:** `GET /api/terms/latest?merchant_id={merchant_id}`

**Purpose:** Mağazanın en son kabul ettiği sözleşmeyi getirir (birden fazla acceptance varsa)

**Request:**
```http
GET /api/terms/latest?merchant_id=5d41402abc4b2a76b9719d911017c592 HTTP/1.1
Host: api.planc.space
X-OmniXEP-Source: Admin-Panel
```

**Response:**
```json
{
  "success": true,
  "data": {
    "merchant_id": "5d41402abc4b2a76b9719d911017c592",
    "site_url": "https://example.com",
    "terms_version": "2.3",
    "terms_text": "# OmniXEP WooCommerce Payment Gateway - Terms of Service...",
    "accepted_at": "2026-02-26 16:00:00",
    "acceptance_count": 3,
    "all_acceptances": [
      {
        "accepted_at": "2026-02-26 16:00:00",
        "terms_version": "2.3",
        "accepted_by": "admin@example.com"
      },
      {
        "accepted_at": "2026-02-26 14:30:00",
        "terms_version": "2.3",
        "accepted_by": "admin@example.com"
      },
      {
        "accepted_at": "2026-02-25 10:00:00",
        "terms_version": "2.0",
        "accepted_by": "admin@example.com"
      }
    ]
  }
}
```

---

## Implementation (Node.js/Express)

### Route Handler:

```javascript
// Get terms by merchant_id or site_url
app.get('/api/terms', async (req, res) => {
    try {
        const { merchant_id, site_url } = req.query;
        
        if (!merchant_id && !site_url) {
            return res.status(400).json({
                success: false,
                error: 'merchant_id or site_url is required',
                code: 'MISSING_PARAMETER'
            });
        }
        
        let query = 'SELECT * FROM omnixep_terms_acceptances WHERE ';
        let params = [];
        
        if (merchant_id) {
            query += 'merchant_id = ?';
            params.push(merchant_id);
        } else {
            query += 'site_url = ?';
            params.push(site_url);
        }
        
        query += ' ORDER BY accepted_at DESC LIMIT 1';
        
        const results = await db.query(query, params);
        
        if (results.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'No terms acceptance found for this merchant',
                code: 'TERMS_NOT_FOUND',
                merchant_id: merchant_id || null,
                site_url: site_url || null
            });
        }
        
        const terms = results[0];
        
        res.json({
            success: true,
            data: {
                merchant_id: terms.merchant_id,
                site_url: terms.site_url,
                site_name: terms.site_name,
                terms_version: terms.terms_version,
                terms_language: terms.terms_language || 'en',
                terms_text: terms.terms_text,
                terms_file_size: terms.terms_file_size,
                terms_checksum: terms.terms_checksum,
                accepted_at: terms.accepted_at,
                accepted_by_email: terms.accepted_by_email,
                accepted_by_name: terms.accepted_by_name,
                accepted_from_ip: terms.accepted_from_ip
            }
        });
        
    } catch (error) {
        console.error('Terms Retrieval Error:', error);
        res.status(500).json({
            success: false,
            error: 'Internal server error',
            code: 'INTERNAL_ERROR'
        });
    }
});

// Get latest terms with all acceptance history
app.get('/api/terms/latest', async (req, res) => {
    try {
        const { merchant_id } = req.query;
        
        if (!merchant_id) {
            return res.status(400).json({
                success: false,
                error: 'merchant_id is required',
                code: 'MISSING_PARAMETER'
            });
        }
        
        // Get latest acceptance
        const latest = await db.query(
            'SELECT * FROM omnixep_terms_acceptances WHERE merchant_id = ? ORDER BY accepted_at DESC LIMIT 1',
            [merchant_id]
        );
        
        if (latest.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'No terms acceptance found for this merchant',
                code: 'TERMS_NOT_FOUND',
                merchant_id: merchant_id
            });
        }
        
        // Get all acceptances for history
        const allAcceptances = await db.query(
            'SELECT accepted_at, terms_version, accepted_by_email, accepted_by_name FROM omnixep_terms_acceptances WHERE merchant_id = ? ORDER BY accepted_at DESC',
            [merchant_id]
        );
        
        const terms = latest[0];
        
        res.json({
            success: true,
            data: {
                merchant_id: terms.merchant_id,
                site_url: terms.site_url,
                site_name: terms.site_name,
                terms_version: terms.terms_version,
                terms_language: terms.terms_language || 'en',
                terms_text: terms.terms_text,
                terms_file_size: terms.terms_file_size,
                terms_checksum: terms.terms_checksum,
                accepted_at: terms.accepted_at,
                accepted_by_email: terms.accepted_by_email,
                accepted_by_name: terms.accepted_by_name,
                accepted_from_ip: terms.accepted_from_ip,
                acceptance_count: allAcceptances.length,
                all_acceptances: allAcceptances.map(a => ({
                    accepted_at: a.accepted_at,
                    terms_version: a.terms_version,
                    accepted_by: a.accepted_by_email
                }))
            }
        });
        
    } catch (error) {
        console.error('Terms Retrieval Error:', error);
        res.status(500).json({
            success: false,
            error: 'Internal server error',
            code: 'INTERNAL_ERROR'
        });
    }
});
```

---

## Database Query Examples

### Get Terms by Merchant ID:
```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE merchant_id = '5d41402abc4b2a76b9719d911017c592'
ORDER BY accepted_at DESC 
LIMIT 1;
```

### Get Terms by Site URL:
```sql
SELECT * FROM omnixep_terms_acceptances 
WHERE site_url = 'https://example.com'
ORDER BY accepted_at DESC 
LIMIT 1;
```

### Get All Acceptances for Merchant:
```sql
SELECT 
    accepted_at,
    terms_version,
    accepted_by_email,
    accepted_by_name,
    accepted_from_ip
FROM omnixep_terms_acceptances 
WHERE merchant_id = '5d41402abc4b2a76b9719d911017c592'
ORDER BY accepted_at DESC;
```

### Count Acceptances by Merchant:
```sql
SELECT 
    merchant_id,
    site_url,
    site_name,
    COUNT(*) as acceptance_count,
    MAX(accepted_at) as latest_acceptance
FROM omnixep_terms_acceptances 
WHERE merchant_id = '5d41402abc4b2a76b9719d911017c592'
GROUP BY merchant_id, site_url, site_name;
```

---

## Frontend Integration (Admin Panel)

### JavaScript Example:

```javascript
// Function to fetch terms for a merchant
async function fetchMerchantTerms(merchantId) {
    try {
        const response = await fetch(
            `https://api.planc.space/api/terms?merchant_id=${merchantId}`,
            {
                method: 'GET',
                headers: {
                    'X-OmniXEP-Source': 'Admin-Panel'
                }
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            // Display terms in modal
            displayTermsModal(result.data);
        } else {
            // Show error message
            showError('Bu mağaza için kayıtlı sözleşme metni bulunmuyor.');
        }
        
    } catch (error) {
        console.error('Terms fetch error:', error);
        showError('Sözleşme yüklenirken hata oluştu.');
    }
}

// Function to display terms in modal
function displayTermsModal(termsData) {
    const modal = document.getElementById('termsModal');
    const termsContent = document.getElementById('termsContent');
    
    // Convert markdown to HTML (or use a library like marked.js)
    const termsHtml = convertMarkdownToHtml(termsData.terms_text);
    
    termsContent.innerHTML = `
        <div class="terms-header">
            <h2>Sözleşme Ön İzleme</h2>
            <p>Versiyon: ${termsData.terms_version}</p>
            <p>Kabul Tarihi: ${termsData.accepted_at}</p>
            <p>Kabul Eden: ${termsData.accepted_by_name} (${termsData.accepted_by_email})</p>
        </div>
        <div class="terms-body">
            ${termsHtml}
        </div>
    `;
    
    modal.style.display = 'block';
}

// Convert markdown to HTML (basic)
function convertMarkdownToHtml(markdown) {
    return markdown
        .replace(/^# (.+)$/gm, '<h1>$1</h1>')
        .replace(/^## (.+)$/gm, '<h2>$1</h2>')
        .replace(/^### (.+)$/gm, '<h3>$1</h3>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/^- (.+)$/gm, '<li>$1</li>')
        .replace(/\n\n/g, '</p><p>')
        .replace(/^(.+)$/gm, '<p>$1</p>');
}
```

### React Example:

```jsx
import React, { useState, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';

function TermsModal({ merchantId, isOpen, onClose }) {
    const [termsData, setTermsData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    
    useEffect(() => {
        if (isOpen && merchantId) {
            fetchTerms();
        }
    }, [isOpen, merchantId]);
    
    const fetchTerms = async () => {
        setLoading(true);
        setError(null);
        
        try {
            const response = await fetch(
                `https://api.planc.space/api/terms?merchant_id=${merchantId}`,
                {
                    headers: {
                        'X-OmniXEP-Source': 'Admin-Panel'
                    }
                }
            );
            
            const result = await response.json();
            
            if (result.success) {
                setTermsData(result.data);
            } else {
                setError('Bu mağaza için kayıtlı sözleşme metni bulunmuyor.');
            }
        } catch (err) {
            setError('Sözleşme yüklenirken hata oluştu.');
        } finally {
            setLoading(false);
        }
    };
    
    if (!isOpen) return null;
    
    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-content" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>Sözleşme Ön İzleme</h2>
                    <button onClick={onClose}>×</button>
                </div>
                
                <div className="modal-body">
                    {loading && <p>Yükleniyor...</p>}
                    
                    {error && (
                        <div className="error-message">
                            <p>{error}</p>
                        </div>
                    )}
                    
                    {termsData && (
                        <>
                            <div className="terms-info">
                                <p><strong>Versiyon:</strong> {termsData.terms_version}</p>
                                <p><strong>Kabul Tarihi:</strong> {termsData.accepted_at}</p>
                                <p><strong>Kabul Eden:</strong> {termsData.accepted_by_name} ({termsData.accepted_by_email})</p>
                                <p><strong>Mağaza:</strong> {termsData.site_name} ({termsData.site_url})</p>
                            </div>
                            
                            <div className="terms-text">
                                <ReactMarkdown>{termsData.terms_text}</ReactMarkdown>
                            </div>
                        </>
                    )}
                </div>
                
                <div className="modal-footer">
                    <button onClick={onClose}>Kapat</button>
                </div>
            </div>
        </div>
    );
}

export default TermsModal;
```

---

## Testing

### cURL Test:

```bash
# Test 1: Get terms by merchant_id
curl -X GET "https://api.planc.space/api/terms?merchant_id=5d41402abc4b2a76b9719d911017c592" \
  -H "X-OmniXEP-Source: Admin-Panel"

# Test 2: Get terms by site_url
curl -X GET "https://api.planc.space/api/terms?site_url=https://example.com" \
  -H "X-OmniXEP-Source: Admin-Panel"

# Test 3: Get latest terms with history
curl -X GET "https://api.planc.space/api/terms/latest?merchant_id=5d41402abc4b2a76b9719d911017c592" \
  -H "X-OmniXEP-Source: Admin-Panel"
```

---

## Security Considerations

### 1. Authentication:
```javascript
// Add API key or JWT authentication
app.get('/api/terms', authenticateRequest, async (req, res) => {
    // ... handler code
});
```

### 2. Rate Limiting:
```javascript
const rateLimit = require('express-rate-limit');

const termsLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // 100 requests per window
    message: 'Too many requests'
});

app.get('/api/terms', termsLimiter, async (req, res) => {
    // ... handler code
});
```

### 3. Input Validation:
```javascript
function validateMerchantId(merchantId) {
    // MD5 hash is 32 characters
    return /^[a-f0-9]{32}$/.test(merchantId);
}

function validateSiteUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}
```

---

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| MISSING_PARAMETER | merchant_id or site_url required | 400 |
| INVALID_MERCHANT_ID | Invalid merchant_id format | 400 |
| INVALID_SITE_URL | Invalid site_url format | 400 |
| TERMS_NOT_FOUND | No terms found for merchant | 404 |
| INTERNAL_ERROR | Server error | 500 |

---

## Summary

Bu endpoint'i ekledikten sonra:

1. ✅ Panel'den mağaza sözleşmesi görüntülenebilir
2. ✅ Kabul tarihi ve kullanıcı bilgileri gösterilir
3. ✅ Sözleşme metni tam olarak okunabilir
4. ✅ Geçmiş acceptance kayıtları listelenebilir

---

**Version:** 1.0  
**Last Updated:** February 26, 2026  
**Author:** XEPMARKET & Ceyhun Yılmaz

---

© 2026 XEPMARKET. All Rights Reserved.
