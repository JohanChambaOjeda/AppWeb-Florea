<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PilotMetrics;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Las métricas del piloto, semana a semana.
 */
class MetricsController extends Controller
{
    /** Cuántas semanas hacia atrás se muestran en el selector y el CSV. */
    private const WEEKS = 8;

    public function index(Request $request): Response
    {
        $week = $this->week($request);

        return Inertia::render('admin/metrics/index', [
            'week' => PilotMetrics::forWeek($week),
            'weeks' => $this->weekOptions(),
            'selected' => $week->startOfWeek()->toDateString(),
            'goal' => [
                'hours' => PilotMetrics::FAST_HOURS,
                'pct' => PilotMetrics::GOAL_PCT,
            ],
        ]);
    }

    /**
     * Las últimas semanas en CSV, para abrirlo en Excel y mandárselo a quien
     * haga falta.
     */
    public function export(): StreamedResponse
    {
        $rows = [];

        foreach ($this->lastWeeks() as $start) {
            $m = PilotMetrics::forWeek($start);

            $rows[] = [
                $m['from'],
                $m['to'],
                $m['listings'],
                $m['wants'],
                $m['offers'],
                $m['deals'],
                $m['firstOffer']['medianMinutes'] ?? '',
                $m['firstOffer']['averageMinutes'] ?? '',
                $m['fast']['withinHours'],
                $m['fast']['total'],
                $m['fast']['pct'] ?? '',
                $m['activeCompanies']['total'],
                $m['activeCompanies']['postharvest'],
                $m['activeCompanies']['growers'],
            ];
        }

        $name = 'florea-metricas-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            // BOM para que Excel en Windows no rompa las tildes.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Desde', 'Hasta', 'Publicaciones', 'Busco', 'Ofertas', 'Tratos',
                'Mediana 1a oferta (min)', 'Promedio 1a oferta (min)',
                'Busco con oferta <4h', 'Busco totales', '% <4h',
                'Empresas activas', 'Poscosechas', 'Productores',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function week(Request $request): CarbonInterface
    {
        $raw = $request->string('week')->toString();

        if ($raw === '') {
            return now()->startOfWeek();
        }

        return rescue(
            fn () => Carbon::parse($raw)->startOfWeek(),
            now()->startOfWeek(),
            report: false,
        );
    }

    /**
     * @return list<CarbonInterface>
     */
    private function lastWeeks(): array
    {
        $weeks = [];

        for ($i = 0; $i < self::WEEKS; $i++) {
            $weeks[] = now()->startOfWeek()->subWeeks($i);
        }

        return $weeks;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function weekOptions(): array
    {
        return array_map(fn (CarbonInterface $start) => [
            'value' => $start->toDateString(),
            'label' => $start->translatedFormat('j M').' – '
                .$start->copy()->endOfWeek()->translatedFormat('j M'),
        ], $this->lastWeeks());
    }
}
