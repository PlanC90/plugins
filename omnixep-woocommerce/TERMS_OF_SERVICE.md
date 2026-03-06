OMNIXEP / ELECTRAPAY
WooCommerce Payment Plugin - Terms of Service & Technical Overview
Version: 18.1
Effective Date: March 6, 2026

BY INSTALLING, ACTIVATING, OR USING THE SOFTWARE (“OMNIXEP WOOCOMMERCE PAYMENT PLUGIN”), YOU (“MERCHANT”) AGREE TO BE BOUND BY THESE TERMS OF SERVICE (“AGREEMENT”). IF YOU DO NOT AGREE, DO NOT INSTALL OR USE THE SOFTWARE.

Acceptance of this Agreement is recorded electronically through click-wrap confirmation and may include timestamp, IP address, plugin version, and agreement version.

---

### 1. PARTIES
**1.1 Developer / Service Provider:** The entity providing the OmniXEP / ElectraPay software.
**1.2 Merchant / User:** The individual or legal entity installing and using the Software within a WooCommerce environment.

### 2. DEFINITIONS
- **XEP:** Native cryptocurrency of the ElectraProtocol blockchain network.
- **Plugin / Gateway:** The OmniXEP WooCommerce payment gateway software.
- **Blockchain:** A decentralized public ledger recording cryptocurrency transactions.
- **API:** A metadata synchronization service used for reporting transactions and commissions.
- **Merchant Wallet:** The wallet address configured by the Merchant to receive customer payments.
- **Fee Wallet:** A separate wallet used exclusively for commission settlement.
- **Auto-Pilot:** Optional automated commission settlement mechanism operating locally within the Merchant browser environment.
- **Complaint Form:** A reporting interface allowing customers to submit payment-related complaints.

### 3. ROLE OF THE DEVELOPER
The Developer provides technical software only. The Developer:
- Does NOT receive customer payments.
- Does NOT hold or custody funds.
- Does NOT control private keys.
- Does NOT operate as a payment processor.
- Does NOT act as a financial intermediary.
- Does NOT provide money transmission services.
- Does NOT provide electronic money services.
- Does NOT provide cryptocurrency exchange services.
- Does NOT convert cryptocurrency to fiat. All payments occur directly between the Customer wallet and the Merchant wallet via the blockchain.

### 4. REGULATORY DISCLAIMER
The Software is not a financial institution, payment processor, money transmitter, or electronic money institution. The Software functions solely as a technical interface enabling peer-to-peer blockchain payments. The Merchant is solely responsible for determining whether cryptocurrency payments are lawful within their jurisdiction.

### 5. SOFTWARE TOOL DISCLAIMER
The Software functions purely as a technical tool and does not route, hold, transmit funds, or control payment transactions. All transfers occur directly between customer and merchant wallets on the blockchain.

### 6. COMPLIANCE ARCHITECTURE
The Software is intentionally designed as a non-custodial architecture. Core principles include wallet-to-wallet payments, merchant-controlled private keys, client-side transaction signing, and no developer custody of funds.

### 7. NON-CUSTODIAL ARCHITECTURE
Private keys and mnemonic phrases remain exclusively under Merchant control. The Developer cannot access merchant wallets, recover private keys, freeze funds, or reverse blockchain transactions. All transaction signing occurs locally.

### 8. MERCHANT TRANSACTION CONTROL
Transactions are initiated exclusively by the Customer or the Merchant environment. The Developer cannot initiate, cancel, modify, or reverse blockchain transactions.

### 9. SYSTEM ARCHITECTURE
The Software may include WooCommerce PHP gateway modules, JavaScript wallet integration, blockchain verification modules, commission settlement logic, metadata synchronization APIs, and automated complaint form modules.

### 10. PAYMENT FLOW
Customer → Checkout → Payment Sent → Blockchain Broadcast → TXID Detection → Order Status Update.

### 11. PAYMENT VERIFICATION & DOUBLE-SPEND PROTECTION
Verification may include recipient address validation, payment amount validation, and confirmation checks. The Developer is not responsible for double-spend attacks, mempool conflicts, or blockchain reorganizations. Merchants determine confirmation thresholds.

### 12. BLOCKCHAIN FINALITY
Once confirmed, blockchain transactions become irreversible. Network stability is outside Developer control.

### 13. ORDER FULFILLMENT RESPONSIBILITY
The Developer is not involved in product sales, product delivery, refunds, or customer disputes. All obligations remain with the Merchant.

### 14. COMMISSION STRUCTURE
The Software may charge a technical license fee (default 0.8%). Customer payments go 100% directly to the Merchant Wallet. Commissions are paid separately via the Fee Wallet.

### 15. TECHNICAL COMMISSION WALLET ARCHITECTURE
The Software may use a Fee Wallet for commission settlement. The Fee Wallet is generated or imported by the Merchant and remains under Merchant control; private keys are never accessible to the Developer. The Software calculates commission, which is paid via a separate transaction from the Fee Wallet.

### 16. CLIENT-SIDE TRANSACTION SIGNING
All blockchain transactions are signed exclusively in the Merchant’s local environment using the Merchant browser, a local JavaScript wallet engine, and encrypted mnemonic data. The Developer never receives private keys, mnemonic phrases, or encrypted wallet data.

### 17. WALLET SECURITY MODEL
Wallet mnemonic data is encrypted using AES-256 encryption. Encrypted wallet data may be stored in browser localStorage. The Developer never receives private keys.

### 18. BROWSER STORAGE RISK DISCLOSURE
Risks include malicious browser extensions, compromised WordPress installations, XSS attacks, and insecure hosting environments. Merchant assumes responsibility for environment security.

### 19. SERVER SECURITY RESPONSIBILITY
Merchant assumes responsibility for risks arising from unlicensed plugins, malicious scripts, insecure server configurations, or unknown third-party software.

### 20. BLOCKCHAIN NETWORK RISKS
Blockchain networks may experience congestion, transaction delays, mempool conflicts, or chain reorganizations. Developer is not responsible for network conditions.

### 21. MERCHANT RESPONSIBILITIES
Merchant is responsible for legal compliance, lawful product sales, tax obligations, maintaining backups, and intellectual property compliance.

### 22. COMPLAINT MANAGEMENT SYSTEM
A complaint form may automatically appear on Merchant stores. If complaints occur, Merchant receives admin notification and must respond within 48 hours. Unresolved complaints may result in payment suspension.

### 23. PROHIBITED USE
The Software may not be used for illegal goods, counterfeit products, fraud schemes, or intellectual property violations.

### 24. PLUGIN KILL-SWITCH MECHANISM
In cases of critical security threats or regulatory obligations, the Developer may activate a software security control mechanism (Kill-Switch). This may include temporarily disabling functions, restricting API endpoints, or requiring security updates. This mechanism does not provide access to merchant funds.

### 25. API METADATA USAGE
API metadata is used only for commission reconciliation, fraud prevention, technical reporting, security monitoring, and merchant performance analytics. API data is not used to control financial transactions, access customer funds, or redirect payments.

### 26. THIRD-PARTY DEPENDENCIES
The Software may rely on blockchain nodes, hosting infrastructure, or APIs. Developer is not responsible for failures of these systems.

### 27. DATA INTEGRITY
Merchant must maintain backups of WooCommerce data.

### 28. MERCHANT INDEMNIFICATION
Merchant agrees to indemnify the Developer against claims arising from merchant business activities, illegal sales, regulatory violations, or customer disputes.

### 29. LIMITATION OF LIABILITY
Maximum Developer liability is limited to the license fees paid during the previous 30 days OR USD 100, whichever is lower.

### 30. SOFTWARE PROVIDED “AS IS”
The Software is provided without warranties.

### 31. NO PARTNERSHIP
This Agreement does not create a partnership, joint venture, or agency.

### 32. FORCE MAJEURE
Developer is not liable for blockchain outages, cyberattacks, infrastructure failures, or natural disasters.

### 33. MERCHANT WALLET CONFIGURATION RESPONSIBILITY
The Merchant must configure a Merchant Wallet Address to receive customer payments and a Fee Wallet Address to pay commissions. These addresses must be different. Developer is not responsible for incorrect wallet setup, misdirected funds, or commission payment failures.

### 34. TRANSACTION DETECTION LIMITATION
WooCommerce order updates rely on blockchain detection. Due to asynchronous systems, orders may not always update automatically. Merchant must verify payments directly on the blockchain when necessary.

### 35. BLOCKCHAIN SOURCE OF TRUTH
Blockchain data is the ultimate source of truth for payment verification. WooCommerce records and plugin logs are secondary technical records.

### 36. REGULATORY CLASSIFICATION DISCLAIMER
The Software does not perform payment processing, money transmission, custody services, or financial intermediation. The Software is distributed solely as a technical software product.

### 37. MERCHANT INDEPENDENCE AND NO PAYMENT NETWORK
The Software does not create or operate a payment network. Each Merchant operates independently within their own WooCommerce environment. The Developer does not operate a centralized payment infrastructure. All transactions occur directly between customers and merchants via public blockchain networks.

### 38. CLASS ACTION WAIVER
All disputes must be brought individually. Merchants waive participation in class-action lawsuits.

### 39. ACCEPTANCE RECORD
Acceptance records may include timestamp, IP address, plugin version, and agreement version.

### 40. GOVERNING LAW
This Agreement is governed by the laws of the Republic of Türkiye. All disputes shall be resolved exclusively in the Courts of Istanbul.

### 41. TECHNICAL COMMISSION WALLET ARCHITECTURE (DETAILED)
The Software may use a separate Fee Wallet for commission settlement. This wallet is generated or imported by the Merchant and remains solely under Merchant control; private keys are not accessible to the Developer. Commission settlement operates as follows: customer payments are sent directly to the Merchant Wallet; the Software calculates the commission amount only; commission payments are executed separately through the Fee Wallet. All commission transactions are initiated within the Merchant browser environment, signed locally on the client-side, and broadcast by the Merchant environment.

### 42. CLIENT-SIDE TRANSACTION SIGNING (DETAILED)
All blockchain transactions generated by the Software are signed exclusively within the Merchant’s local environment. Signing occurs using the Merchant browser environment, the local JavaScript wallet engine, and encrypted mnemonic data stored locally. The Developer never receives private keys, mnemonic phrases, or encrypted wallet data.

### 43. MERCHANT WALLET CONFIGURATION RESPONSIBILITY (REITERATION)
The Merchant must configure two separate wallet addresses: Merchant Wallet Address and Fee Wallet Address. These addresses must not be identical. The Merchant is solely responsible for verifying wallet configuration before enabling the payment system. The Developer is not responsible for incorrect configuration, misdirected funds, or accounting discrepancies.

### 44. TRANSACTION DETECTION LIMITATION (DETAILED)
The Software relies on blockchain transaction detection mechanisms to update WooCommerce order status. Due to asynchronous server behavior and blockchain indexing delays, the Developer does not guarantee that every valid blockchain transaction will automatically update a WooCommerce order. Possible causes include server outages, plugin conflicts, hosting limitations, or API interruptions.

### 45. BLOCKCHAIN SOURCE OF TRUTH (REITERATION)
The blockchain network is the ultimate source of truth for payment verification. WooCommerce order data, plugin logs, and API responses are considered secondary records. In case of discrepancies, blockchain transaction data shall prevail.

### 46. PLUGIN KILL-SWITCH MECHANISM (DETAILED)
In cases of critical security threats or regulatory compliance requirements, the Developer reserves the right to activate a software security control mechanism (“Kill-Switch”). This may include temporarily disabling features, restricting API endpoints, or requiring mandatory security updates. This does not provide access to Merchant funds.

### 47. API METADATA USAGE (DETAILED)
Metadata transmitted through the API may be used only for commission reconciliation, security monitoring, fraud prevention, technical reporting, and merchant analytics. API data does not control transactions, access funds, or redirect payments.

### 48. REGULATORY CLASSIFICATION DISCLAIMER (DETAILED)
The Software does not perform payment processing, money transmission, custody services, or financial intermediation. It shall not be classified as a payment system or financial platform. It is distributed solely as a technical software product.

### 49. MERCHANT INDEPENDENCE AND NO PAYMENT NETWORK (REITERATION)
The Software does not create or operate a payment network. Each Merchant installs and operates the Software independently. The Developer does not manage a shared payment infrastructure.

### 50. SOFTWARE SECURITY UPDATE REQUIREMENT
The Merchant agrees to install critical software updates when required for security or regulatory compliance. Failure to do so may result in temporary suspension of plugin functionality.

### 51. BLOCKCHAIN DATA DISCLAIMER
The Developer does not guarantee the accuracy or availability of blockchain data obtained through third-party APIs or nodes. Blockchain data services may experience delays or outages beyond the Developer’s control.

### 52. INTELLECTUAL PROPERTY & ASSIGNMENT
The Software remains the exclusive intellectual property of the Developer. The Developer reserves the right to transfer or assign all rights, including merchant metadata and the software license, to any third-party acquirer without prior Merchant consent.
