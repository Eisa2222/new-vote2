<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Support;

use App\Modules\Notifications\Models\EmailTemplate;
use App\Modules\Shared\Support\Branding;

/**
 * Turns a (key, campaign_type, locale, vars[]) tuple into a
 * ['subject' => ..., 'body' => ...] pair ready to hand to Mail::html.
 *
 *   • Lookup walks the fallback chain defined on EmailTemplate::resolve.
 *   • Variable substitution is plain `{dot.path}` — no PHP eval, no
 *     Blade, no Twig. Unknown placeholders are left as-is so an admin
 *     typo is visible in the output, not silently eaten.
 *   • `platform.name` is auto-populated from Branding so every template
 *     doesn't have to hard-code "SFPA Voting".
 */
final class TemplateRenderer
{
    /**
     * @return array{subject:string, body:string, resolved:bool}
     */
    public static function render(string $key, ?string $campaignType, string $locale, array $vars, array $fallback = []): array
    {
        $row = EmailTemplate::resolve($key, $campaignType, $locale);

        if (! $row) {
            // Nothing in the DB — use the caller's hardcoded fallback.
            return [
                'subject'  => self::interpolate($fallback['subject'] ?? $key, $vars),
                'body'     => self::interpolate($fallback['body']    ?? '{platform.name}', $vars),
                'resolved' => false,
            ];
        }

        $vars = array_merge(['platform.name' => Branding::name()], $vars);
        return [
            'subject'  => self::interpolate($row->subject, $vars),
            'body'     => self::interpolate($row->body, $vars),
            'resolved' => true,
        ];
    }

    /**
     * Replace every `{some.key}` with the matching value from $vars.
     * Supports dot-notation (`campaign.title`) by exact key lookup.
     */
    public static function interpolate(string $text, array $vars): string
    {
        return preg_replace_callback('/\{([a-z0-9_.]+)\}/i', function ($m) use ($vars) {
            $key = $m[1];
            if (array_key_exists($key, $vars)) {
                $v = $vars[$key];
                return is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            return $m[0];
        }, $text) ?? $text;
    }
}
