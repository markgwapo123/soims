<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Http\Requests\Admin\ItemRequest;
use App\Http\Requests\Admin\OfficeRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Office;
use App\Services\CatalogManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogManagementService $catalog) {}

    public function index(): View
    {
        return view('admin.catalog.index', [
            'departments' => Department::with('offices')->orderBy('name')->get(),
            'offices' => Office::with('department')->orderBy('name')->get(),
            'categories' => Category::with('items')->orderBy('name')->get(),
            'items' => Item::with('category')->orderBy('name')->get(),
        ]);
    }

    public function create(string $type): View
    {
        abort_unless(in_array($type, ['departments', 'offices', 'categories', 'items'], true), 404);

        return view('admin.catalog.form', [
            'type' => $type, 'record' => null,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeDepartment(DepartmentRequest $request): RedirectResponse { $this->catalog->create(new Department(), $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function storeOffice(OfficeRequest $request): RedirectResponse { $this->catalog->create(new Office(), $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function storeCategory(CategoryRequest $request): RedirectResponse { $this->catalog->create(new Category(), $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function storeItem(ItemRequest $request): RedirectResponse { $this->catalog->create(new Item(), $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }

    public function edit(string $type, int $id): View
    {
        $model = $this->modelFor($type)::findOrFail($id);
        return view('admin.catalog.form', [
            'type' => $type, 'record' => $model,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateDepartment(DepartmentRequest $request, Department $department): RedirectResponse { $this->catalog->update($department, $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function updateOffice(OfficeRequest $request, Office $office): RedirectResponse { $this->catalog->update($office, $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function updateCategory(CategoryRequest $request, Category $category): RedirectResponse { $this->catalog->update($category, $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function updateItem(ItemRequest $request, Item $item): RedirectResponse { $this->catalog->update($item, $request->validated(), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }

    public function deactivate(\Illuminate\Http\Request $request, string $type, int $id): RedirectResponse { $this->catalog->deactivate($this->modelFor($type)::findOrFail($id), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }
    public function activate(\Illuminate\Http\Request $request, string $type, int $id): RedirectResponse { $this->catalog->activate($this->modelFor($type)::findOrFail($id), $request->user(), $request->ip(), $request->userAgent()); return $this->back(); }

    private function modelFor(string $type): string
    {
        return match ($type) {
            'departments' => Department::class, 'offices' => Office::class,
            'categories' => Category::class, 'items' => Item::class,
            default => abort(404),
        };
    }

    private function back(): RedirectResponse { return redirect()->route('admin.catalog.index')->with('status', 'Catalog record saved.'); }
}
