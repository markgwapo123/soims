<?php

namespace App\Services;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\InventoryTransaction;
use App\Models\PhysicalInventory;
use App\Models\PropertyReturn;
use App\Models\Release;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly StockAvailabilityService $stockAvailability) {}

    public function dataFor(User $user, array $stockFilters = []): array
    {
        $stats = [];
        $actions = [];
        $receivingTransactions = collect();
        $recentReleases = collect();
        $recentRequests = collect();
        $canManageInventory = $user->hasPermission('manage_inventory');
        $canViewStockAvailability = $canManageInventory
            || $user->hasPermission('submit_supply_requests')
            || $user->hasPermission('approve_supply_requests');
        $stockItems = $canViewStockAvailability
            ? $this->stockAvailability->paginate($stockFilters, $canManageInventory, 6, 'stock_page')
            : collect();
        $stockSummary = $canManageInventory ? $this->stockAvailability->summary(true) : null;

        if ($user->hasPermission('manage_users')) {
            $actions[] = ['label' => 'Manage users', 'url' => route('admin.users.index')];
        }

        if ($user->hasPermission('view_reports')) {
            $actions[] = ['label' => 'Reports', 'url' => route('reports.index')];
        }

        if ($user->hasPermission('manage_inventory')) {
            $stats[] = ['label' => 'Total inventory quantity', 'value' => number_format($stockSummary['current_stock'], 3), 'url' => route('admin.inventory.index')];
            $stats[] = ['label' => 'Open physical counts', 'value' => PhysicalInventory::where('status', PhysicalInventory::STATUS_DRAFT)->count(), 'url' => route('physical-inventories.index')];
            $stats[] = ['label' => 'Low stock items', 'value' => $stockSummary['low_stock_items'], 'url' => route('stock-availability.index')];
            $actions[] = ['label' => 'Inventory', 'url' => route('admin.inventory.index')];
            $actions[] = ['label' => 'Receive stock', 'url' => route('admin.inventory.receive')];
            $actions[] = ['label' => 'Physical inventory', 'url' => route('physical-inventories.index')];
            $receivingTransactions = InventoryTransaction::with(['item', 'performer'])
                ->where('type', 'receipt')->latest('occurred_at')->limit(5)->get();
        }

        $reviewableTypes = $this->reviewableTypes($user);
        if ($reviewableTypes !== []) {
            $requests = SupplyRequest::whereIn('request_type', $reviewableTypes);
            $stats[] = ['label' => 'Pending requests', 'value' => (clone $requests)->where('status', SupplyRequest::STATUS_SUBMITTED)->count(), 'url' => route('approvals.index')];
            $stats[] = ['label' => 'Approved requests', 'value' => (clone $requests)->whereIn('status', [SupplyRequest::STATUS_CONFIRMED, SupplyRequest::STATUS_PROCESSING])->count(), 'url' => route('approvals.index')];
            $stats[] = ['label' => 'Denied requests', 'value' => (clone $requests)->where('status', SupplyRequest::STATUS_DENIED)->count(), 'url' => route('approvals.index')];
            $actions[] = ['label' => 'Review requests', 'url' => route('approvals.index')];
            $recentRequests = (clone $requests)->with(['requestor', 'department'])->latest('updated_at')->limit(6)->get();
        }

        if ($user->hasRole('program_department') || ($user->hasRole('supply_sub_administrator') && $user->hasPermission('submit_supply_requests'))) {
            $ownRequests = SupplyRequest::where('requestor_id', $user->id);
            $stats[] = ['label' => 'Submitted requests', 'value' => (clone $ownRequests)->count(), 'url' => route('requests.index')];
            $stats[] = ['label' => 'Pending requests', 'value' => (clone $ownRequests)->where('status', SupplyRequest::STATUS_SUBMITTED)->count(), 'url' => route('requests.index')];
            $stats[] = ['label' => 'Approved requests', 'value' => (clone $ownRequests)->whereIn('status', [SupplyRequest::STATUS_CONFIRMED, SupplyRequest::STATUS_PROCESSING])->count(), 'url' => route('requests.index')];
            $stats[] = ['label' => 'Denied requests', 'value' => (clone $ownRequests)->where('status', SupplyRequest::STATUS_DENIED)->count(), 'url' => route('requests.index')];
            $stats[] = ['label' => 'Issued requests', 'value' => (clone $ownRequests)->where('status', SupplyRequest::STATUS_COMPLETED)->count(), 'url' => route('requests.index')];
            $actions[] = ['label' => 'My supply requests', 'url' => route('requests.index')];
            $actions[] = ['label' => 'New request', 'url' => route('requests.create')];
            $recentRequests = (clone $ownRequests)->with(['requestor', 'department'])->latest('updated_at')->limit(6)->get();
        }

        if ($user->hasPermission('issue_supplies')) {
            $stats[] = ['label' => 'Approved requests', 'value' => SupplyRequest::whereIn('status', [SupplyRequest::STATUS_CONFIRMED, SupplyRequest::STATUS_PROCESSING])->count(), 'url' => route('ris.index')];
            $actions[] = ['label' => 'Prepare / release RIS', 'url' => route('ris.index')];
            $recentReleases = Release::with(['ris.supplyRequest', 'releaser'])->latest('released_at')->limit(5)->get();
        } elseif ($user->hasPermission('submit_supply_requests')) {
            $recentReleases = Release::with(['ris.supplyRequest', 'releaser'])
                ->whereHas('ris.supplyRequest', fn ($query) => $query->where('requestor_id', $user->id))
                ->latest('released_at')->limit(5)->get();
        }

        if ($user->hasPermission('manage_accountable_items')) {
            $stats[] = ['label' => 'Accountable items', 'value' => AccountableItem::count(), 'url' => route('accountable-items.index')];
            $stats[] = ['label' => 'Pending returns', 'value' => PropertyReturn::where('status', PropertyReturn::STATUS_DRAFT)->count(), 'url' => route('property-returns.index')];
            $actions[] = ['label' => 'Accountable assets', 'url' => route('accountable-items.index')];
            $actions[] = ['label' => 'Property returns', 'url' => route('property-returns.index')];
        }

        if ($user->hasPermission('view_audit_logs')) {
            $activity = AuditLog::latest('occurred_at')->limit(6)->get();
            $subjectUserIds = $activity->where('record_type', User::class)->pluck('record_id')->filter()->unique();
            $subjectUserNames = User::whereKey($subjectUserIds)->pluck('name', 'id');

            $activity->each(function (AuditLog $entry) use ($subjectUserNames): void {
                if ($entry->record_type === User::class) {
                    $entry->setAttribute('subject_name', $subjectUserNames->get($entry->record_id));
                }
            });
        } else {
            $activity = $recentRequests;
        }

        return [
            'stats' => $this->uniqueStats($stats),
            'actions' => $actions,
            'receivingTransactions' => $receivingTransactions,
            'recentReleases' => $recentReleases,
            'recentRequests' => $recentRequests,
            'stockItems' => $stockItems,
            'stockSummary' => $stockSummary,
            'stockFilters' => $stockFilters,
            'canManageInventory' => $canManageInventory,
            'canViewStockAvailability' => $canViewStockAvailability,
            'activity' => $activity,
            'roles' => $user->roles()->wherePivotNull('revoked_at')->pluck('roles.name')->all(),
        ];
    }

    /** @return list<string> */
    private function reviewableTypes(User $user): array
    {
        if (! $user->hasPermission('approve_supply_requests')) {
            return [];
        }

        $types = [];
        if ($user->hasRole('supply_administrator') || $user->hasRole('daa')) {
            $types[] = SupplyRequest::TYPE_ACADEMIC;
        }
        if ($user->hasRole('supply_administrator') || $user->hasRole('administrative_officer')) {
            $types[] = SupplyRequest::TYPE_NON_ACADEMIC;
        }

        return $types;
    }

    private function uniqueStats(array $stats): Collection
    {
        return collect($stats)->unique('label')->values();
    }
}