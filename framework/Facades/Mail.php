<?php


namespace Laras\Facades;

/**
 * @method static \Illuminate\Mail\Mailer mailer(string|null $name = null)
 * @method static \Illuminate\Mail\PendingMail bcc($users)
 * @method static \Illuminate\Mail\PendingMail to($users)
 * @method static void raw(string $text, $callback)
 * @method static void plain(string $view, array $data, $callback)
 * @method static void html(string $html, $callback)
 * @method static void send(\Illuminate\Contracts\Mail\Mailable|string|array $view, array $data = [], \Closure|string $callback = null)
 *
 * @see \Illuminate\Mail\Mailer
 * @see \Illuminate\Support\Testing\Fakes\MailFake
 */
class Mail extends Facade
{
    /**
     * @return string
     */
    public function getAccessor(): string
    {
        return 'mail.manager';
    }
}