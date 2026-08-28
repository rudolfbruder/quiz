<?php

declare(strict_types=1);

namespace App\Domains\Source\Actions;

use App\Domains\Generation\Jobs\GenerateQuiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Illuminate\Validation\ValidationException;

/**
 * Put a document that failed analysis back in the queue.
 *
 * Only a `failed` source may be retried. A `rejected` one was refused on its own
 * merits — the page limit — and running it again would only spend another
 * generation run to reach the same refusal, so the person deletes it instead.
 */
class RetryGeneration
{
    /**
     * Clear the recorded failure and queue the run again.
     *
     * @throws ValidationException
     */
    public function __invoke(Source $source): void
    {
        if ($source->status !== SourceStatus::Failed) {
            throw ValidationException::withMessages([
                'source' => __('Only a document that failed analysis can be retried; this one is :status.', [
                    'status' => $source->status->value,
                ]),
            ]);
        }

        $source->forceFill([
            'status' => SourceStatus::Pending,
            'failure_reason' => null,
        ])->save();

        dispatch(GenerateQuiz::forSource($source));
    }
}
