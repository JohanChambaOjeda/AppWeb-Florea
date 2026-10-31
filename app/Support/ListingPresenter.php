<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Listing;
use App\Models\ListingItem;

/**
 * Da forma a una publicación para el frontend.
 *
 * REGLA 6: aquí nunca se incluye el celular de nadie. El contacto de otra
 * empresa solo sale en la oferta aceptada que creó el trato, y eso lo arma
 * OfferPresenter. Si alguna pantalla necesita datos de la empresa que publica,
 * se agregan en company() y nada más.
 */
final class ListingPresenter
{
    /**
     * Tarjeta para los listados del inicio.
     *
     * @return array<string, mixed>
     */
    public static function card(Listing $listing, Company $viewer): array
    {
        return [
            'id' => $listing->id,
            'kind' => $listing->kind->value,
            'kindLabel' => $listing->kind->label(),
            'status' => $listing->status->value,
            'statusLabel' => $listing->status->label(),
            'isMine' => $listing->company_id === $viewer->id,
            'isOpen' => $listing->isOpen(),
            'company' => self::company($listing),
            'summary' => self::summary($listing),
            'itemCount' => $listing->items->count(),
            'neededBy' => self::neededBy($listing),
            'expiresAt' => $listing->expires_at->toIso8601String(),
            'offersCount' => (int) ($listing->offers_count ?? 0),
            'newOffersCount' => (int) ($listing->new_offers_count ?? 0),
            'createdAt' => $listing->created_at->toIso8601String(),
        ];
    }

    /**
     * Detalle: la tarjeta más las líneas completas y los datos del pedido.
     *
     * @return array<string, mixed>
     */
    public static function detail(Listing $listing, Company $viewer): array
    {
        return [
            ...self::card($listing, $viewer),
            'items' => $listing->items->map(fn (ListingItem $item) => [
                'id' => $item->id,
                'variety' => $item->variety->name,
                'color' => $item->variety->color,
                'lengths' => $item->lengths,
                'quantity' => self::quantity($item),
                'unit' => $item->unit?->value,
                'unitLabel' => $item->unit?->label(),
                'summary' => self::itemSummary($item),
            ])->all(),
            'destination' => $listing->destination?->value,
            'destinationLabel' => $listing->destination?->label(),
            'cutPoint' => $listing->cut_point?->value,
            'cutPointLabel' => $listing->cut_point?->label(),
            'notes' => $listing->notes,
        ];
    }

    /**
     * Solo lo que se puede mostrar de la empresa que publica. Sin celular.
     *
     * @return array<string, mixed>
     */
    private static function company(Listing $listing): array
    {
        $company = $listing->company;

        return [
            'name' => $company->name,
            'type' => $company->type->value,
            'typeLabel' => $company->type->label(),
            'zone' => $company->zone->name,
            'verified' => $company->isVerified(),
            'reputation' => Reputation::of($company),
        ];
    }

    /**
     * "2 HB Pink Floyd 60 · 3 HB Explorer 70 · y 2 más"
     */
    private static function summary(Listing $listing, int $max = 3): string
    {
        $items = $listing->items;
        $shown = $items->take($max)->map(self::itemSummary(...))->all();
        $rest = $items->count() - count($shown);

        if ($rest > 0) {
            $shown[] = $rest === 1 ? 'y 1 más' : "y {$rest} más";
        }

        return implode(' · ', $shown);
    }

    /**
     * "2 HB Pink Floyd 60/50", o "Pink Floyd 60/50" si no dijeron cantidad.
     */
    private static function itemSummary(ListingItem $item): string
    {
        $parts = [];

        if ($item->quantity !== null) {
            $parts[] = self::quantity($item);
            $parts[] = $item->unit?->label();
        }

        $parts[] = $item->variety->name;
        $parts[] = implode('/', $item->lengths);

        return implode(' ', array_filter($parts));
    }

    /**
     * Cantidad sin decimales de relleno: 2.00 se muestra "2", 2.50 "2.5".
     */
    private static function quantity(ListingItem $item): ?string
    {
        if ($item->quantity === null) {
            return null;
        }

        $value = rtrim(rtrim($item->quantity, '0'), '.');

        return $value === '' ? '0' : $value;
    }

    /**
     * @return array{date: string|null, label: string|null, isToday: bool}
     */
    private static function neededBy(Listing $listing): array
    {
        $date = $listing->needed_by;

        if ($date === null) {
            return ['date' => null, 'label' => null, 'isToday' => false];
        }

        return [
            'date' => $date->toDateString(),
            'label' => match (true) {
                $date->isToday() => 'Hoy',
                $date->isTomorrow() => 'Mañana',
                default => $date->translatedFormat('D j M'),
            },
            'isToday' => $date->isToday(),
        ];
    }
}
