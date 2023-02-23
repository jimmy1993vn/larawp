<?php

namespace LaraPress\Auth\Events;

use LaraPress\Queue\SerializesModels;

class Verified
{
    use SerializesModels;

    /**
     * The verified user.
     *
     * @var \LaraPress\Contracts\Auth\MustVerifyEmail
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param \LaraPress\Contracts\Auth\MustVerifyEmail $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
