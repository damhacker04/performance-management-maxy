<?php
namespace Tests\Feature\Seeder;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_does_not_reset_existing_user_password(): void
    {
        $this->seed(UserSeeder::class);

        $email = 'ika.maxy.academy@gmail.com';
        $user  = User::where('email', $email)->firstOrFail();

        $user->update(['password' => Hash::make('rahasia-baru-ku')]);

        $this->seed(UserSeeder::class);

        $user->refresh();
        $this->assertTrue(
            Hash::check('rahasia-baru-ku', $user->password),
            'Password user TIDAK boleh ter-reset oleh re-seed.'
        );
    }

    public function test_new_user_gets_default_password_on_first_seed(): void
    {
        $this->seed(UserSeeder::class);
        $user = User::where('email', 'ika.maxy.academy@gmail.com')->firstOrFail();
        $this->assertTrue(Hash::check('maxy2026', $user->password));
    }
}
