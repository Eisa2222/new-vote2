<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Users\Actions\CreateRoleAction;
use App\Modules\Users\Actions\DeleteRoleAction;
use App\Modules\Users\Actions\UpdateRoleAction;
use App\Modules\Users\Http\Requests\StoreRoleRequest;
use App\Modules\Users\Http\Requests\UpdateRoleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class AdminRoleController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
    }

    public function index(): View
    {
        $this->authorizeManage();
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles'          => $roles,
            'permissionTree' => $this->groupedPermissions(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeManage();
        return view('admin.roles.form', [
            'role'           => new Role(['name' => '']),
            'selected'       => [],
            'permissionTree' => $this->groupedPermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): RedirectResponse
    {
        $action->execute($request->string('name')->toString(), (array) $request->input('permissions', []));
        return redirect()->route('admin.roles.index')->with('success', __('Role created.'));
    }

    public function edit(Role $role): View
    {
        $this->authorizeManage();
        return view('admin.roles.form', [
            'role'           => $role->load('permissions'),
            'selected'       => $role->permissions->pluck('name')->all(),
            'permissionTree' => $this->groupedPermissions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $action->execute($role, (array) $request->input('permissions', []));
        return redirect()->route('admin.roles.index')->with('success', __('Role updated.'));
    }

    public function destroy(Role $role, DeleteRoleAction $action): RedirectResponse
    {
        $this->authorizeManage();
        try {
            $action->execute($role);
            return redirect()->route('admin.roles.index')->with('success', __('Role deleted.'));
        } catch (\DomainException $exception) {
            return redirect()->route('admin.roles.index')->withErrors(['role' => $exception->getMessage()]);
        }
    }

    /**
     * Groups every registered permission by its dotted namespace
     * (e.g. "clubs.create" → "clubs"), so the form can render a
     * section per module instead of a flat 20-item checkbox list.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function groupedPermissions(): \Illuminate\Support\Collection
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0]);
    }
}
