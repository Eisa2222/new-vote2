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
use Illuminate\Http\Request;
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

        return view('admin.users.form', [
            'user'  => new User(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $creator): RedirectResponse
    {
        $result = $creator->execute($request->validated());

        // Tell the admin what happened: direct-create vs invite-sent.
        $message = $result['invited']
            ? __('User created. An invitation email has been sent to :email.', ['email' => $result['user']->email])
            : __('User created.');

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
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
            return redirect()->route('admin.users.index')->with('success', __('User archived.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => $exception->getMessage()]);
        }
    }

    // ─── Bulk ops ───────────────────────────────────────────────
    // One-click archive of many users at once. The request carries
    // `ids[]` of selected rows; the action loops through them and
    // skips ones that can't be archived (self, last super_admin).

    public function bulkDelete(Request $request, DeleteUserAction $deleter): RedirectResponse
    {
        $this->authorizeManage();
        $ids = $request->array('ids');
        $archived = 0; $skipped = 0; $reasons = [];

        foreach (User::whereIn('id', $ids)->get() as $user) {
            try {
                $deleter->execute($user);
                $archived++;
            } catch (DomainException $e) {
                $skipped++;
                $reasons[] = $user->email.' — '.$e->getMessage();
            }
        }

        $msg = __(':n user(s) archived.', ['n' => $archived]);
        $redirect = redirect()->route('admin.users.index')->with('success', $msg);
        if ($skipped > 0) {
            $redirect = $redirect->with('warning', __(':n skipped.', ['n' => $skipped]))
                                 ->with('bulk_errors', array_slice($reasons, 0, 5));
        }
        return $redirect;
    }

    // ─── Archive (soft-deleted) ─────────────────────────────────

    public function archive(): View
    {
        $this->authorizeManage();
        $users = User::onlyTrashed()
            ->with('roles')
            ->orderByDesc('deleted_at')
            ->paginate(config('voting.pagination.users'));
        return view('admin.users.archive', compact('users'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorizeManage();
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        return redirect()->route('admin.users.archive')->with('success', __('User restored.'));
    }

    public function forceDelete(int $id): RedirectResponse
    {
        // Permanent destruction is super_admin-only. A non-privileged
        // admin can archive (soft-delete) but never hard-delete.
        abort_unless(auth()->user()?->can('users.forceDelete'), 403);
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();
        return redirect()->route('admin.users.archive')->with('success', __('User permanently deleted.'));
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $count = User::onlyTrashed()->whereIn('id', $request->array('ids'))->restore();
        return redirect()->route('admin.users.archive')->with('success', __(':n user(s) restored.', ['n' => $count]));
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.forceDelete'), 403);
        $count = User::onlyTrashed()->whereIn('id', $request->array('ids'))->forceDelete();
        return redirect()->route('admin.users.archive')->with('success', __(':n user(s) permanently deleted.', ['n' => $count]));
    }
}
