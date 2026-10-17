<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\Department;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagement) {}

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userManagement->create($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('admin.users.index')->with('status', 'User account created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = array_filter($request->validated(), static fn ($value) => $value !== null && $value !== '');
        $this->userManagement->update($user, $attributes, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('admin.users.index')->with('status', 'User account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot deactivate your own account.');
        $this->userManagement->deactivate($user, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('admin.users.index')->with('status', 'User account deactivated.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->userManagement->activate($user, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('admin.users.index')->with('status', 'User account activated.');
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->userManagement->resetPassword($user, $request->string('password')->toString(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('admin.users.edit', $user)->with('status', 'User password reset.');
    }
}
