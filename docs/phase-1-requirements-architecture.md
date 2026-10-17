# SOIMS Phase 1: Database and ERD Alignment

Date: 2026-10-02
Status: Completed against the official architecture

## 1. Scope

SOIMS is a Laravel 12 modular monolith for supply office inventory operations at West Visayas State University - Himamaylan City Campus. This document aligns the approved database architecture with the current implementation before further module work.

## 2. Validated Architecture

- Presentation: Laravel Blade, HTML5, CSS3, JavaScript, and accessible server-rendered UI.
- Application: Laravel routes, middleware, controllers, policies, form requests, and services.
- Data: Eloquent ORM with MySQL/MariaDB.
- Boundary: Browser -> route -> middleware/policy -> controller -> service -> model -> database.
- Authorization: Backend middleware and policies are authoritative. UI visibility is not a security boundary.
- Transactions: Receiving, inventory issue/release, accountable assignment, transfer, return, reconciliation, and other multi-record changes must use database transactions.
- Auditability: Mutating workflows must preserve actor, role, action, record, before/after values, reason, and timestamp.
- Barcode: A USB 1D scanner is treated as keyboard input to the web application. It does not connect directly to MySQL.
- Deployment target: Apache/XAMPP-compatible local or campus server with MySQL/MariaDB.

## 3. Validated Business Areas

The approved scope includes:

- Identity and access
- Departments and offices
- Categories and item catalog
- Inventory and receiving
- Supply requests and approval/modification workflow
- RIS and consumable issuance
- Accountable items and barcode traceability
- Assignment, transfer, and reassignment
- Property return / PRS
- Physical inventory and reconciliation
- Notifications
- Reports
- Audit logs

The implementation order remains dependency-driven: identity and access precede protected workflows; catalog precedes inventory; inventory precedes receiving and issuance; accountable item records precede assignment and return; audit data supports all critical workflows.

## 4. Canonical Roles

- Supply Administrator
- Supply Sub-Administrator
- Administrative Officer
- DAA
- Programs / Departments

Procurement is not a role. The live role catalog contains exactly these five roles. Permissions remain configurable for the Supply Sub-Administrator.

## 5. Official Workflow Decisions

The following rules are accepted as the implementation baseline:

- Academic versus non-academic request routing and the source of the classification.
- Approval statuses, transition rules, rejection reasons, and resubmission behavior.
- Who may modify confirmed quantities/items, which fields may change, and mandatory reason rules.
- Partial availability behavior, including whether unavailable quantities can be backordered.
- RIS numbering, revision numbering, numbering scope, and print format.
- Accountable item status values and whether one item record represents one physical unit.
- Property number and barcode uniqueness rules.
- Transfer, return, repair, disposal, and returned-to-stock state transitions.
- Physical inventory discrepancy approval and adjustment authority.
- Notification delivery uses in-app records with read state first; external delivery is deferred.

## 6. Database-to-Architecture Mapping

The ERD must preserve:

- Original requested quantities even when confirmed or issued quantities change.
- Request modification history with previous value, new value, user, time, reason, and related request.
- Inventory movement history for receiving, issue, release, return, adjustment, and reconciliation.
- Current and historical accountable assignments.
- Current and historical item locations and responsible users/departments.
- RIS and PRS document metadata independently from line items.
- Audit records for security-sensitive and inventory-affecting mutations.
- Referential integrity and appropriate delete/archive behavior.
- Decimal monetary values and quantity precision appropriate to the selected units.
- Expiration/lot data where applicable to consumables.

| Official requirement | Current implementation | Decision |
| --- | --- | --- |
| Stock Cards | `inventory_transactions` ledger | Stock Cards are a derived report/read model from immutable movements; no duplicate balance table is required. |
| Expiration Records | `inventory_lots` | Lot rows are the normalized expiration source, including lot number, expiration date, quantity, and receiving link. |
| Accountability History | `item_assignments` and `item_transfers` | Assignment and transfer rows preserve current and historical accountability; no duplicate history table is required. |
| Audit Logs | `audit_logs` | Table exists; event-writing service remains a later implementation requirement. |
| Five roles | `roles` and seeded catalog | Exact five-role model is authoritative; Procurement remains removed. |

## 7. Current Foundation Assessment

- Laravel 12.69.3 and PHP 8.2.12 are compatible and operational.
- MySQL/MariaDB connection is configured through environment variables.
- The `soims` database contains the approved migration schema and no inventory/business records.
- Authentication, authorization, user management, and shared UI foundation are implemented.
- The basic visual identity is light blue with a dirty-white background and white surfaces.
- Inventory, request, approval, issuance, accountability workflows, reporting, notifications, and audit event writing remain unimplemented.

## 8. Architecture Risks and Controls

- Role vocabulary drift is controlled by the exact five-role seed catalog.
- Mixing procurement, approval, and supply processing responsibilities without explicit boundaries can create unauthorized workflow transitions.
- Storing current inventory without immutable movement records would make reconciliation and audit reporting unreliable.
- Treating accountable items as ordinary stock would lose per-unit traceability.
- Stock Card derivation must use the immutable movement ledger and must not recalculate history from current balances.
- Audit event writing must be added before transactional modules are considered complete.
- UI must continue using centralized SOIMS CSS tokens and Blade components.

## 9. Phase 1 Result

The official architecture is aligned with the existing foundation and database schema. The explicit table mappings above resolve the prior audit findings without adding duplicate tables.

Recommended next phase: Phase 2 - Laravel Project Foundation review, then Phase 3 - Authentication review under the updated phase sequence.
