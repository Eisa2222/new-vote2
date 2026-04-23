<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Shared\Enums\ActiveStatus;
use App\Modules\Sports\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'club_id'       => Club::factory(),
            'sport_id'      => fn () => Sport::firstOrCreate(
                ['slug' => 'football'],
                ['name_ar' => 'كرة القدم', 'name_en' => 'Football', 'status' => 'active'],
            )->id,
            'name_ar'       => 'لاعب '.fake()->firstNameMale(),
            'name_en'       => fake()->name('male'),
            'position'      => fake()->randomElement(PlayerPosition::cases()),
            'nationality'   => fake()->randomElement(NationalityType::cases()),
            'is_captain'    => false,
            'jersey_number' => fake()->unique()->numberBetween(1, 99),
            'status'        => ActiveStatus::Active,
        ];
    }
}
