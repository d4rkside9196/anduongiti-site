<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('site.name').'] New enquiry from '.$this->lead->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-lead',
            with: ['contact' => Setting::read('contact', [
                'email' => 'contact@anduongiti.com',
                'phone' => '+84 915 663 683',
            ])],
        );
    }
}
