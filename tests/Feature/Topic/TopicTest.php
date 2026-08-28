<?php

declare(strict_types=1);

use App\Domains\Quiz\Models\Quiz;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Database\Factories\Topic\TopicFactory;
use Illuminate\Support\Str;

it('creates a topic through its factory and reads it back', function (): void {
    $topic = Topic::factory()->create(['name' => 'Roman History']);

    $stored = Topic::query()->findOrFail($topic->getKey());

    expect($stored->name)->toBe('Roman History')
        ->and($stored->slug)->toBe('roman-history')
        ->and($stored->user)->toBeInstanceOf(User::class);
});

it('resolves its factory through the model attribute rather than by convention', function (): void {
    expect(Topic::factory())->toBeInstanceOf(TopicFactory::class);
});

it('derives the slug from the generated name', function (): void {
    $topic = Topic::factory()->create();

    expect($topic->slug)->toBe(Str::slug($topic->name));
});

it('lists the quizzes filed under it', function (): void {
    $topic = Topic::factory()->create();
    Quiz::factory()->count(2)->create(['topic_id' => $topic->getKey(), 'user_id' => $topic->user_id]);

    expect($topic->quizzes()->count())->toBe(2);
});

it('lets two users each hold a topic with the same slug', function (): void {
    $first = Topic::factory()->create(['name' => 'Roman History']);
    $second = Topic::factory()->create(['name' => 'Roman History']);

    expect($first->user_id)->not->toBe($second->user_id)
        ->and($first->slug)->toBe($second->slug);
});
