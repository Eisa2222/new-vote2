<?php

declare(strict_types=1);

namespace App\Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];
}
