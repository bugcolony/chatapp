<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Horizon::auth(function ($request): bool {
            if (app()->environment('local')) {
                return true;
            }

            $user = $request->user();

            if (! $user) {
                return false;
            }

            return in_array($user->email, $this->allowedEmails(), true);
        });
    }

    /**
     * @return array<int, string>
     */
    protected function allowedEmails(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', (string) env('HORIZON_ALLOWED_EMAILS', ''))
        )));
    }
}
