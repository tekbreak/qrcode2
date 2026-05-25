<?php

namespace App\Console\Commands;

use App\Models\Scan;
use App\Models\ScanAggregate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate {--hours=1 : Hours to look back}';
    protected $description = 'Aggregate recent scan data into scan_aggregates table';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $since = now()->subHours($hours);

        $hourExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', scanned_at) AS INTEGER)"
            : 'HOUR(scanned_at)';

        $scans = Scan::where('scanned_at', '>=', $since)
            ->select(
                'short_link_id',
                DB::raw('DATE(scanned_at) as date'),
                DB::raw("{$hourExpr} as hour"),
                DB::raw('COUNT(*) as total_scans'),
                DB::raw('SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END) as unique_scans'),
            )
            ->groupBy('short_link_id', 'date', 'hour')
            ->get();

        $count = 0;
        foreach ($scans as $scan) {
            ScanAggregate::updateOrCreate(
                [
                    'short_link_id' => $scan->short_link_id,
                    'date' => $scan->date,
                    'hour' => $scan->hour,
                ],
                [
                    'total_scans' => $scan->total_scans,
                    'unique_scans' => $scan->unique_scans,
                ]
            );
            $count++;
        }

        $this->info("Aggregated {$count} records.");

        return self::SUCCESS;
    }
}
