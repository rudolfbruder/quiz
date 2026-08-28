<?php

declare(strict_types=1);

use App\Domains\Generation\Jobs\GenerateQuiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('clears the failure reason and queues the run again', function (): void {
    Queue::fake();

    $source = Source::factory()->failed()->create();

    $this->actingAs($source->user)
        ->post(route('sources.retry', $source))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $source->refresh();

    expect($source->failure_reason)->toBeNull()
        ->and($source->status)->toBe(SourceStatus::Pending);

    Queue::assertPushed(GenerateQuiz::class);
});

it('refuses to retry a source that did not fail, naming the state', function (): void {
    Queue::fake();

    $source = Source::factory()->rejected()->create();
    $reason = $source->failure_reason;

    $this->actingAs($source->user)
        ->post(route('sources.retry', $source))
        ->assertSessionHasErrors('source');

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Rejected)
        ->and($source->failure_reason)->toBe($reason);

    Queue::assertNothingPushed();
});

it('refuses to retry a source belonging to someone else', function (): void {
    Queue::fake();

    $source = Source::factory()->failed()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('sources.retry', $source))
        ->assertForbidden();

    expect($source->refresh()->status)->toBe(SourceStatus::Failed);

    Queue::assertNothingPushed();
});

it('sends an anonymous retry to the login page', function (): void {
    Queue::fake();

    $source = Source::factory()->failed()->create();

    $this->post(route('sources.retry', $source))->assertRedirect(route('login'));

    Queue::assertNothingPushed();
});
