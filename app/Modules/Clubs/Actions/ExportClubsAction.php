<?php

declare(strict_types=1);

namespace App\Modules\Clubs\Actions;

use App\Modules\Clubs\Models\Club;
use App\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportClubsAction
{
    public const COLUMNS = [
        'name_ar', 'name_en', 'short_name', 'status',
        'sports_en', 'leagues_en',
    ];

    public function execute(): StreamedResponse
    {
        $filename = 'clubs-'.now()->format('Y-m-d-His').'.csv';

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=,\r\n");
            fputcsv($out, self::COLUMNS);

            Club::with(['sports', 'leagues'])->orderBy('id')->chunk(500, function ($clubs) use ($out) {
                foreach ($clubs as $c) {
                    // Csv::safe defuses formula injection on every cell.
                    fputcsv($out, [
                        Csv::safe($c->name_ar),
                        Csv::safe($c->name_en),
                        Csv::safe($c->short_name),
                        Csv::safe($c->status?->value ?? 'active'),
                        Csv::safe($c->sports->pluck('name_en')->implode('|')),
                        Csv::safe($c->leagues->pluck('name_en')->implode('|')),
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function template(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=,\r\n");
            fputcsv($out, self::COLUMNS);
            fputcsv($out, [
                'الهلال', 'Al-Hilal', 'HIL', 'active', 'Football', 'Roshn League',
            ]);
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="clubs-template.csv"',
        ]);
    }
}
