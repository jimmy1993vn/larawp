<?php

namespace LaraPress\Mail\Transport;

use LaraPress\Support\Collection;
use Swift_Mime_SimpleMessage;

class ArrayTransport extends Transport
{
    /**
     * The collection of Swift Messages.
     *
     * @var \LaraPress\Support\Collection
     */
    protected $messages;

    /**
     * Create a new array transport instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->messages[] = $message;

        return $this->numberOfRecipients($message);
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return \LaraPress\Support\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * @return \LaraPress\Support\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}
