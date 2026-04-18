<?php

declare(strict_types=1);

namespace App\Support;

/**
 * CSV cell sanitiser + lightweight PII masking.
 *
 * Why this exists:
 *  - **Formula injection (CWE-1236).** A cell beginning with `=`, `+`, `-`,
 *    `@`, TAB or CR will be evaluated by Excel / LibreOffice / Google Sheets
 *    as a formula. A malicious player name like `=cmd|'/c calc'!A1` becomes
 *    code execution the moment an admin opens the export. We neutralise it
 *    by prefixing a single quote, which Excel renders harmlessly.
 *  - **PII leakage.** national_id and mobile_number are crown-jewel PII.
 *    The default export masks them; a "with_pii" export (gated by an
 *    explicit permission) is left as a TODO for the operations team.
 */
final class Csv
{
    /**
     * Make a single cell value safe to ship in a CSV / XLSX.
     *
     * Returns a string in every case (including null and bool) so callers
     * can `fputcsv` without surprises.
     */
    public static function safe(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        $s = (string) $value;
        if ($s === '') {
            return '';
        }

        $first = $s[0];
        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            // Leading apostrophe defuses the formula in every spreadsheet.
            return "'".$s;
        }

        return $s;
    }

    /**
     * Mask a Saudi national ID like `1012345678` → `1012****78`.
     * Returns empty string for null / blank.
     */
    public static function maskNationalId(?string $id): string
    {
        $id = trim((string) $id);
        if ($id === '') {
            return '';
        }
        if (strlen($id) <= 6) {
            return str_repeat('*', strlen($id));
        }
        return substr($id, 0, 4).str_repeat('*', strlen($id) - 6).substr($id, -2);
    }

    /**
     * Mask a Saudi mobile like `0501234567` → `05*****567`.
     */
    public static function maskMobile(?string $mobile): string
    {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return '';
        }
        if (strlen($mobile) <= 5) {
            return str_repeat('*', strlen($mobile));
        }
        return substr($mobile, 0, 2).str_repeat('*', strlen($mobile) - 5).substr($mobile, -3);
    }
}
