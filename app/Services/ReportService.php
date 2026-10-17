<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const REPORTS = [
        'inventory_balances' => 'Inventory balances',
        'stock_movements' => 'Stock movements',
        'supply_requests' => 'Supply requests',
        'releases' => 'RIS releases',
        'accountable_items' => 'Accountable items',
        'property_returns' => 'Property returns',
        'physical_inventory' => 'Physical inventory discrepancies',
        'expiration' => 'Expiration lots',
    ];

    /** @return array{title: string, columns: array<string, string>, query: Builder} */
    public function definition(string $type, array $filters): array
    {
        return match ($type) {
            'inventory_balances' => [
                'title' => self::REPORTS[$type],
                'columns' => ['stock_no' => 'Stock no.', 'article' => 'Article', 'category' => 'Category', 'unit' => 'Unit', 'quantity_on_hand' => 'On hand', 'quantity_reserved' => 'Reserved', 'quantity_available' => 'Available', 'reorder_level' => 'Reorder level', 'location' => 'Location'],
                'query' => DB::table('inventories')->join('items', 'items.id', '=', 'inventories.item_id')->join('categories', 'categories.id', '=', 'items.category_id')->where('items.classification', 'consumable')
                    ->select('items.stock_no', 'items.name as article', 'categories.name as category', 'items.unit_of_measure as unit', 'inventories.quantity_on_hand', 'inventories.quantity_reserved', DB::raw('(inventories.quantity_on_hand - inventories.quantity_reserved) as quantity_available'), 'items.reorder_level', 'inventories.location')->orderBy('items.name'),
            ],
            'stock_movements' => [
                'title' => self::REPORTS[$type],
                'columns' => ['occurred_at' => 'Date', 'stock_no' => 'Stock no.', 'article' => 'Article', 'type' => 'Movement', 'quantity' => 'Quantity', 'unit_cost' => 'Unit cost', 'reference_type' => 'Reference type', 'reference_id' => 'Reference no.', 'reason' => 'Reason', 'performed_by' => 'Performed by'],
                'query' => $this->period(DB::table('inventory_transactions')->join('items', 'items.id', '=', 'inventory_transactions.item_id')->leftJoin('users', 'users.id', '=', 'inventory_transactions.performed_by')
                    ->select('inventory_transactions.occurred_at', 'items.stock_no', 'items.name as article', 'inventory_transactions.type', 'inventory_transactions.quantity', 'inventory_transactions.unit_cost', 'inventory_transactions.reference_type', 'inventory_transactions.reference_id', 'inventory_transactions.reason', 'users.name as performed_by'), 'inventory_transactions.occurred_at', $filters)->orderByDesc('inventory_transactions.occurred_at'),
            ],
            'supply_requests' => [
                'title' => self::REPORTS[$type],
                'columns' => ['request_no' => 'Request no.', 'request_type' => 'Type', 'status' => 'Status', 'requestor' => 'Requestor', 'department' => 'Department', 'office' => 'Office', 'submitted_at' => 'Submitted', 'confirmed_at' => 'Confirmed', 'completed_at' => 'Completed'],
                'query' => $this->period(DB::table('supply_requests')->join('users', 'users.id', '=', 'supply_requests.requestor_id')->leftJoin('departments', 'departments.id', '=', 'supply_requests.department_id')->leftJoin('offices', 'offices.id', '=', 'supply_requests.office_id')
                    ->select('supply_requests.request_no', 'supply_requests.request_type', 'supply_requests.status', 'users.name as requestor', 'departments.name as department', 'offices.name as office', 'supply_requests.submitted_at', 'supply_requests.confirmed_at', 'supply_requests.completed_at'), 'supply_requests.submitted_at', $filters)->orderByDesc('supply_requests.created_at'),
            ],
            'releases' => [
                'title' => self::REPORTS[$type],
                'columns' => ['released_at' => 'Released', 'ris_no' => 'RIS no.', 'request_no' => 'Request no.', 'status' => 'Status', 'released_by' => 'Released by', 'received_by' => 'Received by', 'notes' => 'Notes'],
                'query' => $this->period(DB::table('releases')->join('ris', 'ris.id', '=', 'releases.ris_id')->leftJoin('supply_requests', 'supply_requests.id', '=', 'ris.supply_request_id')->leftJoin('users as releaser', 'releaser.id', '=', 'releases.released_by')->leftJoin('users as receiver', 'receiver.id', '=', 'releases.received_by')
                    ->select('releases.released_at', 'ris.ris_no', 'supply_requests.request_no', 'releases.status', 'releaser.name as released_by', 'receiver.name as received_by', 'releases.notes'), 'releases.released_at', $filters)->orderByDesc('releases.released_at'),
            ],
            'accountable_items' => [
                'title' => self::REPORTS[$type],
                'columns' => ['property_no' => 'Property no.', 'stock_no' => 'Stock no.', 'article' => 'Article', 'serial_no' => 'Serial no.', 'status' => 'Status', 'unit_value' => 'Value', 'user' => 'Custodian', 'department' => 'Department', 'office' => 'Office', 'location' => 'Location'],
                'query' => DB::table('accountable_items')->join('items', 'items.id', '=', 'accountable_items.item_id')->leftJoin('users', 'users.id', '=', 'accountable_items.current_user_id')->leftJoin('departments', 'departments.id', '=', 'accountable_items.current_department_id')->leftJoin('offices', 'offices.id', '=', 'accountable_items.current_office_id')
                    ->whereNull('accountable_items.deleted_at')->select('accountable_items.property_no', 'items.stock_no', 'items.name as article', 'accountable_items.serial_no', 'accountable_items.status', 'accountable_items.unit_value', 'users.name as user', 'departments.name as department', 'offices.name as office', 'accountable_items.current_location as location')->orderBy('accountable_items.property_no'),
            ],
            'property_returns' => [
                'title' => self::REPORTS[$type],
                'columns' => ['received_at' => 'Received', 'prs_no' => 'PRS no.', 'purpose' => 'Purpose', 'office_name' => 'Office', 'status' => 'PRS status', 'property_no_snapshot' => 'Property no.', 'description_snapshot' => 'Description', 'unit_value' => 'Value', 'remarks' => 'Remarks'],
                'query' => $this->period(DB::table('property_return_items')->join('property_returns', 'property_returns.id', '=', 'property_return_items.property_return_id')
                    ->select('property_returns.received_at', 'property_returns.prs_no', 'property_returns.purpose', 'property_returns.office_name', 'property_returns.status', 'property_return_items.property_no_snapshot', 'property_return_items.description_snapshot', 'property_return_items.unit_value', 'property_return_items.remarks'), 'property_returns.received_at', $filters)->orderByDesc('property_returns.received_at'),
            ],
            'physical_inventory' => [
                'title' => self::REPORTS[$type],
                'columns' => ['inventory_no' => 'Inventory no.', 'conducted_at' => 'Conducted', 'article' => 'Article', 'description' => 'Description', 'old_property_no' => 'Old property no.', 'new_property_no' => 'New property no.', 'quantity_per_property_card' => 'Per card', 'quantity_per_physical_count' => 'Physical count', 'shortage_quantity' => 'Shortage', 'overage_quantity' => 'Overage', 'remarks' => 'Remarks'],
                'query' => $this->period(DB::table('physical_inventory_items')->join('physical_inventories', 'physical_inventories.id', '=', 'physical_inventory_items.physical_inventory_id')
                    ->where('physical_inventories.status', 'COMPLETED')->where(function ($query): void {
                        $query->where('physical_inventory_items.shortage_quantity', '>', 0)->orWhere('physical_inventory_items.overage_quantity', '>', 0);
                    })->select('physical_inventories.inventory_no', 'physical_inventories.conducted_at', 'physical_inventory_items.article', 'physical_inventory_items.description', 'physical_inventory_items.old_property_no', 'physical_inventory_items.new_property_no', 'physical_inventory_items.quantity_per_property_card', 'physical_inventory_items.quantity_per_physical_count', 'physical_inventory_items.shortage_quantity', 'physical_inventory_items.overage_quantity', 'physical_inventory_items.remarks'), 'physical_inventories.conducted_at', $filters)->orderByDesc('physical_inventories.conducted_at'),
            ],
            'expiration' => [
                'title' => self::REPORTS[$type],
                'columns' => ['stock_no' => 'Stock no.', 'article' => 'Article', 'lot_no' => 'Lot no.', 'received_date' => 'Received', 'expiration_date' => 'Expires', 'quantity_on_hand' => 'Lot quantity', 'unit_cost' => 'Unit cost'],
                'query' => $this->period(DB::table('inventory_lots')->join('items', 'items.id', '=', 'inventory_lots.item_id')->whereNotNull('inventory_lots.expiration_date')
                    ->select('items.stock_no', 'items.name as article', 'inventory_lots.lot_no', 'inventory_lots.received_date', 'inventory_lots.expiration_date', 'inventory_lots.quantity_on_hand', 'inventory_lots.unit_cost'), 'inventory_lots.expiration_date', $filters)->orderBy('inventory_lots.expiration_date'),
            ],
            default => throw new \InvalidArgumentException('Unknown report type.'),
        };
    }

    public function types(): array
    {
        return self::REPORTS;
    }

    private function period(Builder $query, string $column, array $filters): Builder
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }

        return $query;
    }
}