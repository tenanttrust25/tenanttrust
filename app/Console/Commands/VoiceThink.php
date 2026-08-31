<?php

namespace App\Console\Commands;

use App\Http\Controllers\TwilioVoiceController;
use Illuminate\Console\Command;

class VoiceThink extends Command
{
    protected $signature = 'voice:think {callSid} {turn}';

    protected $description = 'Compute a Jarvis phone reply in the background and cache it for /twilio/think.';

    public function handle(): int
    {
        $callSid = (string) $this->argument('callSid');
        $turn = (int) $this->argument('turn');

        app(TwilioVoiceController::class)->computeReplyForCall($callSid, $turn);

        return 0;
    }
}
