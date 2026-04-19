<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\RedirectResponse;

/**
 * Shared archive methods for admin controllers whose model uses
 * SoftDeletes. Hosts the three routes every module repeats:
 *
 *   GET    {module}/archive           → paginated onlyTrashed() list
 *   POST   {module}/archive/{id}/restore
 *   DELETE {module}/archive/{id}/force
 *
 * Consumers implement two tiny hooks:
 *   archiveModel() : class-string    — the Eloquent model
 *   archiveRouteName() : string      — e.g. 'admin.clubs'
 *   archiveView() : string           — blade view for the list
 *   archiveViewArchive()             — optional
 */
trait ArchivesResource
{
    public function archive(): \Illuminate\Contracts\View\View
    {
        $this->authorizeArchive('viewAny');
        $model = $this->archiveModel();
        $rows  = $model::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(config('voting.pagination.'.$this->archiveKey(), 20));

        return view($this->archiveView(), [
            'rows'   => $rows,
            'module' => $this->archiveKey(),
            'backRoute' => $this->archiveRouteName().'.index',
            'restoreRoute' => $this->archiveRouteName().'.restore',
            'forceRoute'   => $this->archiveRouteName().'.forceDelete',
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorizeArchive('restore');
        $model = $this->archiveModel();
        $row   = $model::onlyTrashed()->findOrFail($id);
        $row->restore();
        return redirect()->route($this->archiveRouteName().'.archive')
            ->with('success', __(':label restored.', ['label' => __(ucfirst($this->archiveKey()))]));
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorizeArchive('forceDelete');
        $model = $this->archiveModel();
        $row   = $model::onlyTrashed()->findOrFail($id);
        $row->forceDelete();
        return redirect()->route($this->archiveRouteName().'.archive')
            ->with('success', __(':label permanently deleted.', ['label' => __(ucfirst($this->archiveKey()))]));
    }

    // Hooks the consuming controller overrides — kept abstract-like
    // via plain assertions so this trait works on any Controller
    // without requiring an interface.
    abstract protected function archiveModel(): string;
    abstract protected function archiveRouteName(): string;
    abstract protected function archiveKey(): string;
    abstract protected function archiveView(): string;

    protected function authorizeArchive(string $verb): void
    {
        $perm = $this->archiveKey().'.'.$verb;
        abort_unless(auth()->user()?->can($perm), 403);
    }
}
