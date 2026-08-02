<?php

namespace App\Http\Controllers;

use App\Models\MailCampaignRecipient;
use Illuminate\Http\Request;

class MailTrackingController extends Controller
{
    public function trackOpen($hash)
    {
        $recipient = MailCampaignRecipient::where('tracking_hash', $hash)->first();
        if ($recipient && $recipient->status === 'sent') {
            $recipient->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
            $recipient->campaign()->increment('open_count');
        }

        // Return 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function trackClick($hash, Request $request)
    {
        $recipient = MailCampaignRecipient::where('tracking_hash', $hash)->first();
        if ($recipient) {
            $recipient->increment('click_count');
            if ($recipient->status !== 'clicked') {
                $recipient->update([
                    'status' => 'clicked',
                    'clicked_at' => now(),
                ]);
            }
            $recipient->campaign()->increment('click_count');
        }

        // Redirect to a default URL or pass via query param
        $url = $request->query('url', url('/'));
        return redirect($url);
    }

    public function unsubscribe($hash)
    {
        $recipient = MailCampaignRecipient::where('tracking_hash', $hash)->first();
        if ($recipient) {
            $recipient->update(['status' => 'unsubscribed']);

            // Also mark the tenant
            if ($recipient->tenant) {
                $recipient->tenant->update(['mail_unsubscribed' => true]);
            }
        }

        return response('You have been unsubscribed from Tenant Trust emails.', 200);
    }
}
