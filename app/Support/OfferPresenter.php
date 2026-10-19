<?php

namespace App\Support;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Photo;

/**
 * Da forma a una oferta para el frontend.
 *
 * REGLA 6: el celular de la otra empresa solo sale cuando la oferta está
 * aceptada, y solo en esa oferta. Ese es el único sitio de toda la app donde
 * se expone un contacto, y se decide aquí, en contact(). Cualquier otra
 * pantalla usa este mismo presenter y por eso no puede filtrarlo.
 */
final class OfferPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function mine(Offer $offer): array
    {
        return [
            ...self::base($offer),
            'canEdit' => $offer->isSent(),
            'canWithdraw' => $offer->isSent(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(Offer $offer): array
    {
        return [
            'id' => $offer->id,
            'status' => $offer->status->value,
            'statusLabel' => $offer->status->label(),
            'pricePerStem' => $offer->price_per_stem,
            'delivery' => $offer->delivery->value,
            'deliveryLabel' => $offer->delivery->label(),
            'notes' => $offer->notes,
            'createdAt' => $offer->created_at->toIso8601String(),
            'updatedAt' => $offer->updated_at->toIso8601String(),
            'items' => $offer->items->map(fn (OfferItem $item) => [
                'id' => $item->id,
                'listingItemId' => $item->listing_item_id,
                'quantity' => self::quantity($item),
                'unit' => $item->unit->value,
                'unitLabel' => $item->unit->label(),
                'length' => $item->length,
            ])->all(),
            'photos' => $offer->photos->map(fn (Photo $photo) => [
                'id' => $photo->id,
                'url' => PhotoStorage::url($photo->path),
                'takenAt' => $photo->taken_at?->toIso8601String(),
            ])->all(),
        ];
    }

    private static function quantity(OfferItem $item): string
    {
        $value = rtrim(rtrim($item->quantity, '0'), '.');

        return $value === '' ? '0' : $value;
    }
}
