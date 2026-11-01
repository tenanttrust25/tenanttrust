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
                // If the template didn't include the placeholder, append the
                // pixel anyway so opens still get tracked.
                if (!str_contains($body, $trackingPixel)) {
                    $body .= $trackingPixel;
                }
                $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $body);
                $body = str_replace('{{tenant_name}}', $recipient->tenant?->name ?? 'Tenant', $body);
                $body = str_replace('{{tenant_email}}', $recipient->tenant?->email ?? '', $body);
                $body = str_replace('{{year}}', date('Y'), $body);

                $subject = $template->subject ?? $campaign->title;

                Mail::html($body, function ($message) use ($recipient, $subject, $campaign, $unsubscribeUrl) {
                    $message->to($recipient->email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name', 'Tenant Trust'));

                    // Bulk-sender best practice (Gmail/Yahoo 2024 rules): advertise
                    // the unsubscribe URL so receivers can surface a one-click
                    // opt-out. Only applied to campaign (bulk) mail, never to
                    // transactional notices/invoices.
                    $message->getHeaders()->addTextHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
                });

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $sentCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Campaign #{$campaign->id} send failed to {$recipient->email}: " . $e->getMessage());
                $recipient->update(['status' => 'bounced']);
            }

            // Force a fresh SMTP connection per message. Stalwart caps
            // messages per session (452), and Laravel reuses one connection
            // for the whole loop — so without this, bulk campaigns die
            // after the first ~10 sends.
            try {
                \Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->stop();
            } catch (\Throwable $e) {
                // ignore transport teardown errors
            }

            // Pace sends to avoid SMTP rate limiting.
            usleep(250000);
        }

        $campaign->increment('sent_count', $sentCount);
        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
