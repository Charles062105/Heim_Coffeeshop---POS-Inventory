# Heim Specialty Coffee — System Analysis Paper

---

## Methodology

The methodology for the system analysis involves understanding the current workflow of the business, identifying operational problems, and designing a solution that addresses those issues. Data gathering will include interviews with staff members, a review of current operational practices, and observation of daily business transactions. The analysis also considers sales volume, peak-hour operations, staff duties, inventory procedures, and delivery processes.

The proposed system will be developed using a user-centered approach that focuses on the needs of cashiers, managers, and owners. This methodology ensures that the final system supports daily operations, improves transaction efficiency, and provides accurate reports for inventory and sales monitoring.

---

## System Objectives

The primary objective of the proposed system is to improve the efficiency and reliability of Heim Specialty Coffee's daily operations. The system shall allow front-desk staff to record orders accurately, process payments more efficiently, and reduce waiting time for customers. It shall also help staff manage dine-in, takeout, and delivery transactions more systematically.

Another important objective is to improve inventory monitoring and waste control. The system shall automatically update stock quantities whenever products are sold or adjusted, thereby reducing errors in inventory counting and improving visibility of stock shortages or spoilage.

The proposed system also aims to simplify sales reconciliation and reporting. Managers and owners shall be able to generate daily and monthly reports that compare sales, inventory usage, and payment records under a unified system. This will support better business decisions and improve operational oversight.

---

## Scope and Limitations

The proposed system will focus on the core business functions of Heim Specialty Coffee, including order management, beverage preparation records, payment processing, inventory tracking, and daily sales reporting. It will also support multiple branches, as the business has operations in Bangkal and San Rafael.

The system will not include advanced kitchen management, food production analytics, or large-scale enterprise resource planning (ERP) modules. The business's operations are limited to beverage services and delivery support; therefore, the system will be designed according to the actual business needs rather than broader enterprise requirements. The system will also depend on the available infrastructure and staff capability of the business, which may limit the level of automation that can be implemented in the short term.

---

## System Requirements

To support the proposed system, certain functional and non-functional requirements are necessary.

**Functional Requirements:**
- Staff must be able to create and manage orders efficiently.
- Cashiers must be able to open and view order details and available actions. They may record split or partial payments only for orders assigned to them. To process a refund, cancellation, or void, a cashier must provide valid credentials for an active Manager or Owner; showing these actions does not grant cashiers approval privileges.
- The system must track payment transactions and update inventory automatically upon each transaction.
- The system must generate sales reports and support multiple order types — namely, dine-in, takeout, and Grab delivery.
- Managers and owners must be able to view stock levels, approve inventory adjustments, and monitor sales performance by branch and by date.
- The system must store customer transaction details securely and preserve records for future reference.

**Non-Functional Requirements:**
- **Accuracy** — All transaction, inventory, and sales data must be recorded without error.
- **Speed** — The system must perform efficiently during peak business hours.
- **Ease of Use** — The interface must be accessible to employees with varying levels of technical skill.
- **Security** — Access must be restricted to authorized users: Owner, Manager, and Cashier. This measure prevents unauthorized modifications to sales, inventory, or financial data.
- **Data Integrity** — The system must maintain consistent and reliable data at all times, given that payment and sales records are involved.

---

## Technical System Architecture & Key Modules

To deliver the functional requirements with high data integrity and minimal latency, the system is structured into five core integrated subsystems:

### 1. Point of Sale (POS) & Unified Payment Engine
The POS terminal provides high-throughput ordering tailored to peak coffee shop rush hours. Cashiers configure item sizes, temperature variants, and custom modifications (add-ons). 

Payments are handled through two explicit channels:
- **Cash Tender:** Calculates real-time change due based on the amount received.
- **Online Payment:** Enforces digital payment verification by capturing an external transaction reference number (e.g., e-wallet confirmation ID). The system strictly locks checkout until a valid reference number is provided, preventing unverified transactions.

### 2. Bill of Materials (BOM) & Inventory Auto-Deduction Engine
A major failure of manual spreadsheet tracking is the delay between product sales and inventory updates. The system automates inventory deduction at the ingredient level upon order completion:
- **Base Recipe BOM:** Each product size links to precise ingredient quantities (e.g., espresso beans, milk volume, syrups).
- **Dynamic Modifier Deductions:** Custom add-ons (such as extra espresso shots, heavy cream, or alternative milks) automatically trigger corresponding ingredient deductions from the stock ledger.
- **Transaction Ledger:** Every deduction generates an auditable `sales_consumption` record tied to the specific order number.

### 3. Stock Management & Waste Logging Subsystem
In addition to sales deductions, physical inventory is tracked through dedicated lifecycle workflows:
- **Stock-In Operations:** Records incoming deliveries from suppliers with batch and cost tracking.
- **Waste & Spoilage Logging:** Tracks spilled drinks, spoiled dairy, and expired beans with mandatory operational reason codes to calculate shrinkage.
- **Physical Count Adjustments:** Enables managers and owners to reconcile discrepancy variances between physical count and system-calculated stock.

### 4. Role-Based Authorization & Security Audit Trail
To protect financial and stock records from unauthorized alteration:
- **Manager Authorization Required:** Actions involving discounts, order cancellations, and refunds require explicit credential validation (email and password) from a manager or owner.
- **Refund Policy Enforcement:** Refunds are strictly restricted to cash transactions with optional inventory restoration. Online payments are designated non-refundable to maintain account balance integrity with external payment gateways.
- **Immutable Audit Logging:** Critical events (logins, price edits, order cancellations, inventory write-offs) are logged with timestamp, actor name, role, IP address, and metadata snapshots.

### 5. Management Analytics & Reporting Dashboard
Provides executive visibility through real-time business intelligence widgets:
- **Daily & Historical Sales Trends:** Visualizing peak ordering periods, daily revenue, and average ticket size.
- **Payment Method Distribution:** Breakdowns comparing Cash vs. Online Payment performance.
- **Daily Consumption Matrix:** Ingredient-level usage tracking to anticipate reorder points before stockouts occur.
- **Low-Stock Alerting:** Instant notifications for ingredients that breach minimum buffer thresholds.

---

## User Roles and System Access Matrix

To maintain security and enforce segregation of duties, the system implements a strict role-based access control (RBAC) architecture. Each account is assigned specific operational permissions tailored to daily responsibilities:

| Role | Email Address | Default Password | System Access Level |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@coffee.com` | `password` | Full System Access + Add/Edit Staff Accounts + View Audit Logs + Access all reports. |
| **Manager** | `manager@coffee.com` | `password` | Inventory, menu, daily consumption, sales and inventory analytics, and authorization. |
| **Cashier** | `cashier@coffee.com` | `password` | POS Order Terminal + Select Sizes/Add-ons + Apply Discounts + Accept Cash/Online Payment + View order details and actions + Record split/partial payments for their own orders + Process refunds, cancellations, and voids only with active Manager or Owner credentials + Print Receipts. |

---

## Conclusion

Heim Specialty Coffee is a growing specialty beverage business with a strong demand pattern and a clear need for improvement in its operations. While the existing system has served the shop during its earlier stages, it has become increasingly inefficient due to its heavy reliance on manual processes, paper order slips, verbal communication, and spreadsheet-based inventory tracking. These shortcomings create delays, miscommunication, and reporting difficulties — particularly during peak hours.

The proposed system addresses these issues by integrating the core business functions of order management, payment processing, inventory monitoring, and sales reporting into a single, unified platform. With this system, Heim Specialty Coffee will be able to:

- Reduce operational errors
- Improve customer service
- Maintain better stock control
- Support informed management decisions

In this way, the proposed digital system will support the business's continued growth and ensure more efficient, reliable operations across both of its branches.
