<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\EmailHeader\CustomVariableHeader;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $recipientEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $recipientEmail)
    {
        $this->name = $name;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('hello@demomailtrap.co', 'Tenant Management'),
            subject: 'You are awesome!',
            replyTo: [new Address('no-reply@demomailtrap.co', 'Mailtrap')],
            using: [
                function (Email $email) {
                    // Additional headers (optional)
                    $email->getHeaders()
                        ->addTextHeader('X-Mailer', 'Mailtrap PHP Client');

                    // Custom Variables
                    $email->getHeaders()
                        ->add(new CustomVariableHeader('user_name', $this->name))
                        ->add(new CustomVariableHeader('integration', 'Test'));

                    // Category
                    $email->getHeaders()
                        ->add(new CategoryHeader('Integration Test'));
                }
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-email', // Blade template
            with: [
                'name' => $this->name,
                'text' => "Congrats for sending test email with Mailtrap!"
            ]
        );
    }
}
