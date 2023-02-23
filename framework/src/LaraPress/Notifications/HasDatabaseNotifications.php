<?php

namespace LaraPress\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     *
     * @return \LaraPress\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     *
     * @return \LaraPress\Database\Query\Builder
     */
    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    /**
     * Get the entity's unread notifications.
     *
     * @return \LaraPress\Database\Query\Builder
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }
}
