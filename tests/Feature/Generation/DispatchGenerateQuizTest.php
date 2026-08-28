<?php

declare(strict_types=1);

use App\Domains\Generation\Agents\GenerateQuizFromSource;
use App\Domains\Generation\Jobs\GenerateQuiz;
use App\Domains\Source\Models\Source;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('queues the generation instead of running it while the person waits', function (): void {
    Storage::fake('local');
    Queue::fake();
    GenerateQuizFromSource::fake()->preventStrayPrompts();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sources.store'), [
            'file' => UploadedFile::fake()->create('lecture.pdf', 40, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    GenerateQuizFromSource::assertNeverPrompted();

    $source = Source::query()->sole();

    Queue::assertPushed(
        GenerateQuiz::class,
        fn (GenerateQuiz $job): bool => $job->sourceId === $source->id && $job->userId === $user->id,
    );
});
