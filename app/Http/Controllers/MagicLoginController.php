<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MagicLoginController extends Controller
{
    private function validToken(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($data['email']));
        $key = 'magic-email:'.hash('sha256', $email);

        $message = 'If that email belongs to an account, a sign-in link is on its way.';

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('status', $message);
        }

        RateLimiter::hit($key, 3600);

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);

            DB::table('magic_login_tokens')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'token_hash' => $hash,
                    'expires_at' => now()->addMinutes(15),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                Mail::raw(
                    "Use this link to sign in to ContractorSpecialties:\n\n"
                    .route('magic-login.confirm', ['token' => $token])
                    ."\n\nThis link expires in 15 minutes and works only once.\n"
                    ."If you didn't request it, ignore this email.",
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('Your ContractorSpecialties sign-in link');
                    }
                );
            } catch (\Throwable $e) {
                DB::table('magic_login_tokens')
                    ->where('user_id', $user->id)
                    ->where('token_hash', $hash)
                    ->delete();

                report($e);
            }
        }

        return back()->with('status', $message);
    }

    public function confirm(string $token): \Illuminate\Http\Response|RedirectResponse
    {
        if (! $this->validToken($token)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This sign-in link is invalid.']);
        }

        return response()
            ->view('auth.magic-confirm', compact('token'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        if (! $this->validToken($token)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This sign-in link is invalid or expired.']);
        }

        $hash = hash('sha256', $token);

        $userId = DB::transaction(function () use ($hash) {
            $record = DB::table('magic_login_tokens')
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if (! $record || now()->greaterThanOrEqualTo($record->expires_at)) {
                return null;
            }

            DB::table('magic_login_tokens')
                ->where('id', $record->id)
                ->delete();

            return $record->user_id;
        });

        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This sign-in link is invalid or expired.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route(
            $user->onboarding_completed_at ? 'dashboard' : 'setup'
        );
    }
}
