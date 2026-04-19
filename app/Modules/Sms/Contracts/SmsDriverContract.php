<?php

declare(strict_types=1);

namespace App\Modules\Sms\Contracts;

/**
 * Minimal driver contract every SMS provider plugs into.
 *
 * Kept deliberately thin: the Service layer handles number normalisation,
 * rate limiting and template rendering — a driver only needs to know how
 * to hand an already-validated (phone, message) pair to its provider and
 * return whether the provider accepted it.
 */
interface SmsDriverContract
{
    /**
     * Send a single text message.
     *
     * @return array{ok:bool, provider_id?:string, error?:string}
     *    ok           — true if the provider accepted the request.
     *    provider_id  — optional message id / receipt from the provider.
     *    error        — human-readable message when ok=false.
     */
    public function send(string $to, string $message): array;

    /**
     * Identifier shown in the admin UI + logs ("twilio", "unifonic", …).
     */
    public function name(): string;
}
