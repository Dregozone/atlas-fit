<?php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserSignedUp extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public CarbonImmutable $signedUpAt,
        public int $totalUsers,
        public ?string $referrer = null,
        public ?string $country = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Atlas Fit sign-up: {$this->user->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-user-signed-up',
            with: [
                'signedUpAtLondon' => $this->signedUpAt->setTimezone('Europe/London')->format('j M Y, H:i T'),
            ],
        );
    }
}
