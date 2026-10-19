<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MagicLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear(
            'magic-email:'.hash('sha256', 'nobody@example.test')
        );
    }

    private function makeToken(User $user, int $minutes = 15): string
    {
        $token = bin2hex(random_bytes(32));

        DB::table('magic_login_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($minutes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    public function test_unknown_email_receives_generic_confirmation(): void
    {
        Mail::fake();

        $this->post('/login/email', [
            'email' => 'nobody@example.test',
        ])->assertRedirect()
          ->assertSessionHas('status');

        $this->assertDatabaseCount('magic_login_tokens', 0);
    }

    public function test_known_email_creates_hashed_token(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/login/email', [
            'email' => $user->email,
        ])->assertRedirect()
          ->assertSessionHas('status');

        $this->assertDatabaseHas('magic_login_tokens', [
            'user_id' => $user->id,
        ]);

        $this->assertEquals(
            64,
            strlen(DB::table('magic_login_tokens')->value('token_hash'))
        );
    }

    public function test_opening_link_does_not_log_user_in(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $this->get("/login/email/{$token}")->assertOk();

        $this->assertGuest();
        $this->assertDatabaseCount('magic_login_tokens', 1);
    }

    public function test_posting_valid_token_logs_user_in(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $this->post("/login/email/{$token}")
            ->assertRedirect(route('setup'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('magic_login_tokens', 0);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user, -1);

        $this->post("/login/email/{$token}")
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = $this->makeToken($user);

        $this->post("/login/email/{$token}")
            ->assertRedirect(route('setup'));

        $this->post('/logout')->assertRedirect(route('home'));

        $this->post("/login/email/{$token}")
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_email_request_limit(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $key = 'magic-email:'.hash('sha256', strtolower($user->email));
        RateLimiter::clear($key);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login/email', [
                'email' => $user->email,
            ])->assertSessionHas('status');
        }

        $this->assertSame(3, RateLimiter::attempts($key));
        $this->assertDatabaseCount('magic_login_tokens', 1);

        RateLimiter::clear($key);
    }

    public function test_mail_failure_removes_token(): void
    {
        $user = User::factory()->create();

        $key = 'magic-email:'.hash('sha256', strtolower($user->email));
        RateLimiter::clear($key);

        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('Test mail failure'));

        $this->post('/login/email', [
            'email' => $user->email,
        ])->assertSessionHas('status');

        $this->assertDatabaseMissing('magic_login_tokens', [
            'user_id' => $user->id,
        ]);

        RateLimiter::clear($key);
    }

}
