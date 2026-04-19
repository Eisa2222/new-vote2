<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Clubs\Actions\CreateClubAction;
use App\Modules\Clubs\Actions\ExportClubsAction;
use App\Modules\Clubs\Actions\ImportClubsAction;
use App\Modules\Clubs\Actions\UpdateClubAction;
use App\Modules\Clubs\Http\Requests\ImportClubsRequest;
use App\Modules\Clubs\Http\Requests\StoreClubRequest;
use App\Modules\Clubs\Http\Requests\UpdateClubRequest;
use App\Modules\Clubs\Models\Club;
use App\Modules\Sports\Models\Sport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminClubController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Club::class);

        $clubs = Club::with('sports')
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(config('voting.pagination.clubs'))
            ->withQueryString();

        return view('admin.clubs.index', compact('clubs'));
    }

    public function create(): View
    {
        $this->authorize('create', Club::class);
        return view('admin.clubs.form', [
            'club'    => new Club(),
            'sports'  => Sport::all(),
            'leagues' => \App\Modules\Leagues\Models\League::with('sport')->orderBy('name_en')->get(),
        ]);
    }

    public function store(StoreClubRequest $r, CreateClubAction $a): RedirectResponse
    {
        $data = $r->validated();
        $club = $a->execute(\Illuminate\Support\Arr::except($data, ['logo', 'sport_ids', 'league_ids']),
                    $r->file('logo'), $data['sport_ids'] ?? []);
        if ($r->has('league_ids')) {
            $club->leagues()->sync($r->input('league_ids', []));
        }
        return redirect('/admin/clubs')->with('success', __('Club created.'));
    }

    public function edit(Club $club): View
    {
        $this->authorize('update', $club);
        return view('admin.clubs.form', [
            'club'    => $club->load('sports', 'leagues'),
            'sports'  => Sport::all(),
            'leagues' => \App\Modules\Leagues\Models\League::with('sport')->orderBy('name_en')->get(),
        ]);
    }

    public function update(UpdateClubRequest $r, Club $club, UpdateClubAction $a): RedirectResponse
    {
        $data = $r->validated();
        $a->execute($club, \Illuminate\Support\Arr::except($data, ['logo', 'sport_ids', 'league_ids']),
                    $r->file('logo'), $data['sport_ids'] ?? null);
        if ($r->has('league_ids')) {
            $club->leagues()->sync($r->input('league_ids', []));
        }
        return redirect('/admin/clubs')->with('success', __('Club updated.'));
    }

    public function toggle(Club $club): RedirectResponse
    {
        $this->authorize('update', $club);
        $next = $club->status->value === 'active' ? 'inactive' : 'active';
        $club->update(['status' => $next]);
        $msg = $next === 'active' ? __('Club activated.') : __('Club deactivated.');
        return back()->with('success', $msg);
    }

    public function destroy(Club $club, \App\Modules\Clubs\Actions\DeleteClubAction $action): RedirectResponse
    {
        $this->authorize('delete', $club);
        if ($club->players()->exists()) {
            return back()->withErrors([
                'club' => __('Cannot delete a club with linked players. Reassign or delete the players first.'),
            ]);
        }
        $action->execute($club);
        return redirect('/admin/clubs')->with('success', __('Club deleted.'));
    }

    // ─── Import / Export ──────────────────────────────────────

    public function export(ExportClubsAction $action): StreamedResponse
    {
        $this->authorize('viewAny', Club::class);
        return $action->execute();
    }

    public function exportTemplate(ExportClubsAction $action): StreamedResponse
    {
        $this->authorize('viewAny', Club::class);
        return $action->template();
    }

    public function import(ImportClubsRequest $request, ImportClubsAction $action): RedirectResponse
    {
        $result = $action->execute($request->file('file'));

        $msg = __(':c created, :u updated. :s row(s) skipped.', [
            'c' => $result['created'],
            'u' => $result['updated'],
            's' => count($result['skipped']),
        ]);

        $redirect = redirect('/admin/clubs')->with('success', $msg);
        if (!empty($result['skipped'])) {
            $lines = array_map(fn ($r) => "Row {$r['row']}: {$r['error']}", $result['skipped']);
            $redirect = $redirect->with('import_errors', array_slice($lines, 0, 20));
        }
        return $redirect;
    }

    // ─── Archive hooks (trait: ArchivesResource) ─────────────────

    use \App\Http\Controllers\Admin\Concerns\ArchivesResource;
    protected function archiveModel(): string     { return \App\Modules\Clubs\Models\Club::class; }
    protected function archiveRouteName(): string { return 'admin.clubs'; }
    protected function archiveKey(): string       { return 'clubs'; }
    protected function archiveView(): string      { return 'admin.shared.archive-list'; }
}
