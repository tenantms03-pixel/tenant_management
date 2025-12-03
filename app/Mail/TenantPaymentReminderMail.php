<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Mail\Mailables\Address;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\EmailHeader\CustomVariableHeader;
use Symfony\Component\Mime\Email;

class TenantPaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $tenant;

    /**
     * Create a new message instance.
     */
    public function __construct(User $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Define the envelope (sender, subject, headers, etc.)
     */
    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            from: new Address('hello@demomailtrap.co', 'Tenant Management'),
            subject: 'Monthly Payment Reminder',
            using: [
                function (Email $email) {
                    $email->getHeaders()
                        ->addTextHeader('X-Mailer', 'Mailtrap PHP Client')
                        ->add(new CustomVariableHeader('tenant_name', $this->tenant->name))
                        ->add(new CustomVariableHeader('rent_balance', $this->tenant->rent_balance))
                        ->add(new CustomVariableHeader('utility_balance', $this->tenant->utility_balance))
                        ->add(new CategoryHeader('Payment Reminder'));
                }
            ]
        );
    }

    /**
     * Define the content of the email.
     */
    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.payment_reminder',
            with: [
                'tenantName'     => $this->tenant->name,
                'rentBalance'    => $this->tenant->rent_balance,
                'utilityBalance' => $this->tenant->utility_balance,
                'status'         => $this->tenant->status,
            ]
        );
    }

}
