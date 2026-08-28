<?php

declare(strict_types=1);

namespace App\Domains\Source\Models;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Policies\SourcePolicy;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Source\SourceFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One uploaded document, together with the lifecycle of its analysis.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $topic_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $media_type
 * @property int $size_bytes
 * @property int|null $page_count
 * @property string|null $extracted_text
 * @property string|null $provider_file_id
 * @property SourceStatus $status
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $analysed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(SourceFactory::class)]
#[UsePolicy(SourcePolicy::class)]
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user who uploaded this source.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The topic this source was filed under, once one is known.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The candidate quiz outlines analysis produced for this source.
     *
     * @return HasMany<QuizProposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(QuizProposal::class)->orderBy('position');
    }

    /**
     * The quiz generated from this source, once generation has produced one.
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
            'size_bytes' => 'integer',
            'page_count' => 'integer',
            'status' => SourceStatus::class,
            'analysed_at' => 'datetime',
        ];
    }
}
