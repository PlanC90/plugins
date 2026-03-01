# Terms of Service Update - Version 2.3

**Update Date:** February 26, 2026  
**Previous Version:** 2.0.0  
**New Version:** 2.3  
**Status:** ✅ ACTIVE

---

## 🔄 What Changed

### Major Updates:

#### 1. **Developer Information Updated**
- **Old:** Ceyhun Oykukaren
- **New:** XEPMARKET & Ceyhun Yılmaz
- **Reason:** Proper business entity representation

#### 2. **Clarified Service Nature**
- ✅ **Emphasized:** Plugin is SOFTWARE ONLY
- ✅ **Clarified:** NOT a payment processor
- ✅ **Highlighted:** NO custody of funds
- ✅ **Stated:** Developer NEVER has access to private keys

#### 3. **Simplified Structure**
- **Old:** 17 detailed sections
- **New:** 16 concise sections
- **Benefit:** Easier to read and understand

#### 4. **Enhanced Key Points**

**Section 2.2 - NO PAYMENT PROCESSING:**
```
The Plugin does NOT:
- Process customer payments
- Hold, custody, or control customer funds
- Transmit funds on behalf of merchants
- Settle merchant funds
- Access private keys or mnemonic phrases
```

**Clear Statement:**
> "All customer payments are received directly into the Merchant Wallet."

**Developer Limitation:**
> "The Developer has no technical ability to access, freeze, redirect, delay, or control Merchant funds under any circumstance."

#### 5. **Governing Law Specified**
- **Jurisdiction:** Republic of Türkiye
- **Courts:** Kırklareli Courts and Enforcement Offices (Kırklareli Mahkemeleri ve İcra Daireleri)
- **Reason:** Clear legal jurisdiction for dispute resolution

#### 6. **Commission Clarification**
- **Nature:** Software license and technical service fee
- **Not:** Payment processing fee
- **Benefit:** Clear distinction from regulated payment services

---

## 📋 Key Differences: v2.0.0 vs v2.3

| Aspect | v2.0.0 | v2.3 |
|--------|--------|------|
| **Sections** | 17 sections | 16 sections |
| **Length** | ~8,000 words | ~2,500 words |
| **Focus** | Comprehensive legal | Concise & clear |
| **Developer** | Ceyhun Oykukaren | XEPMARKET & Ceyhun Yılmaz |
| **Service Type** | Implied software | Explicitly SOFTWARE ONLY |
| **Custody** | Mentioned | HEAVILY EMPHASIZED - NO CUSTODY |
| **Jurisdiction** | Generic | Specific - Türkiye/Kırklareli |
| **Commission** | Payment processing fee | Software service fee |
| **Private Keys** | Mentioned once | Emphasized multiple times |

---

## 🎯 Why These Changes Matter

### 1. **Legal Clarity**
- ✅ Clear distinction: Software vs Payment Service
- ✅ Removes any ambiguity about custody
- ✅ Protects from payment processor regulations

### 2. **Regulatory Compliance**
- ✅ Not subject to payment institution licensing
- ✅ Not subject to money transmitter regulations
- ✅ Clear software licensing model

### 3. **User Understanding**
- ✅ Merchants understand: Developer doesn't hold funds
- ✅ Clear: Merchant has full control
- ✅ Transparent: Commission is for software, not payment processing

### 4. **Dispute Resolution**
- ✅ Clear jurisdiction (Türkiye)
- ✅ Specific courts (Kırklareli)
- ✅ No ambiguity in legal proceedings

---

## 🔒 What Stayed the Same

### Unchanged Core Terms:

1. ✅ **0.8% Commission Rate** - Still fixed at 0.8%
2. ✅ **Commission Non-Refundable** - Still final
3. ✅ **Liability Limit** - Still $100 USD or 30 days commission
4. ✅ **Security Responsibility** - Still merchant's responsibility
5. ✅ **Blockchain Disclaimer** - Still comprehensive
6. ✅ **Force Majeure** - Still covers major events
7. ✅ **Termination Rights** - Still immediate for violations
8. ✅ **Data Handling** - Still minimal metadata only
9. ✅ **Survival Clauses** - Still same sections survive

---

## 📊 Impact on Existing Users

### For Merchants Who Already Accepted v2.0.0:

**Action Required:** ✅ YES - Must re-accept v2.3

**Why?**
- Material changes to service description
- New jurisdiction specified
- Enhanced clarity on custody

**What Happens:**
1. Admin notice will appear
2. Gateway will be disabled until re-acceptance
3. Must read and accept new terms
4. New acceptance recorded with v2.3

**Timeline:**
- Immediate upon plugin update
- No grace period (for legal clarity)

### For New Users:

**Action Required:** ✅ Accept v2.3 before first use

**Process:**
1. Install plugin
2. See Terms notice
3. Read Terms v2.3
4. Accept to activate

---

## 🔧 Technical Implementation

### Database Changes:

```php
// Version updated
omnixep_terms_version: '2.0.0' → '2.3'

// New acceptance required
omnixep_terms_accepted: false (until re-accepted)

// New acceptance record
omnixep_terms_accepted_date: [new timestamp]
omnixep_terms_accepted_by: [user ID]
omnixep_terms_accepted_ip: [IP address]
```

### Code Changes:

**File:** `omnixep-woocommerce.php`

```php
// Version check updated
$current_version = '2.3'; // was '2.0.0'

// Acceptance recording updated
update_option('omnixep_terms_version', '2.3'); // was '2.0.0'
```

**File:** `TERMS_OF_SERVICE.md`

```markdown
Version: 2.3 (was 2.0.0)
Developer: XEPMARKET & Ceyhun Yılmaz (was Ceyhun Oykukaren)
Sections: 16 (was 17)
```

---

## 📝 Acceptance Page Updates

### New Acknowledgments Displayed:

```
✅ Software License Only: This is a software tool, not a payment processor
✅ No Custody: Developer never has access to your funds or private keys
✅ 0.8% Commission Fee: Software service fee on all transactions
✅ Security Responsibility: You are solely responsible for securing your mnemonic phrase
✅ Blockchain Risks: Transactions are irreversible and subject to network conditions
✅ Regulatory Compliance: You are responsible for legal and tax compliance
✅ Limited Liability: Maximum liability is $100 USD or 30 days of commission
✅ Governing Law: Republic of Türkiye - Kırklareli Courts
```

### New Checkbox Text:

```
I have read, understood, and agree to be legally bound by the OmniXEP Terms of Service (v2.3). 
I acknowledge that this is a software license only, the Developer does not hold or control my funds, 
and I accept the 0.8% software service fee. I understand that I am solely responsible for wallet security, 
regulatory compliance, and that the Developer's liability is limited to $100 USD. 
I agree that disputes are governed by the laws of the Republic of Türkiye and subject to Kırklareli Courts.
```

---

## 🎓 Best Practices for Merchants

### When Updating:

1. ✅ **Read Carefully** - Review all changes
2. ✅ **Understand Implications** - Especially jurisdiction
3. ✅ **Consult Legal Counsel** - If operating internationally
4. ✅ **Document Acceptance** - Keep record of acceptance
5. ✅ **Update Internal Policies** - Align with new terms

### Ongoing Compliance:

1. ✅ **Monitor Updates** - Check for new versions
2. ✅ **Maintain Records** - Keep transaction logs
3. ✅ **Report Taxes** - Comply with local laws
4. ✅ **Secure Wallets** - Follow security best practices
5. ✅ **Review Periodically** - Re-read terms quarterly

---

## ⚖️ Legal Implications

### For XEPMARKET:

**Benefits:**
- ✅ Clear software licensing model
- ✅ No payment processor liability
- ✅ No custody liability
- ✅ Clear jurisdiction for disputes
- ✅ Enhanced legal protection

**Obligations:**
- ✅ Maintain software functionality
- ✅ Provide technical support
- ✅ Issue monthly invoices
- ✅ Respect merchant wallet control

### For Merchants:

**Benefits:**
- ✅ Full control of funds
- ✅ Clear fee structure
- ✅ No hidden custody risks
- ✅ Transparent service model

**Obligations:**
- ✅ Pay 0.8% commission
- ✅ Secure own wallets
- ✅ Comply with local laws
- ✅ Report taxes
- ✅ Accept Türkiye jurisdiction

---

## 🚀 Deployment Checklist

### For Plugin Update:

- [x] Update TERMS_OF_SERVICE.md to v2.3
- [x] Update version in code to '2.3'
- [x] Update acceptance page acknowledgments
- [x] Update checkbox text
- [x] Test version comparison logic
- [x] Test re-acceptance flow
- [x] Verify database updates
- [x] Document changes

### For Existing Installations:

- [ ] Deploy plugin update
- [ ] Existing users see notice
- [ ] Users must re-accept
- [ ] Gateway disabled until acceptance
- [ ] New acceptance recorded
- [ ] Gateway re-enabled
- [ ] Monitor for issues

---

## 📞 Support

### For Questions About Terms:

**Legal Questions:**
- Email: legal@xepmarket.com
- Consult with qualified attorney in your jurisdiction

**Technical Questions:**
- Email: support@xepmarket.com
- Check documentation

**Jurisdiction Questions:**
- Türkiye law applies
- Kırklareli Courts have jurisdiction
- Consult Turkish legal counsel if needed

---

## 📈 Version History

| Version | Date | Key Changes |
|---------|------|-------------|
| 2.0.0 | Feb 26, 2026 | Initial comprehensive terms |
| 2.3 | Feb 26, 2026 | Clarified software-only nature, added jurisdiction, simplified structure |

---

## ✅ Summary

**Version 2.3 is a significant improvement that:**

1. ✅ Clarifies the Plugin is SOFTWARE ONLY
2. ✅ Emphasizes NO CUSTODY of funds
3. ✅ Specifies clear jurisdiction (Türkiye/Kırklareli)
4. ✅ Simplifies language for better understanding
5. ✅ Maintains all core protections
6. ✅ Enhances legal clarity
7. ✅ Reduces regulatory ambiguity

**Recommendation:** ✅ All users should accept v2.3 immediately

**Status:** ✅ PRODUCTION READY

---

**Last Updated:** February 26, 2026  
**Version:** 2.3  
**Author:** XEPMARKET & Ceyhun Yılmaz

