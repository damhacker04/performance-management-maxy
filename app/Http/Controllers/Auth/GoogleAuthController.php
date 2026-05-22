<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user's email exists in our whitelist database
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // If user exists in whitelist, update their google_id and avatar
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);

                // Log the user in
                Auth::login($user);

                // Redirect to intended page or dashboard
                return redirect()->intended(route('dashboard', absolute: false));
            } else {
                // Email not found in whitelist
                return redirect()->route('login')->withErrors([
                    'email' => 'Akun dengan email ' . $googleUser->getEmail() . ' tidak terdaftar dalam sistem.',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Terjadi kesalahan saat otentikasi dengan Google. Silakan coba lagi.',
            ]);
        }
    }
}
