<?php

declare(strict_types=1);

namespace App\Domains\Attempt\Models;

use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use Carbon\CarbonImmutable;
use Database\Factories\Attempt\ResponseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question served inside an attempt. The row is written when the question is
 * served and filled in when it is answered, so an abandoned question still counts
 * as having been shown.
 *
 * @property int $id
 * @property int $attempt_id
 * @property int $question_id
 * @property int|null $option_id
 * @property int $position
 * @property bool|null $is_correct
 * @property int|null $answered_in_milliseconds
 * @property CarbonImmutable $served_at
 * @property CarbonImmutable|null $answered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(ResponseFactory::class)]
class Response extends Model
{
    /** @use HasFactory<ResponseFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The attempt this response belongs to.
     *
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * The question that was served.
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * The option the user picked, absent until the question is answered.
     *
     * @return BelongsTo<Option, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
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
            'is_correct' => 'boolean',
            'answered_in_milliseconds' => 'integer',
            'served_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }
}
