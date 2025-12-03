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

class TenantRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $tenant;

    public function __construct(User $tenant)
    {
        $this->tenant = $tenant;
    }

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            from: new Address('hello@demomailtrap.co', 'Tenant Management'),
            subject: 'Tenant Application Rejected',
            using: [
                function (Email $email) {
                    $email->getHeaders()
                        ->addTextHeader('X-Mailer', 'Mailtrap PHP Client')
                        ->add(new CustomVariableHeader('tenant_name', $this->tenant->name))
                        ->add(new CustomVariableHeader('rejection_reason', $this->tenant->rejection_reason))
                        ->add(new CategoryHeader('Tenant Rejected'));
                }
            ]
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.tenant_rejected',
            with: [
                'tenantName' => $this->tenant->name,
                'reason'     => $this->tenant->rejection_reason,
            ]
        );
    }
}
