<?php

declare(strict_types=1);

namespace App\Modules\Sports\Actions;

use App\Modules\Sports\Models\Sport;
use DomainException;

final class DeleteSportAction
{
    public function execute(Sport $sport): void
    {
        if ($sport->clubs()->exists()) {
            throw new DomainException(__('Cannot delete a sport that is linked to clubs.'));
        }

        $sport->delete();
    }
}
