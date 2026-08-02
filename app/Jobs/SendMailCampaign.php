<?php

namespace App\Jobs;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailCampaign
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public MailCampaign $campaign;

    public function __construct(MailCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(): void
    {
        $campaign = $this->campaign;
        $template = $campaign->template;

        if (!$template) {
            $campaign->update(['status' => 'cancelled']);
            return;
        }

        $recipients = $campaign->recipients()->where('status', 'pending')->get();
        $sentCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $trackingPixel = route('mail.track.open', ['hash' => $recipient->tracking_hash]);
                $unsubscribeUrl = route('mail.track.unsubscribe', ['hash' => $recipient->tracking_hash]);

                $body = $template->body;
                $body = str_replace('{{tracking_pixel}}', $trackingPixel, $body);
                $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $body);
                $body = str_replace('{{tenant_name}}', $recipient->tenant?->name ?? 'Tenant', $body);
                $body = str_replace('{{tenant_email}}', $recipient->tenant?->email ?? '', $body);
                $body = str_replace('{{year}}', date('Y'), $body);

                $subject = $template->subject ?? $campaign->title;

                Mail::html($body, function ($message) use ($recipient, $subject, $campaign) {
                    $message->to($recipient->email)
                        ->subject($subject)
                        ->from('noreply@tenanttrust.ca', 'Tenant Trust');
                });

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $sentCount++;
            } catch (\Exception $e) {
                $recipient->update(['status' => 'bounced']);
            }
        }

        $campaign->increment('sent_count', $sentCount);
        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
