<?php

declare(strict_types=1);

namespace App\Modules\Leagues\Actions;

use App\Modules\Leagues\Models\League;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DeleteLeagueAction
{
    public function execute(League $league): void
    {
        if ($league->campaigns()->exists()) {
            throw new DomainException(__('Cannot delete a league that has campaigns.'));
        }

        DB::transaction(function () use ($league) {
            $league->clubs()->detach();
            $league->delete();
        });
    }
}
