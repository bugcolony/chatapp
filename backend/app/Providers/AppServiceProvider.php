<?php

namespace App\Providers;

use Agence104\LiveKit\WebhookReceiver;
use App\Services\Gateway\RealtimeTransport;
use App\Services\Gateway\RedisPubSubTransport;
use App\Services\Gateway\RedisWebSocketTicketStore;
use App\Services\Gateway\WebSocketTicketStore;
use App\Services\RTC\RedisVoiceChannelPresence;
use App\Services\RTC\VoiceChannelPresence;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RealtimeTransport::class, fn ($app) => new RedisPubSubTransport(
            $app->make(RedisFactory::class),
            (string) config('realtime.channel'),
        ));

        $this->app->singleton(VoiceChannelPresence::class, fn ($app) => new RedisVoiceChannelPresence(
            $app->make(RedisFactory::class),
        ));

        $this->app->singleton(WebSocketTicketStore::class, fn ($app) => new RedisWebSocketTicketStore(
            $app->make(RedisFactory::class),
        ));

        $this->app->singleton(WebhookReceiver::class, fn () => new WebhookReceiver(
            (string) config('services.rtc.livekit.api_key'),
            (string) config('services.rtc.livekit.secret'),
        ));

        if (
            $this->app->environment('local')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
