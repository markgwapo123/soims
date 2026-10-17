<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountableItemRequest;
use App\Http\Requests\StoreItemAssignmentRequest;
use App\Models\AccountableItem;
use App\Models\Department;
use App\Models\Item;
use App\Models\Office;
use App\Models\User;
use App\Services\AccountableItemService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountableItemController extends Controller
{
    public function __construct(private readonly AccountableItemService $accountableItems) {}

    public function create(): View
    {
        return view('accountable-items.create', [
            'items' => Item::where('classification', 'accountable')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(CreateAccountableItemRequest $request): RedirectResponse
    {
        $asset = $this->accountableItems->register(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('accountable-items.show', $asset)->with('status', 'Accountable asset registered.');
    }

    public function assign(StoreItemAssignmentRequest $request, AccountableItem $accountableItem): RedirectResponse
    {
        $this->accountableItems->assign($accountableItem, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('accountable-items.show', $accountableItem)->with('status', 'Asset assigned.');
    }

    public function transfer(StoreItemAssignmentRequest $request, AccountableItem $accountableItem): RedirectResponse
    {
        $this->accountableItems->transfer($accountableItem, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('accountable-items.show', $accountableItem)->with('status', 'Asset transferred.');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $assets = AccountableItem::query()
            ->with(['item', 'currentDepartment', 'currentOffice', 'currentUser'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($matches) use ($search): void {
                    $matches->where('barcode', $search)
                        ->orWhere('property_no', $search)
                        ->orWhere('serial_no', $search);
                });
            })
            ->orderBy('property_no')
            ->paginate(20)
            ->withQueryString();

        return view('accountable-items.index', compact('assets', 'search'));
    }

    public function show(AccountableItem $accountableItem): View
    {
        $asset = $accountableItem->load([
            'item', 'currentDepartment', 'currentOffice', 'currentUser',
            'assignments.assignedUser', 'assignments.assignedDepartment', 'assignments.assignedOffice', 'assignments.assignedBy',
        ]);

        return view('accountable-items.show', [
            'asset' => $asset,
            'activeAssignment' => $asset->assignments->first(fn ($assignment) => $assignment->released_at === null),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}