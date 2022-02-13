<?php

namespace App\Mails;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct()
    {
    }

    public function build()
    {
        $this->markdown('emails.bar',['name' => 'ffff','url' => 'https://www.baidu.com']);
    }
}