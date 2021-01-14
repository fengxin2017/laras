<?php

namespace Illuminate\Mail;

use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Swoole\Timer;

class PendingMail
{
    /**
     * The mailer instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The locale of the message.
     *
     * @var string
     */
    protected $locale;

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * Create a new mailable mailer instance.
     *
     * @param \Illuminate\Contracts\Mail\Mailer $mailer
     * @return void
     */
    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Set the locale of the message.
     *
     * @param string $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function to($users)
    {
        $this->to = $users;

        if (!$this->locale && $users instanceof HasLocalePreference) {
            $this->locale($users->preferredLocale());
        }

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function cc($users)
    {
        $this->cc = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param mixed $users
     * @return $this
     */
    public function bcc($users)
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * @param MailableContract $mailable
     * @param int $delay
     * @return bool|void
     */
    public function send(MailableContract $mailable, int $delay = 0)
    {
        if ($delay instanceof Carbon) {
            $delay = $delay->diffInSeconds(Carbon::now());
        }

        $delay = max((int)$delay, 0);

        if ($delay != 0) {
            Timer::after(
                $delay * 1000,
                function () use ($mailable) {
                    $this->mailer->send($this->fill($mailable));
                }
            );

            return;
        }

        $this->mailer->send($this->fill($mailable));
    }

    /**
     * Populate the mailable with the addresses.
     *
     * @param \Illuminate\Contracts\Mail\Mailable $mailable
     * @return \Illuminate\Mail\Mailable
     */
    protected function fill(MailableContract $mailable)
    {
        return tap(
            $mailable->to($this->to)
                ->cc($this->cc)
                ->bcc($this->bcc),
            function (MailableContract $mailable) {
                if ($this->locale) {
                    $mailable->locale($this->locale);
                }
            }
        );
    }
}
