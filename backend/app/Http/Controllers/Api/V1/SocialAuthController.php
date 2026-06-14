<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuthProvider;
use App\Enums\FrontendPath;
use App\Http\Controllers\Controller;
use App\Services\Auth\Social\SocialAuthFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(AuthProvider $provider): RedirectResponse
    {
        return Socialite::driver($provider->value)->redirect();
    }

    public function auth(AuthProvider $provider, Request $request, SocialAuthFactory $factory): RedirectResponse
    {
        try {
            if ($request->has('error')) {
                return $this->redirectOnProviderError($provider, $request);
            }

            $providerUser = Socialite::driver($provider->value)->user();

            $user = $factory->make($provider)->handleCallback($providerUser);

            Auth::login($user);

            $request->session()->regenerate();

            return redirect(url()->query($this->frontendUrl().FrontendPath::AuthCallback->value));

        } catch (Throwable $e) {
            Log::error($e->getMessage(), $e->getTrace());

            return $this->redirectOnFailure();
        }
    }

    protected function redirectOnProviderError(AuthProvider $provider, Request $request): RedirectResponse
    {
        $params = ['provider' => $provider->value]
            + $request->only(['error', 'error_description']);

        return redirect(url()->query($this->frontendUrl().FrontendPath::Login->value, $params));
    }

    protected function redirectOnFailure(): RedirectResponse
    {
        return redirect(url()->query($this->frontendUrl().FrontendPath::Login->value, ['error' => 'Authentication failed']));
    }

    protected function frontendUrl(): string
    {
        return config('app.frontend_url');
    }
}
