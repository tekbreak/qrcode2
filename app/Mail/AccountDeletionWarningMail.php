<?php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CarbonInterface $deletionDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('billing.account_deletion_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-deletion-warning',
        );
    }
}
