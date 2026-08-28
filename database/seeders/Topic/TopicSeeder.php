<?php

declare(strict_types=1);

namespace Database\Seeders\Topic;

use App\Domains\Topic\Models\Topic;
use Database\Seeders\User\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * The one topic the demo fixtures study. Everything downstream — sources, proposals,
 * quizzes, attempts and review state — is about this same material.
 */
class TopicSeeder extends Seeder
{
    public const DEMO_NAME = 'The Roman Republic';

    public const DEMO_SLUG = 'the-roman-republic';

    /**
     * Seed the demo topic.
     */
    public function run(): void
    {
        self::demoTopic();
    }

    /**
     * The demo topic, created on first call and returned unchanged afterwards.
     */
    public static function demoTopic(): Topic
    {
        $user = UserSeeder::demoUser();

        return Topic::query()->firstOrCreate(
            [
                'user_id' => $user->getKey(),
                'slug' => self::DEMO_SLUG,
            ],
            [
                'name' => self::DEMO_NAME,
            ],
        );
    }
}
