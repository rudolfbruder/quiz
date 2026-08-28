<?php

declare(strict_types=1);

namespace Database\Seeders\User;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The single demo account every other seeder hangs its fixtures off.
 */
class UserSeeder extends Seeder
{
    /**
     * The address the demo account is identified by. Seeding is keyed on it, so the
     * account survives repeated runs rather than being duplicated.
     */
    public const DEMO_EMAIL = 'test@example.com';

    public const DEMO_NAME = 'Test User';

    public const DEMO_PASSWORD = 'password';

    /**
     * Seed the demo account.
     */
    public function run(): void
    {
        self::demoUser();
    }

    /**
     * The demo account, created on first call and returned unchanged afterwards.
     *
     * Password and verification are only written when the account is new, so a
     * developer who has changed either keeps their change across a re-seed.
     */
    public static function demoUser(): User
    {
        $user = User::query()->firstOrNew(['email' => self::DEMO_EMAIL]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => self::DEMO_NAME,
                'email' => self::DEMO_EMAIL,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ])->save();
        }

        return $user;
    }
}
