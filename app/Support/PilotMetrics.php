<?php

namespace App\Support;

use App\Enums\CompanyType;
use App\Enums\ListingKind;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Los números con los que se decide si Florea sirve.
 *
 * La meta del piloto: que al menos la MITAD de los "Busco" reciban una
 * oferta en menos de 4 horas. Si eso se cumple, Florea convirtió días de
 * llamadas en una mañana; si no, no vale la suscripción.
 *
 * Las restas de fechas se hacen en PHP porque MySQL usa TIMESTAMPDIFF y
 * SQLite julianday. A escala de piloto son decenas de filas por semana.
 */
final class PilotMetrics
{
    /** La meta: 50% o más de los pedidos atendidos en 4 horas. */
    public const FAST_HOURS = 4;

    public const GOAL_PCT = 50;

    /**
     * @return array<string, mixed>
     */
    public static function forWeek(CarbonInterface $weekStart): array
    {
        $from = $weekStart->copy()->startOfWeek();
        $to = $from->copy()->endOfWeek();

        $waits = self::waitsInMinutes($from, $to);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'listings' => Listing::whereBetween('created_at', [$from, $to])->count(),
            'wants' => Listing::where('kind', ListingKind::Want)
                ->whereBetween('created_at', [$from, $to])->count(),
            'offers' => Offer::whereBetween('created_at', [$from, $to])->count(),
            'deals' => Deal::whereBetween('closed_at', [$from, $to])->count(),
            'firstOffer' => [
                'measured' => count($waits),
                'averageMinutes' => self::average($waits),
                'medianMinutes' => self::median($waits),
            ],
            'fast' => self::fast($from, $to),
            'activeCompanies' => self::activeCompanies($from, $to),
        ];
    }

    /**
     * Espera hasta la primera oferta, en minutos, de las publicaciones
     * creadas en la semana que recibieron alguna.
     *
     * @return list<int>
     */
    private static function waitsInMinutes(CarbonInterface $from, CarbonInterface $to): array
    {
        $waits = [];

        $listings = Listing::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('offers')
            ->withMin('offers as first_offer_at', 'created_at')
            ->get(['id', 'created_at']);

        foreach ($listings as $listing) {
            if ($listing->first_offer_at === null) {
                continue;
            }

            $waits[] = (int) $listing->created_at->diffInMinutes(
                Carbon::parse($listing->first_offer_at),
                absolute: true,
            );
        }

        sort($waits);

        return $waits;
    }

    /**
     * LA métrica del piloto: qué porcentaje de los "Busco" recibió su
     * primera oferta en menos de 4 horas.
     *
     * El denominador son TODOS los "Busco" de la semana, no solo los que
     * recibieron oferta: un pedido que se quedó sin respuesta también es un
     * pedido que Florea no resolvió, y esconderlo maquillaría el número.
     *
     * @return array{total: int, withinHours: int, pct: int|null, goalMet: bool}
     */
    private static function fast(CarbonInterface $from, CarbonInterface $to): array
    {
        $wants = Listing::query()
            ->where('kind', ListingKind::Want)
            ->whereBetween('created_at', [$from, $to])
            ->withMin('offers as first_offer_at', 'created_at')
            ->get(['id', 'created_at']);

        $total = $wants->count();
        $withinHours = 0;

        foreach ($wants as $listing) {
            if ($listing->first_offer_at === null) {
                continue;
            }

            $minutes = $listing->created_at->diffInMinutes(
                Carbon::parse($listing->first_offer_at),
                absolute: true,
            );

            if ($minutes <= self::FAST_HOURS * 60) {
                $withinHours++;
            }
        }

        $pct = $total > 0 ? (int) round($withinHours / $total * 100) : null;

        return [
            'total' => $total,
            'withinHours' => $withinHours,
            'pct' => $pct,
            'goalMet' => $pct !== null && $pct >= self::GOAL_PCT,
        ];
    }

    /**
     * Empresas que hicieron algo en la semana: publicar, ofertar o entrar.
     *
     * @return array<string, int>
     */
    private static function activeCompanies(CarbonInterface $from, CarbonInterface $to): array
    {
        $published = Listing::whereBetween('created_at', [$from, $to])->pluck('company_id');
        $offered = Offer::whereBetween('created_at', [$from, $to])->pluck('company_id');
        $loggedIn = User::whereBetween('last_seen_at', [$from, $to])
            ->whereNotNull('company_id')
            ->pluck('company_id');

        $ids = $published->merge($offered)->merge($loggedIn)->unique();

        $byType = Company::whereIn('id', $ids)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'total' => $ids->count(),
            'postharvest' => (int) ($byType[CompanyType::Postharvest->value] ?? 0),
            'growers' => (int) ($byType[CompanyType::Grower->value] ?? 0),
        ];
    }

    /**
     * @param  list<int>  $values
     */
    private static function average(array $values): ?int
    {
        return $values === [] ? null : (int) round(array_sum($values) / count($values));
    }

    /**
     * La mediana aguanta mejor los casos raros: una publicación que tardó
     * dos días no arrastra el número como sí lo hace el promedio.
     *
     * @param  list<int>  $values  Ya ordenados.
     */
    private static function median(array $values): ?int
    {
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }
}
