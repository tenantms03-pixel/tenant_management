<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Lease;
use Illuminate\Mail\Mailables\Address;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\EmailHeader\CustomVariableHeader;
use Symfony\Component\Mime\Email;

class TenantApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $tenant;
    public Lease $lease;

    public function __construct(User $tenant, Lease $lease)
    {
        $this->tenant = $tenant;
        $this->lease = $lease;
    }

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            from: new Address('hello@demomailtrap.co', 'Tenant Management'),
            subject: 'Tenant Application Approved',
            using: [
                function (Email $email) {
                    $email->getHeaders()
                        ->addTextHeader('X-Mailer', 'Mailtrap PHP Client')
                        ->add(new CustomVariableHeader('tenant_name', $this->tenant->name))
                        ->add(new CustomVariableHeader('lease_term', $this->lease->lea_terms))
                        ->add(new CategoryHeader('Tenant Approved'));
                }
            ]
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.tenant_approved',
            with: [
                'tenantName' => $this->tenant->name,
                'leaseTerm'  => $this->lease->lea_terms,
            ]
        );
    }
}
