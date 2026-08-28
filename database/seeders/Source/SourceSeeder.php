<?php

declare(strict_types=1);

namespace Database\Seeders\Source;

use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use Database\Seeders\Topic\TopicSeeder;
use Database\Seeders\User\UserSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * The two documents the demo topic was built from. Both are already analysed, so the
 * proposal, quiz and question fixtures downstream have something to hang off.
 */
class SourceSeeder extends Seeder
{
    /**
     * The demo documents, keyed by the name their fixtures are filed under.
     *
     * @var array<string, array{original_name: string, media_type: string, size_bytes: int, page_count: int}>
     */
    public const DOCUMENTS = [
        'the-roman-constitution' => [
            'original_name' => 'the-roman-constitution.pdf',
            'media_type' => 'application/pdf',
            'size_bytes' => 1_482_112,
            'page_count' => 18,
        ],
        'the-punic-wars' => [
            'original_name' => 'the-punic-wars-and-the-fall-of-the-republic.pdf',
            'media_type' => 'application/pdf',
            'size_bytes' => 2_143_744,
            'page_count' => 22,
        ],
    ];

    /**
     * Seed both demo documents.
     */
    public function run(): void
    {
        self::demoSources();
    }

    /**
     * The demo documents, created on first call and returned unchanged afterwards.
     *
     * @return Collection<int, Source>
     */
    public static function demoSources(): Collection
    {
        $user = UserSeeder::demoUser();
        $topic = TopicSeeder::demoTopic();

        $sources = new Collection;

        foreach (self::DOCUMENTS as $key => $document) {
            $sources->push(Source::query()->firstOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'path' => self::pathFor($key),
                ],
                [
                    'topic_id' => $topic->getKey(),
                    'disk' => 'local',
                    'original_name' => $document['original_name'],
                    'media_type' => $document['media_type'],
                    'size_bytes' => $document['size_bytes'],
                    'page_count' => $document['page_count'],
                    'status' => SourceStatus::Ready,
                    'analysed_at' => now()->subDays(30),
                ],
            ));
        }

        return $sources;
    }

    /**
     * Where the document with the given fixture key is stored.
     */
    public static function pathFor(string $key): string
    {
        return "sources/demo/{$key}.pdf";
    }

    /**
     * The fixture key the given source's proposals and questions are filed under.
     */
    public static function keyFor(Source $source): string
    {
        return basename($source->path, '.pdf');
    }
}
