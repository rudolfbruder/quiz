<?php

declare(strict_types=1);

namespace App\Domains\Generation\Models;

use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Models\Source;
use Carbon\CarbonImmutable;
use Database\Factories\Generation\QuizProposalFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One AI-suggested outline for a source. Exactly one proposal per source is accepted.
 *
 * @property int $id
 * @property int $source_id
 * @property int $position
 * @property string $title
 * @property string $angle
 * @property string $summary
 * @property string|null $suggested_topic_name
 * @property int $suggested_question_count
 * @property array<int, array{title: string, focus: string}> $outline
 * @property string|null $language
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(QuizProposalFactory::class)]
class QuizProposal extends Model
{
    /** @use HasFactory<QuizProposalFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The source this proposal was derived from.
     *
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * The quiz this proposal was expanded into, once it has been accepted.
     *
     * @return HasOne<Quiz, $this>
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'suggested_question_count' => 'integer',
            'outline' => 'array',
            'accepted_at' => 'datetime',
        ];
    }
}
