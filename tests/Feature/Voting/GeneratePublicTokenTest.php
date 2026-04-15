<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\GenerateVotingPublicTokenAction;
use App\Modules\Campaigns\Models\Campaign;

it('generates a unique public token and persists it', function () {
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'draft',
    ]);
    $original = $c->public_token;

    $new = (new GenerateVotingPublicTokenAction())->execute($c);

    expect($new)->not->toBe($original);
    expect(strlen($new))->toBe(48);
    expect($c->fresh()->public_token)->toBe($new);
});

it('tokens are unique across many regenerations', function () {
    $tokens = [];
    foreach (range(1, 20) as $i) {
        $c = Campaign::create([
            'title_ar' => "c{$i}", 'title_en' => "c{$i}",
            'type' => 'individual_award',
            'start_at' => now(), 'end_at' => now()->addDay(),
            'status' => 'draft',
        ]);
        $tokens[] = (new GenerateVotingPublicTokenAction())->execute($c);
    }
    expect($tokens)->toHaveCount(count(array_unique($tokens)));
});
