<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('status', __('users.created'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $this->users->update($request->user(), $user, [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
            'is_active' => $request->boolean('is_active'),
            'password' => $data['password'] ?? null,
        ]);

        return redirect()->route('users.index')->with('status', __('users.updated'));
    }

    public function destroy(\Illuminate\Http\Request $request, User $user): RedirectResponse
    {
        $this->users->delete($request->user(), $user);

        return redirect()->route('users.index')->with('status', __('users.deleted'));
    }
}
