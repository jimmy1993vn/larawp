<?php

namespace LaraPress\Support\Facades;

use LaraPress\Notifications\AnonymousNotifiable;
use LaraPress\Notifications\ChannelManager;
use LaraPress\Support\Testing\Fakes\NotificationFake;

/**
 * @method static \LaraPress\Notifications\ChannelManager locale(string|null $locale)
 * @method static \LaraPress\Support\Collection sent(mixed $notifiable, string $notification, callable $callback = null)
 * @method static bool hasSent(mixed $notifiable, string $notification)
 * @method static mixed channel(string|null $name = null)
 * @method static void assertNotSentTo(mixed $notifiable, string|\Closure $notification, callable $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertSentOnDemand(string|\Closure $notification, callable $callback = null)
 * @method static void assertSentTo(mixed $notifiable, string|\Closure $notification, callable $callback = null)
 * @method static void assertSentOnDemandTimes(string $notification, int $times = 1)
 * @method static void assertSentToTimes(mixed $notifiable, string $notification, int $times = 1)
 * @method static void assertTimesSent(int $expectedCount, string $notification)
 * @method static void send(\LaraPress\Support\Collection|array|mixed $notifiables, $notification)
 * @method static void sendNow(\LaraPress\Support\Collection|array|mixed $notifiables, $notification)
 *
 * @see \LaraPress\Notifications\ChannelManager
 */
class Notification extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \LaraPress\Support\Testing\Fakes\NotificationFake
     */
    public static function fake()
    {
        static::swap($fake = new NotificationFake);

        return $fake;
    }

    /**
     * Begin sending a notification to an anonymous notifiable.
     *
     * @param string $channel
     * @param mixed $route
     * @return \LaraPress\Notifications\AnonymousNotifiable
     */
    public static function route($channel, $route)
    {
        return (new AnonymousNotifiable)->route($channel, $route);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ChannelManager::class;
    }
}