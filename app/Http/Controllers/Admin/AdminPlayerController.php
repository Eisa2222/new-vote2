<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Actions\CreatePlayerAction;
use App\Modules\Players\Actions\ExportPlayersAction;
use App\Modules\Players\Actions\ImportPlayersAction;
use App\Modules\Players\Actions\UpdatePlayerAction;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Http\Requests\ImportPlayersRequest;
use App\Modules\Players\Http\Requests\StorePlayerRequest;
use App\Modules\Players\Http\Requests\UpdatePlayerRequest;
use App\Modules\Players\Models\Player;
use App\Modules\Sports\Models\Sport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminPlayerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Player::class);

        $players = Player::with(['club', 'sport'])
            ->when($request->filled('club_id'),  fn ($q) => $q->where('club_id',  $request->integer('club_id')))
            ->when($request->filled('position'), fn ($q) => $q->where('position', $request->string('position')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name_ar', 'like', $t)->orWhere('name_en', 'like', $t));
            })
            ->orderByDesc('id')->paginate(config('voting.pagination.players'))->withQueryString();

        return view('admin.players.index', [
            'players'   => $players,
            'clubs'     => Club::orderBy('name_en')->get(),
            'positions' => PlayerPosition::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Player::class);
        return view('admin.players.form', [
            'player'    => new Player(),
            'clubs'     => Club::orderBy('name_en')->get(),
            'sports'    => Sport::orderBy('name_en')->get(),
            'positions' => PlayerPosition::cases(),
        ]);
    }

    public function store(StorePlayerRequest $r, CreatePlayerAction $a): RedirectResponse
    {
        $data = $r->validated();
        $a->execute(Arr::except($data, ['photo']), $r->file('photo'));
        return redirect('/admin/players')->with('success', __('Player created.'));
    }

    public function edit(Player $player): View
    {
        $this->authorize('update', $player);
        return view('admin.players.form', [
            'player'    => $player->load(['club', 'sport']),
            'clubs'     => Club::orderBy('name_en')->get(),
            'sports'    => Sport::orderBy('name_en')->get(),
            'positions' => PlayerPosition::cases(),
        ]);
    }

    public function update(UpdatePlayerRequest $r, Player $player, UpdatePlayerAction $a): RedirectResponse
    {
        $data = $r->validated();
        $a->execute($player, Arr::except($data, ['photo']), $r->file('photo'));
        return redirect('/admin/players')->with('success', __('Player updated.'));
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        $player->delete();
        return redirect('/admin/players')->with('success', __('Player deleted.'));
    }

    // ─── Import / Export ──────────────────────────────────────

    public function export(ExportPlayersAction $action): StreamedResponse
    {
        $this->authorize('viewAny', Player::class);
        return $action->execute();
    }

    public function exportTemplate(ExportPlayersAction $action): StreamedResponse
    {
        $this->authorize('viewAny', Player::class);
        return $action->template();
    }

    public function import(ImportPlayersRequest $request, ImportPlayersAction $action): RedirectResponse
    {
        $result = $action->execute($request->file('file'));
        return $this->importRedirect('/admin/players', $result);
    }

    /**
     * Build the flash redirect for a CSV import result. Shared by
     * players/clubs imports to keep the flash shape identical.
     */
    private function importRedirect(string $path, array $result): RedirectResponse
    {
        $msg = __(':c created, :u updated. :s row(s) skipped.', [
            'c' => $result['created'],
            'u' => $result['updated'],
            's' => count($result['skipped']),
        ]);

        $redirect = redirect($path)->with('success', $msg);
        if (!empty($result['skipped'])) {
            $lines = array_map(fn ($r) => "Row {$r['row']}: {$r['error']}", $result['skipped']);
            $redirect = $redirect->with('import_errors', array_slice($lines, 0, 20));
        }
        return $redirect;
    }

    // ─── Archive hooks (trait: ArchivesResource) ─────────────────

    use \App\Http\Controllers\Admin\Concerns\ArchivesResource;
    protected function archiveModel(): string     { return \App\Modules\Players\Models\Player::class; }
    protected function archiveRouteName(): string { return 'admin.players'; }
    protected function archiveKey(): string       { return 'players'; }
    protected function archiveView(): string      { return 'admin.shared.archive-list'; }
}
