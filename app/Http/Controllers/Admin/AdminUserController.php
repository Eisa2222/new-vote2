<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Actions\CreateUserAction;
use App\Modules\Users\Actions\DeleteUserAction;
use App\Modules\Users\Actions\ToggleUserStatusAction;
use App\Modules\Users\Actions\UpdateUserAction;
use App\Modules\Users\Http\Requests\StoreUserRequest;
use App\Modules\Users\Http\Requests\UpdateUserRequest;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

        $users = User::with('roles')
            ->orderByDesc('id')
            ->paginate(config('voting.pagination.users'));

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('admin.users.form', [
            'user'  => new User(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $creator): RedirectResponse
    {
        $creator->execute($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('User created.'));
    }

    public function edit(User $user): View
    {
        $this->authorizeManage();

        return view('admin.users.form', [
            'user'  => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updater): RedirectResponse
    {
        $updater->execute($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('User updated.'));
    }

    public function toggle(User $user, ToggleUserStatusAction $toggler): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $updated = $toggler->execute($user);
            $message = $updated->status === 'active' ? __('User activated.') : __('User deactivated.');
            return redirect()->route('admin.users.index')->with('success', $message);
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['status' => $exception->getMessage()]);
        }
    }

    public function destroy(User $user, DeleteUserAction $deleter): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $deleter->execute($user);
            return redirect()->route('admin.users.index')->with('success', __('User deleted.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => $exception->getMessage()]);
        }
    }
}
