<?php

declare(strict_types=1);

namespace App\Domains\Question\Models;

use Carbon\CarbonImmutable;
use Database\Factories\Question\OptionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer choice on a question, carrying its own correctness flag.
 *
 * @property int $id
 * @property int $question_id
 * @property string $text
 * @property bool $is_correct
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(OptionFactory::class)]
class Option extends Model
{
    /** @use HasFactory<OptionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The question this option belongs to.
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'position' => 'integer',
        ];
    }
}
