<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;

function draftC(): Campaign
{
    return Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'draft',
    ]);
}

it('admin can add a category to a campaign', function () {
    $c = draftC();
    $this->actingAs(makeSuperAdmin())
        ->post("/admin/campaigns/{$c->id}/categories", [
            'title_ar' => 'الأفضل', 'title_en' => 'Best',
            'category_type' => 'single_choice',
            'position_slot' => 'any',
            'selection_min' => 1, 'selection_max' => 1,
        ])
        ->assertRedirect();

    expect($c->categories()->count())->toBe(1);
});

it('rejects selection_max less than selection_min', function () {
    $c = draftC();
    $this->actingAs(makeSuperAdmin())
        ->post("/admin/campaigns/{$c->id}/categories", [
            'title_ar' => 'x', 'title_en' => 'x',
            'category_type' => 'multiple_choice',
            'position_slot' => 'any',
            'selection_min' => 3, 'selection_max' => 2,
        ])
        ->assertSessionHasErrors(['selection_max']);
});

it('admin can attach a player candidate to a category', function () {
    $c = draftC();
    $cat = $c->categories()->create([
        'title_ar' => 'x', 'title_en' => 'x',
        'position_slot' => 'any', 'required_picks' => 1, 'is_active' => true,
    ]);
    $p = makePlayer();

    $this->actingAs(makeSuperAdmin())
        ->post("/admin/categories/{$cat->id}/candidates", [
            'candidate_type' => 'player',
            'candidate_id' => $p->id,
        ])
        ->assertRedirect();

    expect($cat->candidates()->count())->toBe(1);
    expect($cat->candidates->first()->player_id)->toBe($p->id);
});
