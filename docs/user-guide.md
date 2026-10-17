# SOIMS User Guide

Supply Office Inventory Management System (SOIMS) for West Visayas State University - Himamaylan City Campus.

## 1. Sign In and Navigation

Open the SOIMS URL provided by your administrator and sign in with your assigned username and password. The navigation menu depends on your assigned role and permissions. If a menu described in this guide is not visible, contact the Supply Administrator; do not use another person's account.

Use the sidebar to open a module. Select **Sign out** when you finish, especially on a shared computer.

## 2. Roles and Main Responsibilities

| Role | Main responsibilities |
| --- | --- |
| Supply Administrator | Manage users, catalog, inventory, approvals, RIS issuance, accountable assets, reports, and audit logs. |
| Supply Sub-Administrator | Manage catalog and inventory, issue supplies, and view reports. Other permissions may be configured by the administrator. |
| Administrative Officer | Review non-academic and administrative requests and view reports. |
| Department of Academic Affairs (DAA) | Review academic requests and view reports. |
| Programs / Departments | Check stock availability, submit requests, and track their own requests. |

## 3. Check Stock Availability

1. Open **Stock availability**.
2. Search by item name, stock number, or category. Filter by category, item type, or availability status if needed.
3. Check the **Available** quantity and status before submitting a request.

For consumables, available quantity excludes stock already reserved. For accountable assets, the available count includes registered assets whose status is `available`. Supply Office users with inventory-management access can also see **Current stock**.

Stock availability shows the Supply Office inventory. It does not show separate consumable balances owned by each department.

## 4. Submit and Track a Supply Request

For a Programs / Departments user:

1. Open **New request**.
2. Enter the request purpose.
3. Enter a positive quantity for each item needed. Leave other item quantities blank or at zero.
4. Review the item names and quantities, then select **Submit request**.
5. Open **My requests** to review the request number, status, items, and approval history. Check **Notifications** for updates.

The system routes requests based on the department assigned to the user's account:

| Department type | Approval queue |
| --- | --- |
| Academic | Department of Academic Affairs (DAA) |
| Non-academic or administrative | Administrative Officer |

An authorized approver opens **Review requests**, selects a submitted request, checks its purpose and requested items, and chooses **Confirm request** or **Deny request**. Add remarks when useful. A confirmed request proceeds to Supply Office processing; a denied request is returned to the requestor with its recorded decision/comments.

Typical request progression is **Submitted** → **Confirmed** → **Processing** when an RIS is prepared → **Completed** when supplies are released. A denied request has the **Denied** status.

## 5. Receive Stock and Maintain the Catalog

These actions require Supply Office permissions.

### Add catalog records

1. Open **Catalog**.
2. Add the necessary department, office, category, or item.
3. For an item, provide its stock number, name, description, category, unit of measure, classification, and any applicable price or reorder level.
4. Choose **Consumable** for supplies tracked by quantity. Choose **Accountable** for individually controlled property such as a computer.

Create or confirm the catalog item before recording stock against it.

### Record incoming consumable stock

1. Open **Receive stock**.
2. Select the catalog item and enter the quantity, location, and received date.
3. Add lot number, expiration date, unit cost, and reason/reference when applicable.
4. Select **Receive stock**. The system updates inventory and records a receipt movement in the ledger.

Use **Inventory** to review balances and stock movements. Do not record an issued item as a new receipt.

## 6. Prepare and Release an RIS

After a request is confirmed:

1. Open **Prepare / release RIS** and select a confirmed request awaiting an issue slip.
2. Review each confirmed item, requested quantity, and current availability.
3. Enter the quantity to issue. It cannot exceed either the confirmed request quantity or currently available stock. Any quantity not issued is recorded as unavailable.
4. Complete the receiving person's printed name and designation, and any applicable fund cluster or responsibility center code.
5. Select **Prepare RIS** and review the generated issue slip.
6. When the supplies are physically handed over, select **Release supplies** and add release notes if needed.

Preparing an RIS does not deduct stock. Stock is deducted only when the RIS is released. The request becomes **Completed** after release.

## 7. Register and Assign an Accountable Asset

Use this workflow for an individually tracked item such as a new computer. It requires accountable-item permissions.

1. In **Catalog**, add the item type/model (for example, Desktop Computer) and set its classification to **Accountable**.
2. Open **Accountable assets** and select **Register asset**.
3. Select the catalog item. Enter the property number, barcode, serial number (if available), acquisition date, unit value, and status.
4. Open the registered asset and choose **Assign asset**. Select the department or office, optional user, and location (for example, Computer Lab).
5. For a later change of custodian or location, use **Transfer asset** on the asset record and provide the new assignment details and transfer reason.

Register each physical computer separately so its property number, barcode, serial number, status, and assignment can be tracked.

## 8. Conduct a Physical Inventory

1. Open **Physical inventory** and select **New count**.
2. Enter an inventory number, count date/time, and the applicable department or office. Select the consumable balances and accountable assets to verify.
3. Select **Create count draft**.
4. On the draft, enter the physical count for every listed line. Record newly found items in the blank rows and add remarks where appropriate.
5. Select **Complete physical inventory** to record the count and any shortage or overage.

Completed physical counts record discrepancies for review. They do not automatically change inventory balances or the stock ledger. Cancel a draft if the count should not proceed.

## 9. Reports, Notifications, and Audit Logs

- **Notifications**: Review request decisions, availability notices, and other workflow updates.
- **Reports**: Review inventory balances and other reports available to your role. Apply the available filters before exporting or printing, if those actions are shown.
- **Audit logs**: Authorized users can review recorded system actions and change history. Audit history is retained even when related operational records are later removed.
- **Property returns**: Authorized users can create and track property-return records for accountable assets.

## 10. Important Limitations and Data Practices

- Consumable inventory is currently maintained as Supply Office stock, not as a separate balance for every department.
- The system does not currently provide a workflow to borrow or transfer consumable stock between departments. Do not record a department-to-department loan as a Supply Office request or receipt.
- Accountable assets, such as computers, can be assigned and transferred between departments or offices through their asset records.
- Use accurate quantities, identifiers, locations, and reasons. Submitted requests, stock movements, approvals, RIS documents, and asset assignments form operational records and may be retained in the audit history.
- Demo sample accounts and sample inventory were removed from the current local database. Ask the system administrator for an authorized account; there are no shared demo credentials in this guide.

## 11. Troubleshooting

- **A menu is missing:** Ask the Supply Administrator to verify your role and permissions.
- **A request cannot be submitted:** Confirm that your user account is assigned to an active department or office and that at least one item has a positive requested quantity.
- **An item is missing from a form:** Ask an authorized Supply Office user to check whether the catalog item exists and is active.
- **An RIS cannot be released:** Recheck current available stock and the issue quantity. The system will not release more than is currently available.
- **A physical count differs from the recorded quantity:** Complete the count with accurate remarks, then have an authorized inventory manager review and process any required stock correction separately.