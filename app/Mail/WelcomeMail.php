<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
// use App\Models\User;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    //public $verify;
    public $user_id;
    public $email;
    public $name;
    public $hash;

    public function __construct( $user_id, $data )
    {

        $this->user_id = $user_id;
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->hash = $data['password'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Please active your account!')
                    ->markdown('emails.welcome')
                    ->with('user_id', $this->user_id)
                    ->with('email', $this->email)
                    ->with('name', $this->name)
                    ->with('hash', md5($this->hash));
    }
}
