<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class AdminUserController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
    }

    public function index(): View
    {
        $this->authorizeManage();
        $users = User::with('roles')->orderByDesc('id')->paginate(config('voting.pagination.users'));
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizeManage();
        return view('admin.users.form', ['user' => new User(), 'roles' => Role::all()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorizeManage();
        $data = $r->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'status'   => ['nullable', 'in:active,inactive'],
            'roles'    => ['array'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'] ?? 'active',
        ]);
        $user->syncRoles($data['roles'] ?? []);

        return redirect('/admin/users')->with('success', __('User created.'));
    }

    public function edit(User $user): View
    {
        $this->authorizeManage();
        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::all()]);
    }

    public function update(Request $r, User $user): RedirectResponse
    {
        $this->authorizeManage();
        $data = $r->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'status'   => ['nullable', 'in:active,inactive'],
            'roles'    => ['array'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ]);

        $user->name   = $data['name'];
        $user->email  = $data['email'];
        $user->status = $data['status'] ?? $user->status ?? 'active';
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles($data['roles'] ?? []);

        return redirect('/admin/users')->with('success', __('User updated.'));
    }

    public function toggle(User $user): RedirectResponse
    {
        $this->authorizeManage();
        if ($user->id === auth()->id()) {
            return back()->withErrors(['status' => __('You cannot deactivate your own account.')]);
        }
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        $msg = $user->status === 'active' ? __('User activated.') : __('User deactivated.');
        return back()->with('success', $msg);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeManage();
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => __('You cannot delete your own account.')]);
        }
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return back()->withErrors(['user' => __('Cannot remove the last super admin.')]);
        }
        $user->delete();
        return back()->with('success', __('User deleted.'));
    }
}
