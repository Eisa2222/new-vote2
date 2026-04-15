<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Actions\VerifyVoterIdentityAction;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Support\IdentityNormalizer;

function makeVotingCampaignWithPlayer(): array
{
    seedRolesAndPermissions();
    $p = makePlayer([
        'national_id'   => '1234567890',
        'mobile_number' => '0501234567',
    ]);
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        'status'   => 'active',
    ]);
    $cat = $c->categories()->create([
        'title_ar' => 'a', 'title_en' => 'A',
        'position_slot' => 'any', 'required_picks' => 1,
        'selection_min' => 1, 'selection_max' => 1, 'is_active' => true,
    ]);
    $cand = $cat->candidates()->create([
        'candidate_type' => 'player', 'player_id' => $p->id, 'is_active' => true,
    ]);
    return [$c->load('categories.candidates'), $p, $cat, $cand];
}

it('player can verify by national id', function () {
    [$c, $p] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1234567890'])
        ->assertRedirect(route('voting.form', $c->public_token));
});

it('player can verify by mobile number', function () {
    [$c, $p] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.verify', $c->public_token), ['mobile' => '0501234567'])
        ->assertRedirect(route('voting.form', $c->public_token));
});

it('mobile is normalized — +966 prefix accepted', function () {
    [$c] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.verify', $c->public_token), ['mobile' => '+966501234567'])
        ->assertRedirect(route('voting.form', $c->public_token));
});

it('rejects unknown identity with generic message', function () {
    [$c] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '9999999999'])
        ->assertSessionHasErrors(['identity']);
});

it('verification requires at least one of national_id or mobile', function () {
    [$c] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.verify', $c->public_token), [])
        ->assertSessionHasErrors();
});

it('vote form is gated behind verification', function () {
    [$c] = makeVotingCampaignWithPlayer();
    $this->get(route('voting.form', $c->public_token))
        ->assertRedirect(route('voting.show', $c->public_token));
});

it('verified player can submit vote', function () {
    [$c, $p, $cat, $cand] = makeVotingCampaignWithPlayer();

    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1234567890']);

    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ])->assertRedirect(route('voting.thanks', $c->public_token));

    $vote = \App\Modules\Voting\Models\Vote::first();
    expect($vote->verified_player_id)->toBe($p->id);
    expect($vote->is_verified)->toBeTrue();
    expect($vote->verification_method?->value)->toBe('national_id');
    expect($vote->verification_value)->toMatch('/^\*+\d{4}$/');
});

it('player cannot vote twice in the same campaign', function () {
    [$c, $p, $cat, $cand] = makeVotingCampaignWithPlayer();

    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1234567890']);
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ])->assertRedirect();

    // Second attempt — player should be blocked at the verify step
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1234567890'])
        ->assertSessionHasErrors(['identity']);
});

it('submit without verification is rejected', function () {
    [$c, $p, $cat, $cand] = makeVotingCampaignWithPlayer();
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ])->assertSessionHasErrors();
    expect(\App\Modules\Voting\Models\Vote::count())->toBe(0);
});

it('VerifyVoterIdentityAction returns the player and method', function () {
    [$c, $p] = makeVotingCampaignWithPlayer();
    $r = (new VerifyVoterIdentityAction())->execute('1234567890', null);
    expect($r['player']->id)->toBe($p->id);
    expect($r['method']->value)->toBe('national_id');
});

it('VerifyVoterIdentityAction throws on miss', function () {
    [$c] = makeVotingCampaignWithPlayer();
    (new VerifyVoterIdentityAction())->execute('9999999999', null);
})->throws(VotingException::class);

it('mask shows only last 4 digits', function () {
    expect(IdentityNormalizer::mask('1234567890'))->toBe('******7890');
    expect(IdentityNormalizer::mask('0501234567'))->toBe('******4567');
    expect(IdentityNormalizer::mask('123'))->toBe('***');
});

it('inactive player cannot verify', function () {
    [$c, $p] = makeVotingCampaignWithPlayer();
    $p->update(['status' => 'inactive']);
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1234567890'])
        ->assertSessionHasErrors(['identity']);
});
