<?php

namespace LaraPress\Notifications;

use LaraPress\Contracts\Queue\ShouldQueue;
use LaraPress\Contracts\Translation\HasLocalePreference;
use LaraPress\Database\Eloquent\Collection as ModelCollection;
use LaraPress\Database\Eloquent\Model;
use LaraPress\Notifications\Events\NotificationSending;
use LaraPress\Notifications\Events\NotificationSent;
use LaraPress\Support\Collection;
use LaraPress\Support\Str;
use LaraPress\Support\Traits\Localizable;

class NotificationSender
{
    use Localizable;

    /**
     * The notification manager instance.
     *
     * @var \LaraPress\Notifications\ChannelManager
     */
    protected $manager;

    /**
     * The Bus dispatcher instance.
     *
     * @var \LaraPress\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The event dispatcher.
     *
     * @var \LaraPress\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The locale to be used when sending notifications.
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Create a new notification sender instance.
     *
     * @param \LaraPress\Notifications\ChannelManager $manager
     * @param \LaraPress\Contracts\Bus\Dispatcher $bus
     * @param \LaraPress\Contracts\Events\Dispatcher $events
     * @param string|null $locale
     * @return void
     */
    public function __construct($manager, $bus, $events, $locale = null)
    {
        $this->bus = $bus;
        $this->events = $events;
        $this->locale = $locale;
        $this->manager = $manager;
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param \LaraPress\Support\Collection|array|mixed $notifiables
     * @param mixed $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            return $this->queueNotification($notifiables, $notification);
        }

        $this->sendNow($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param \LaraPress\Support\Collection|array|mixed $notifiables
     * @param mixed $notification
     * @param array|null $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            $this->withLocale($this->preferredLocale($notifiable, $notification), function () use ($viaChannels, $notifiable, $original) {
                $notificationId = Str::uuid()->toString();

                foreach ((array)$viaChannels as $channel) {
                    if (!($notifiable instanceof AnonymousNotifiable && $channel === 'database')) {
                        $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
                    }
                }
            });
        }
    }

    /**
     * Get the notifiable's preferred locale for the notification.
     *
     * @param mixed $notifiable
     * @param mixed $notification
     * @return string|null
     */
    protected function preferredLocale($notifiable, $notification)
    {
        return $notification->locale ?? $this->locale ?? lp_value(function () use ($notifiable) {
            if ($notifiable instanceof HasLocalePreference) {
                return $notifiable->preferredLocale();
            }
        });
    }

    /**
     * Send the given notification to the given notifiable via a channel.
     *
     * @param mixed $notifiable
     * @param string $id
     * @param mixed $notification
     * @param string $channel
     * @return void
     */
    protected function sendToNotifiable($notifiable, $id, $notification, $channel)
    {
        if (!$notification->id) {
            $notification->id = $id;
        }

        if (!$this->shouldSendNotification($notifiable, $notification, $channel)) {
            return;
        }

        $response = $this->manager->driver($channel)->send($notifiable, $notification);

        $this->events->dispatch(
            new NotificationSent($notifiable, $notification, $channel, $response)
        );
    }

    /**
     * Determines if the notification can be sent.
     *
     * @param mixed $notifiable
     * @param mixed $notification
     * @param string $channel
     * @return bool
     */
    protected function shouldSendNotification($notifiable, $notification, $channel)
    {
        if (method_exists($notification, 'shouldSend') &&
            $notification->shouldSend($notifiable, $channel) === false) {
            return false;
        }

        return $this->events->until(
                new NotificationSending($notifiable, $notification, $channel)
            ) !== false;
    }

    /**
     * Queue the given notification instances.
     *
     * @param mixed $notifiables
     * @param \LaraPress\Notifications\Notification $notification
     * @return void
     */
    protected function queueNotification($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $notificationId = Str::uuid()->toString();

            foreach ((array)$original->via($notifiable) as $channel) {
                $notification = clone $original;

                $notification->id = $notificationId;

                if (!is_null($this->locale)) {
                    $notification->locale = $this->locale;
                }

                $queue = $notification->queue;

                if (method_exists($notification, 'viaQueues')) {
                    $queue = $notification->viaQueues()[$channel] ?? null;
                }

                $this->bus->dispatch(
                    (new SendQueuedNotifications($notifiable, $notification, [$channel]))
                        ->onConnection($notification->connection)
                        ->onQueue($queue)
                        ->delay(is_array($notification->delay) ?
                            ($notification->delay[$channel] ?? null)
                            : $notification->delay
                        )
                        ->through(
                            array_merge(
                                method_exists($notification, 'middleware') ? $notification->middleware() : [],
                                $notification->middleware ?? []
                            )
                        )
                );
            }
        }
    }

    /**
     * Format the notifiables into a Collection / array if necessary.
     *
     * @param mixed $notifiables
     * @return \LaraPress\Database\Eloquent\Collection|array
     */
    protected function formatNotifiables($notifiables)
    {
        if (!$notifiables instanceof Collection && !is_array($notifiables)) {
            return $notifiables instanceof Model
                ? new ModelCollection([$notifiables]) : [$notifiables];
        }

        return $notifiables;
    }
}