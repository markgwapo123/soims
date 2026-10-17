# SOIMS Phase 4: Permission Matrix

Date: 2026-10-02
Status: Complete

## Canonical Roles

1. Supply Administrator
2. Supply Sub-Administrator
3. Administrative Officer
4. Department of Academic Affairs (DAA)
5. Programs / Departments

Procurement is not a role. The `process_procurement` permission remains defined for future workflow configuration but is not assigned to any role.

## Current Permission Matrix

| Permission | Supply Admin | Supply Sub-Admin | Administrative Officer | DAA | Programs / Departments |
| --- | --- | --- | --- | --- | --- |
| access_supply_office | Allow | Allow | Allow | Allow | Deny |
| manage_users | Allow | Configurable | Deny | Deny | Deny |
| manage_catalog | Allow | Allow by default | Deny | Deny | Deny |
| manage_inventory | Allow | Allow by default | Deny | Deny | Deny |
| submit_supply_requests | Allow | Deny by default | Deny | Deny | Allow |
| approve_supply_requests | Allow | Deny by default | Allow | Allow | Deny |
| process_procurement | Unassigned | Unassigned | Unassigned | Unassigned | Unassigned |
| issue_supplies | Allow | Allow by default | Deny | Deny | Deny |
| manage_accountable_items | Allow | Deny by default | Deny | Deny | Deny |
| view_reports | Allow | Allow by default | Allow | Allow | Deny |
| view_audit_logs | Allow | Deny by default | Deny | Deny | Deny |

## Enforcement

- Route middleware enforces `role:<key>` or `permission:<key>`.
- `User::hasRole()` ignores inactive roles and revoked assignments.
- `User::hasPermission()` ignores inactive roles, revoked assignments, and unassigned permissions.
- UI visibility is not authorization; backend middleware remains authoritative.
- Role and permission assignments are stored relationally in `user_roles` and `role_permissions`.

## Validation

The test suite covers guest denial, role authorization, permission authorization, revoked assignments, user-management permission protection, and configurable role permissions.

Recommended next phase: Phase 5 - User Management review under the official phase sequence.
