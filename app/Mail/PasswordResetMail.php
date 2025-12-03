<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Symfony\Component\Mime\Email;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\EmailHeader\CustomVariableHeader;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, string $resetUrl)
    {
        $this->userName = $userName;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Define the envelope.
     */
    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            from: new Address('hello@demomailtrap.co', 'Tenant Management'),
            subject: 'Reset Your Password',
            using: [
                function (Email $email) {
                    $email->getHeaders()
                        ->addTextHeader('X-Mailer', 'Mailtrap PHP Client')
                        ->add(new CustomVariableHeader('category', 'Password Reset'))
                        ->add(new CategoryHeader('Password Reset'));
                }
            ]
        );
    }

    /**
     * Define the content.
     */
    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.password_reset',
            with: [
                'userName' => $this->userName,
                'resetUrl' => $this->resetUrl,
            ]
        );
    }
}
