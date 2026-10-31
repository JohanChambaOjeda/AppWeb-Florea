<?php

namespace App\Support;

use App\Models\Company;

/**
 * Los números de reputación que ve otra empresa.
 *
 * Nunca incluye comentarios: en el MVP los lee solo el admin. Lo que se
 * publica son números, que son más difíciles de usar para pelearse.
 */
final class Reputation
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Company $company): array
    {
        // Con menos de 3 calificaciones no hay promedio que valga: se
        // muestra "Nuevo en Florea" y el que decide ya sabe a qué atenerse.
        if (! $company->hasReputation()) {
            return [
                'isNew' => true,
                'stars' => null,
                'ratings' => $company->ratings_count,
                'deals' => $company->deals_count,
                'onTimePct' => null,
            ];
        }

        return [
            'isNew' => false,
            'stars' => $company->ratings_avg !== null ? (float) $company->ratings_avg : null,
            'ratings' => $company->ratings_count,
            'deals' => $company->deals_count,
            // El % a tiempo solo tiene sentido para quien entrega.
            'onTimePct' => $company->isGrower() ? $company->on_time_pct : null,
        ];
    }
}
