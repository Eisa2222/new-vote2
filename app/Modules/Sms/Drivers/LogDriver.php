<?php

declare(strict_types=1);

namespace App\Modules\Sms\Drivers;

use App\Modules\Sms\Contracts\SmsDriverContract;
use Illuminate\Support\Facades\Log;

/**
 * Fallback "no-op" driver — writes the message to the application log
 * instead of calling a real provider. Used automatically when no
 * provider is configured, and selected explicitly in dev so you can
 * see the message content in storage/logs/laravel.log without spending
 * real SMS credits.
 */
final class LogDriver implements SmsDriverContract
{
    public function send(string $to, string $message): array
    {
        // Use the default log channel implicitly — Log::info()
        // routes through Laravel's default channel and is also what
        // the testing facade spies on, so this stays testable.
        Log::info('SMS (log driver)', [
            'to'      => $to,
            'message' => $message,
        ]);
        return ['ok' => true, 'provider_id' => 'log'];
    }

    public function name(): string { return 'log'; }
}
