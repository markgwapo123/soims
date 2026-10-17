# System Input and Output

The SOIMS input and output processes allow authorized users to provide data, initiate inventory workflows, and view processed information. The application validates each submission, applies role-based permissions, processes the request, and stores or retrieves the relevant records. The resulting outputs include confirmation messages, request statuses, inventory balances, reports, and transaction histories.

**Table X**  
*SOIMS System Inputs, Processes, and Outputs*

| Input | System Process | Output |
| --- | --- | --- |
| Username/email and password | Verifies credentials, account status, and assigned permissions. | Access to the authorized dashboard, or an authentication error. |
| Item and category details | Validates the required catalog fields and saves the catalog record. | Item or category available in the catalog. |
| Stock receipt details, including item, quantity, date, and location | Validates the receipt, updates the inventory balance, and records the stock movement. | Updated balance and receipt transaction in the inventory ledger. |
| Search terms and stock filters | Queries inventory records and calculates available quantities and status. | Matching items with availability information. |
| Request purpose, items, and quantities | Validates the request, associates it with the user's department, assigns its request number, and routes it for approval. | Submitted request with its number and current status. |
| Approval or denial decision and remarks | Records the approver's decision and updates the request status and confirmed quantities. | Updated request status and notification to the requestor. |
| RIS issue quantities and recipient details | Checks issue quantities against the confirmed request and current availability, then prepares the RIS. On release, deducts issued quantities and records the release. | RIS document, updated stock balances, release history, and completed request status. |
| Accountable-asset details, including property number, barcode value, and serial number | Validates and records the individual asset; assignment or transfer details update its accountability history. | Asset record, current assignment, and assignment history. |
| Physical inventory count and remarks | Records physical quantities and calculates shortages or overages for review. | Completed count record and discrepancy information; stock balances are not changed automatically. |
| Report filters, such as date range or item | Queries and organizes the corresponding inventory or transaction records. | Available inventory reports, including balance, stock-card, and expiration information. |

A USB barcode scanner can provide barcode input to a focused browser field when configured to act as keyboard input. In the current system, barcode values are stored for accountable assets and can be used for identification; barcode generation should not be described as an output unless it is implemented and verified.

## Scope Note

The current system does not process consumable borrowing and returns between departments, purchase orders, or supplier-facing transactions. Do not list these as system inputs or outputs unless those workflows are added to the final implementation.
