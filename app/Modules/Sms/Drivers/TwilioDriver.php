<?php

declare(strict_types=1);

namespace App\Modules\Sms\Drivers;

use App\Modules\Sms\Contracts\SmsDriverContract;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Twilio's Messages API using Basic Auth (Account SID + Auth
 * Token). No Twilio SDK dependency — a single HTTP call keeps the
 * footprint small and the code auditable.
 *
 * Credentials:
 *   sid    = ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx (the "SID" field)
 *   token  = the Auth Token from the Twilio console
 *   from   = a verified Twilio phone number (+E.164)
 */
final class TwilioDriver implements SmsDriverContract
{
    public function __construct(
        private readonly string $sid,
        private readonly string $token,
        private readonly string $from,
    ) {}

    public function send(string $to, string $message): array
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->sid, $this->token)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                    'To'   => $to,
                    'From' => $this->from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'provider_id' => (string) ($response->json('sid') ?? '')];
            }

            // Twilio returns a readable `message` on errors — surface it
            // so the admin sees e.g. "Invalid 'To' Phone Number" instead
            // of a generic 400.
            return [
                'ok'    => false,
                'error' => (string) ($response->json('message') ?? 'Twilio request failed ('.$response->status().')'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function name(): string { return 'twilio'; }
}
