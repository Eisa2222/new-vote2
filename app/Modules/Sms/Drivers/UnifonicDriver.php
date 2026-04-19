<?php

declare(strict_types=1);

namespace App\Modules\Sms\Drivers;

use App\Modules\Sms\Contracts\SmsDriverContract;
use Illuminate\Support\Facades\Http;

/**
 * Unifonic — one of the most common SMS providers in Saudi Arabia.
 * Uses the REST v1 endpoint `/wsgi/v1/sms/messages/send`. AppSid is
 * the account credential, SenderID is the approved alphanumeric
 * sender name shown on the recipient's handset.
 *
 * Docs: https://docs.unifonic.com/reference/send-sms-messages
 */
final class UnifonicDriver implements SmsDriverContract
{
    public function __construct(
        private readonly string $appSid,
        private readonly string $senderId,
        private readonly string $endpoint = 'https://api.unifonic.com/rest/SMS/messages',
    ) {}

    public function send(string $to, string $message): array
    {
        try {
            $response = Http::asForm()->timeout(15)->post($this->endpoint, [
                'AppSid'    => $this->appSid,
                'SenderID'  => $this->senderId,
                'Recipient' => ltrim($to, '+'),   // Unifonic expects digits only
                'Body'      => $message,
            ]);

            if ($response->successful() && (bool) $response->json('success', false)) {
                return [
                    'ok'          => true,
                    'provider_id' => (string) ($response->json('data.MessageID') ?? ''),
                ];
            }

            return [
                'ok'    => false,
                'error' => (string) ($response->json('message') ?? 'Unifonic request failed ('.$response->status().')'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function name(): string { return 'unifonic'; }
}
