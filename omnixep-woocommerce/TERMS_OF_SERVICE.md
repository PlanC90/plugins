OMNIXEP / ELECTRAPAY
WooCommerce Payment Plugin - Terms of Service & Technical Overview
Version: Final
Effective 23/03/2026

BY INSTALLING, ACTIVATING, OR USING THE SOFTWARE (“OMNIXEP WOOCOMMERCE PAYMENT PLUGIN”), YOU (“MERCHANT”) AGREE TO BE BOUND BY THESE TERMS OF SERVICE (“AGREEMENT”). IF YOU DO NOT AGREE, DO NOT INSTALL OR USE THE SOFTWARE.

Acceptance of this Agreement is recorded electronically through click-wrap confirmation and may include timestamp, IP address, plugin version, browser or device information, and agreement version.

### 1. PARTIES
1.1 Developer / Service Provider: The entity providing the OmniXEP / ElectraPay software.
1.2 Merchant / User: The individual or legal entity installing and using the Software within a WooCommerce environment.

### 2. DEFINITIONS
XEP: Native cryptocurrency of the ElectraProtocol blockchain network, if applicable.
Plugin / Gateway: The OmniXEP WooCommerce payment gateway software.
Blockchain: A decentralized public ledger recording cryptocurrency transactions.
API: A metadata synchronization service used for reporting transactions and commissions.
Merchant Wallet: The wallet address configured by the Merchant to receive customer payments.
Fee Wallet: A separate wallet used exclusively for commission settlement.
Auto-Pilot: Optional automated commission settlement mechanism operating locally within the Merchant browser environment.
Complaint Form: A reporting interface allowing customers to submit payment-related complaints.
Restricted Jurisdiction: Any jurisdiction where the use, offering, promotion, availability, or operation of the Software is prohibited, restricted, sanctioned, or would require a licence, registration, authorisation, approval, or regulatory clearance not independently obtained by Merchant.

### 3. ROLE OF THE DEVELOPER
The Developer provides technical software only. The Developer:
Does NOT receive customer payments.
Does NOT hold or custody funds.
Does NOT control private keys.
Does NOT operate as a payment processor.
Does NOT act as a financial intermediary.
Does NOT provide money transmission services.
Does NOT provide electronic money services.
Does NOT provide cryptocurrency exchange services.
Does NOT provide cryptocurrency exchange services.
Does NOT convert cryptocurrency to fiat.
All payments occur directly between the Customer wallet and the Merchant wallet via the blockchain.

### 4. REGULATORY DISCLAIMER
The Software is not a financial institution, payment processor, money transmitter, or electronic money institution. The Software functions solely as a technical interface enabling peer-to-peer blockchain payments. The Merchant is solely responsible for determining whether cryptocurrency payments are lawful within each jurisdiction in which Merchant operates or targets customers. Merchant shall not use, market, enable, distribute, or make the Software available in any Restricted Jurisdiction.

### 5. SOFTWARE TOOL DISCLAIMER
The Software functions purely as a technical tool and does not route, hold, transmit funds, or control payment transactions. All transfers occur directly between customer and merchant wallets on the blockchain. Any payment-related activity occurring through the Software is executed by users through blockchain infrastructure and user-controlled wallets, not by the Developer.

### 6. COMPLIANCE ARCHITECTURE
The Software is intentionally designed as a non-custodial architecture. Core principles include wallet-to-wallet payments, merchant-controlled private keys, client-side transaction signing, and no developer custody of funds. The Merchant is solely responsible for legal, tax, regulatory, licensing, sanctions, AML, consumer protection, and compliance obligations relating to Merchant’s business and use of the Software.

### 7. NON-CUSTODIAL ARCHITECTURE
Private keys and mnemonic phrases remain exclusively under Merchant control. The Developer cannot access merchant wallets, recover private keys, freeze funds, or reverse blockchain transactions. All transaction signing occurs locally. Developer has no ability, whether legal, technical, practical, or discretionary, to access, hold, custody, freeze, transmit, redirect, reverse, settle, or safeguard customer or merchant funds at any stage.

### 8. MERCHANT TRANSACTION CONTROL
Transactions are initiated exclusively by the Customer or the Merchant environment. The Developer cannot initiate, cancel, modify, or reverse blockchain transactions. The Merchant acknowledges that the Developer has no authority and no practical means to control the execution, timing, routing, or finality of blockchain transactions.

### 9. SYSTEM ARCHITECTURE
The Software may include WooCommerce PHP gateway modules, JavaScript wallet integration, blockchain verification modules, commission settlement logic, metadata synchronization APIs, and automated complaint form modules. The Software may also include diagnostics tools, merchant dashboards, reporting tools, support interfaces, notification systems, and update services. The Developer may modify, update, suspend, replace, or discontinue any component of the Software in accordance with this Agreement.

### 10. PAYMENT FLOW
Customer → Checkout → Payment Sent → Blockchain Broadcast → TXID Detection → Order Status Update.
The Merchant acknowledges that actual payment flow may depend on blockchain conditions, wallet behavior, external infrastructure, hosting configuration, WooCommerce configuration, user action, and third-party dependencies.

### 11. PAYMENT VERIFICATION & DOUBLE-SPEND PROTECTION
Verification may include recipient address validation, payment amount validation, and confirmation checks. The Developer is not responsible for double-spend attacks, mempool conflicts, or blockchain reorganizations. Merchants determine confirmation thresholds. No verification mechanism is perfect, and the Developer shall not be liable for failed, delayed, conflicting, duplicated, fraudulent, or disputed transactions arising from blockchain conditions, user conduct, customer error, wallet error, external infrastructure, or third-party software.

### 12. BLOCKCHAIN FINALITY
Once confirmed, blockchain transactions become irreversible. Network stability is outside Developer control. The Developer is not responsible for validator behavior, miner behavior, chain forks, congestion, outages, delayed confirmations, mempool conditions, or other blockchain-level events.

### 13. ORDER FULFILLMENT RESPONSIBILITY
The Developer is not involved in product sales, product delivery, refunds, or customer disputes. All obligations remain with the Merchant. This includes product quality, merchant-customer communications, returns, support, tax collection, consumer claims, chargebacks, and any similar merchant-side obligations.

### 14. COMMISSION STRUCTURE
The Software may charge a technical license fee (default 0.8%). Customer payments go 100% directly to the Merchant Wallet. Commissions are paid separately via the Fee Wallet. The Software may also involve subscription fees, usage fees, or other technical charges as separately communicated by the Developer.

### 15. TECHNICAL COMMISSION WALLET ARCHITECTURE
The Software may use a Fee Wallet for commission settlement. The Fee Wallet is generated or imported by the Merchant and remains under Merchant control; private keys are never accessible to the Developer. The Software calculates commission, which is paid via a separate transaction from the Fee Wallet. Merchant remains solely responsible for verifying fee settings and payment destinations before live use.

### 16. CLIENT-SIDE TRANSACTION SIGNING
All blockchain transactions are signed exclusively in the Merchant’s local environment using the Merchant browser, a local JavaScript wallet engine, and encrypted mnemonic data. The Developer never receives private keys, mnemonic phrases, or encrypted wallet data. All signing occurs in the Merchant environment or the applicable user-controlled environment.

### 17. WALLET SECURITY MODEL
Wallet mnemonic data is encrypted using AES-256 encryption. Encrypted wallet data may be stored in browser localStorage. The Developer never receives private keys. Merchant bears sole responsibility for backups, seed phrases, key rotation, access control, device security, wallet recovery procedures, and related operational safeguards.

### 18. BROWSER STORAGE RISK DISCLOSURE
Risks include malicious browser extensions, compromised WordPress installations, XSS attacks, and insecure hosting environments. Merchant assumes responsibility for environment security. Merchant further assumes sole responsibility for device compromise, malware, unauthorized administrative access, poor internal security, and third-party plugin conflicts.

### 19. SERVER SECURITY RESPONSIBILITY
Merchant assumes responsibility for risks arising from unlicensed plugins, malicious scripts, insecure server configurations, or unknown third-party software. Merchant is solely responsible for the security of its servers, hosting environment, APIs, browser environment, WordPress installation, plugins, scripts, and devices.

### 20. BLOCKCHAIN NETWORK RISKS
Blockchain networks may experience congestion, transaction delays, mempool conflicts, or chain reorganizations. Developer is not responsible for network conditions. Merchant acknowledges that blockchain networks, mempool conditions, validator or miner behavior, node availability, third-party RPC services, indexing latency, chain reorganizations, congestion, outages, forks, and other infrastructure conditions may affect transaction visibility, timing, confirmation status, order-status synchronization, and related reporting.

### 21. MERCHANT RESPONSIBILITIES
Merchant is responsible for legal compliance, lawful product sales, tax obligations, maintaining backups, and intellectual property compliance. Merchant is also solely responsible for determining whether its installation, offering, promotion, and use of the Software is lawful in each jurisdiction in which it operates or targets customers, and for obtaining and maintaining all necessary licences, registrations, disclosures, approvals, notices, policies, procedures, and internal controls.

### 22. COMPLAINT MANAGEMENT SYSTEM
A complaint form may automatically appear on Merchant stores. If complaints occur, Merchant receives admin notification and must respond within 48 hours. Unresolved complaints may result in software-level restrictions, access limitations, support limitations, dashboard restrictions, API limitations, or non-essential feature suspensions. Such measures shall not be interpreted as Developer taking possession or control of any funds.

### 23. PROHIBITED USE
The Software may not be used for illegal goods, counterfeit products, fraud schemes, or intellectual property violations. The Software may also not be used for prohibited services, sanctions evasion, deceptive conduct, unauthorized financial services, or any unlawful business activity. The Developer may determine, acting reasonably, that a category of use presents unacceptable legal, regulatory, sanctions-related, technical, fraud, abuse, reputational, or operational risk and may prohibit or discontinue such use.

### 24. PLUGIN KILL-SWITCH MECHANISM
In cases of critical security threats or regulatory obligations, the Developer may activate a software security control mechanism (Kill-Switch). This may include temporarily disabling functions, restricting API endpoints, or requiring security updates. This mechanism does not provide access to merchant funds. Such actions may be taken where reasonably necessary for security, legal requirements, sanctions, compliance, abuse prevention, fraud prevention, system integrity, infrastructure protection, or risk management.

### 25. API METADATA USAGE
API metadata is used only for commission reconciliation, fraud prevention, technical reporting, security monitoring, and merchant performance analytics. API data is not used to control financial transactions, access customer funds, or redirect payments. Metadata may also be used for abuse detection, support diagnostics, operational analytics, merchant analytics, and service integrity.

### 26. THIRD-PARTY DEPENDENCIES
The Software may rely on blockchain nodes, hosting infrastructure, or APIs. Developer is not responsible for failures of these systems. This includes node failures, third-party API failures, cloud outages, RPC issues, infrastructure failures, internet disruptions, and other external dependencies outside Developer’s reasonable control.

### 27. DATA INTEGRITY
Merchant must maintain backups of WooCommerce data. Plugin logs, WooCommerce data, dashboards, notifications, API metadata, and internal reports are secondary technical records only and may be incomplete, delayed, or inconsistent.

### 28. MERCHANT INDEMNIFICATION
Merchant agrees to indemnify the Developer against claims arising from merchant business activities, illegal sales, regulatory violations, or customer disputes. Merchant shall defend, indemnify, and hold harmless the Developer, its affiliates, officers, directors, employees, contractors, licensors, and successors from and against any and all claims, demands, actions, investigations, proceedings, losses, damages, liabilities, penalties, fines, costs, and expenses, including reasonable legal fees, arising out of or relating to:
(i) Merchant’s business activities;
(ii) Merchant’s products, services, marketing, or customer relationships;
(iii) Merchant’s breach of this Agreement;
(iv) Merchant’s violation of any law, regulation, sanctions regime, licensing requirement, consumer protection rule, or tax obligation;
(v) use of the Software in any Restricted Jurisdiction; or
(vi) any allegation that Merchant’s goods, services, content, statements, or conduct infringes third-party rights or causes harm to any third party.

### 29. LIMITATION OF LIABILITY
Maximum Developer liability is limited to the license fees paid during the previous 30 days OR USD 100, whichever is lower. To the maximum extent permitted by applicable law, the Developer shall not be liable for any indirect, incidental, special, exemplary, punitive, or consequential damages, including loss of profits, revenue, business, goodwill, anticipated savings, data, or business opportunity, even if advised of the possibility of such damages.

### 30. SOFTWARE PROVIDED “AS IS”
The Software is provided without warranties. The Software is provided on an “as is,” “as available,” and “with all faults” basis. To the maximum extent permitted by applicable law, the Developer disclaims all representations, warranties, and conditions, whether express, implied, statutory, or otherwise, including implied warranties of merchantability, fitness for a particular purpose, title, non-infringement, uninterrupted availability, accuracy, security, or error-free operation.

### 31. NO PARTNERSHIP
This Agreement does not create a partnership, joint venture, or agency. Nothing in this Agreement creates or shall be construed as creating any fiduciary relationship, employment relationship, franchise, or representative relationship between the parties. Merchant shall not represent itself as authorized to bind the Developer unless expressly authorized in writing.

### 32. FORCE MAJEURE
Developer is not liable for blockchain outages, cyberattacks, infrastructure failures, or natural disasters. Developer shall also not be liable for node failures, hosting failures, internet disruptions, third-party API failures, cloud outages, sanctions events, or any other circumstances beyond Developer’s reasonable control.

### 33. MERCHANT WALLET CONFIGURATION RESPONSIBILITY
The Merchant must configure a Merchant Wallet Address to receive customer payments and a Fee Wallet Address to pay commissions. These addresses must be different. Developer is not responsible for incorrect wallet setup, misdirected funds, or commission payment failures. The Merchant must independently verify all wallet addresses, fee wallet settings, network settings, and related configuration details before production or live use.

### 34. TRANSACTION DETECTION LIMITATION
WooCommerce order updates rely on blockchain detection. Due to asynchronous systems, orders may not always update automatically. Merchant must verify payments directly on the blockchain when necessary. The Developer does not guarantee that every valid blockchain transaction will automatically update a WooCommerce order or related record.

### 35. BLOCKCHAIN SOURCE OF TRUTH
Blockchain data is the ultimate source of truth for payment verification. WooCommerce records and plugin logs are secondary technical records. For all technical, transactional, reconciliation, accounting-support, and dispute-related purposes, the relevant blockchain record shall constitute the primary and controlling source of truth.

### 36. REGULATORY CLASSIFICATION DISCLAIMER
The Software does not perform payment processing, money transmission, custody services, or financial intermediation. The Software is distributed solely as a technical software product. The Developer does not provide legal, regulatory, licensing, sanctions, tax, AML, accounting, or compliance advice, and the Merchant must independently assess its own regulatory position.

### 37. MERCHANT INDEPENDENCE AND NO PAYMENT NETWORK
The Software does not create or operate a payment network. Each Merchant operates independently within their own WooCommerce environment. The Developer does not operate a centralized payment infrastructure. All transactions occur directly between customers and merchants via public blockchain networks.

### 38. CLASS ACTION WAIVER
All disputes must be brought individually. Merchants waive participation in class-action lawsuits, collective claims, representative actions, or similar aggregate proceedings to the maximum extent permitted by applicable law.

### 39. ACCEPTANCE RECORD
Acceptance records may include timestamp, IP address, plugin version, and agreement version. They may also include browser or device information, dashboard confirmation data, API logging, or version acceptance flow records, and may be used as evidence of acceptance to the fullest extent permitted by applicable law.

### 40. GOVERNING LAW
This Agreement is governed by the laws of the Republic of Türkiye. All disputes shall be resolved exclusively in the Courts of Istanbul, unless the parties expressly agree otherwise in writing.

### 41. TECHNICAL COMMISSION WALLET ARCHITECTURE (DETAILED)
The Software may use a separate Fee Wallet for commission settlement. This wallet is generated or imported by the Merchant and remains solely under Merchant control; private keys are not accessible to the Developer. Commission settlement operates as follows: customer payments are sent directly to the Merchant Wallet; the Software calculates the commission amount only; commission payments are executed separately through the Fee Wallet. All commission transactions are initiated within the Merchant browser environment, signed locally on the client-side, and broadcast by the Merchant environment. Merchant remains solely responsible for verifying fee settings, fee wallet configuration, and payment destinations before live use.

### 42. CLIENT-SIDE TRANSACTION SIGNING (DETAILED)
All blockchain transactions generated by the Software are signed exclusively within the Merchant’s local environment. Signing occurs using the Merchant browser environment, the local JavaScript wallet engine, and encrypted mnemonic data stored locally. The Developer never receives private keys, mnemonic phrases, or encrypted wallet data. All transaction signing remains exclusively under Merchant or relevant user control.

### 43. MERCHANT WALLET CONFIGURATION RESPONSIBILITY (REITERATION)
The Merchant must configure two separate wallet addresses: Merchant Wallet Address and Fee Wallet Address. These addresses must not be identical. The Merchant is solely responsible for verifying wallet configuration before enabling the payment system. The Developer is not responsible for incorrect configuration, misdirected funds, or accounting discrepancies. Merchant also bears sole responsibility for backups, seed phrases, key rotation, access control, device security, wallet recovery procedures, and related operational safeguards.

### 44. TRANSACTION DETECTION LIMITATION (DETAILED)
The Software relies on blockchain transaction detection mechanisms to update WooCommerce order status. Due to asynchronous server behavior and blockchain indexing delays, the Developer does not guarantee that every valid blockchain transaction will automatically update a WooCommerce order. Possible causes include server outages, plugin conflicts, hosting limitations, API interruptions, third-party node issues, wallet behavior, or infrastructure delays. Merchant must independently verify on-chain transactions whenever necessary.

### 45. BLOCKCHAIN SOURCE OF TRUTH (REITERATION)
The blockchain network is the ultimate source of truth for payment verification. WooCommerce order data, plugin logs, and API responses are considered secondary records. In case of discrepancies, blockchain transaction data shall prevail. This shall apply to technical, transactional, reconciliation, accounting-support, and dispute-related matters.

### 46. PLUGIN KILL-SWITCH MECHANISM (DETAILED)
In cases of critical security threats or regulatory compliance requirements, the Developer reserves the right to activate a software security control mechanism (“Kill-Switch”). This may include temporarily disabling features, restricting API endpoints, or requiring mandatory security updates. This does not provide access to Merchant funds. Such action may be taken where reasonably necessary for security, legal requirements, sanctions, compliance, abuse prevention, fraud prevention, system integrity, infrastructure protection, or risk management, including urgent action without prior notice where reasonably required.

### 47. API METADATA USAGE (DETAILED)
Metadata transmitted through the API may be used only for commission reconciliation, security monitoring, fraud prevention, technical reporting, and merchant analytics. API data does not control transactions, access funds, or redirect payments. Metadata may also be used for abuse detection, support diagnostics, operational analytics, merchant performance analytics, and service integrity, but shall not grant the Developer custody of funds or control over transactions.

### 48. REGULATORY CLASSIFICATION DISCLAIMER (DETAILED)
The Software does not perform payment processing, money transmission, custody services, or financial intermediation. It shall not be classified as a payment system or financial platform. It is distributed solely as a technical software product. The Merchant acknowledges that it is solely responsible for determining the legality and regulatory treatment of its use of the Software in all relevant jurisdictions.

### 49. MERCHANT INDEPENDENCE AND NO PAYMENT NETWORK (REITERATION)
The Software does not create or operate a payment network. Each Merchant installs and operates the Software independently. The Developer does not manage a shared payment infrastructure. The Merchant shall not market, describe, or present the Software, the Plugin, or the Developer as “licensed,” “regulated,” “government-approved,” “bank-grade compliant,” “approved payment processor,” or any similar statement suggesting that the Developer is a regulated financial institution or licensed crypto-asset service provider, unless expressly approved in writing by the Developer.

### 50. SOFTWARE SECURITY UPDATE REQUIREMENT
The Merchant agrees to install critical software updates when required for security or regulatory compliance. Failure to do so may result in temporary suspension of plugin functionality. Merchant shall promptly implement all security, compatibility, and compliance-related updates, patches, and technical instructions issued by the Developer. Failure may result in restricted functionality, suspension of support, incompatibility, increased security risk, or temporary disabling of certain Software features.

### 51. BLOCKCHAIN DATA DISCLAIMER
The Developer does not guarantee the accuracy or availability of blockchain data obtained through third-party APIs or nodes. Blockchain data services may experience delays or outages beyond the Developer’s control. Merchant acknowledges that third-party infrastructure, RPC services, indexers, and blockchain data providers may affect visibility, timing, and reporting accuracy.

### 52. INTELLECTUAL PROPERTY & ASSIGNMENT
The Software remains the exclusive intellectual property of the Developer. The Developer reserves the right to transfer or assign all rights, including merchant metadata and the software license, to any third-party acquirer without prior Merchant consent. All right, title, and interest in and to the Software, Plugin, source code, object code, interfaces, documentation, branding, know-how, design elements, updates, modifications, and related intellectual property shall remain exclusively with the Developer and/or its licensors. Except for the limited right to use the Software in accordance with this Agreement, no licence, transfer, assignment, or ownership right is granted to Merchant. Merchant shall not reverse engineer, decompile, disassemble, copy, create derivative works from, sublicence, resell, redistribute, or exploit the Software except as expressly permitted by the Developer in writing.
