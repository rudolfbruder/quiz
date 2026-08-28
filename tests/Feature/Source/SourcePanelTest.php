<?php

declare(strict_types=1);

use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('sends an anonymous visitor to the login page', function (): void {
    $this->get(route('sources.index'))->assertRedirect(route('login'));
});

it('lists the uploads and the topics belonging to the signed-in person', function (): void {
    $user = User::factory()->create();
    Source::factory()->count(2)->create(['user_id' => $user->id]);
    Topic::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('source/Index')
            ->has('sources', 2)
            ->has('topics', 1)
            ->has('limits'),
        );
});

it('leaves another person\'s uploads and topics out of the panel', function (): void {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $mine = Source::factory()->create(['user_id' => $user->id, 'original_name' => 'mine.pdf']);
    Source::factory()->create(['user_id' => $stranger->id, 'original_name' => 'theirs.pdf']);

    Topic::factory()->create(['user_id' => $user->id, 'name' => 'Mine']);
    Topic::factory()->create(['user_id' => $stranger->id, 'name' => 'Theirs']);

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sources', 1)
            ->where('sources.0.id', $mine->id)
            ->where('sources.0.originalName', 'mine.pdf')
            ->has('topics', 1)
            ->where('topics.0.name', 'Mine'),
        );
});

it('states the accepted types and the size limit from configuration', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('limits.acceptedExtensions', config('generation.accepted_extensions'))
            ->where('limits.acceptedMediaTypes', config('generation.accepted_media_types'))
            ->where('limits.maxUploadKilobytes', (int) config('generation.max_upload_kilobytes'))
            ->where('limits.maxPages', (int) config('generation.max_pages')),
        );
});

it('carries the quiz title and question count of a ready upload', function (): void {
    $user = User::factory()->create();
    $source = Source::factory()->ready()->create(['user_id' => $user->id]);

    $quiz = Quiz::factory()->create([
        'source_id' => $source->id,
        'user_id' => $user->id,
        'title' => 'The Roman Republic',
    ]);
    Question::factory()->count(6)->create(['quiz_id' => $quiz->id]);

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sources.0.status', SourceStatus::Ready->value)
            ->where('sources.0.quizTitle', 'The Roman Republic')
            ->where('sources.0.questionCount', 6)
            ->where('sources.0.pageCount', $source->page_count),
        );
});

it('adds up the questions a topic holds across its quizzes', function (): void {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['user_id' => $user->id, 'name' => 'The Roman Republic']);

    foreach ([4, 5] as $questionCount) {
        $source = Source::factory()->ready()->create(['user_id' => $user->id]);
        $quiz = Quiz::factory()->create([
            'source_id' => $source->id,
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);
        Question::factory()->count($questionCount)->create(['quiz_id' => $quiz->id]);
    }

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('topics', 1)
            ->where('topics.0.name', 'The Roman Republic')
            ->where('topics.0.quizCount', 2)
            ->where('topics.0.questionCount', 9),
        );
});

it('shows the failure reason of a failed upload', function (): void {
    $user = User::factory()->create();
    Source::factory()->failed()->create([
        'user_id' => $user->id,
        'failure_reason' => 'The provider could not read the document.',
    ]);

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sources.0.status', SourceStatus::Failed->value)
            ->where('sources.0.failureReason', 'The provider could not read the document.'),
        );
});

it('carries empty collections for a person who has uploaded nothing', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('sources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('source/Index')
            ->where('sources', [])
            ->where('topics', []),
        );
});

it('reloads only the uploads when the page polls', function (): void {
    $user = User::factory()->create();
    Source::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('sources.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sources', 1)
            ->reloadOnly('sources', fn (Assert $reload) => $reload
                ->has('sources', 1)
                ->missing('topics'),
            ),
        );
});
