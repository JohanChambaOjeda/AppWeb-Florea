<?php

namespace App\Support;

use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Photo;
use App\Models\User;

/**
 * Da forma a una oferta para el frontend.
 *
 * REGLA 6: contact() es el ÚNICO sitio de toda la app que devuelve un celular.
 * Solo lo hace si la oferta está aceptada (o sea, si existe el trato) y solo
 * a las dos empresas que lo cerraron. Cualquier pantalla que muestre ofertas
 * pasa por aquí, así que no hay forma de filtrar un contacto por accidente.
 */
final class OfferPresenter
{
    /**
     * Para quien publicó: las ofertas que recibió, para compararlas.
     *
     * @return array<string, mixed>
     */
    public static function forPublisher(Offer $offer, User $viewer): array
    {
        return [
            ...self::base($offer),
            'company' => self::company($offer->company),
            'canRespond' => $offer->isSent() && $offer->listing->isOpen(),
            'contact' => self::contact($offer, $viewer),
        ];
    }

    /**
     * Para quien ofertó: mi propia oferta.
     *
     * @return array<string, mixed>
     */
    public static function mine(Offer $offer, ?User $viewer = null): array
    {
        return [
            ...self::base($offer),
            'canEdit' => $offer->isSent(),
            'canWithdraw' => $offer->isSent(),
            'contact' => $viewer !== null ? self::contact($offer, $viewer) : null,
        ];
    }

    /**
     * Para "Mis ofertas": la oferta más de qué publicación es.
     *
     * @return array<string, mixed>
     */
    public static function withListing(Offer $offer, User $viewer): array
    {
        $listing = $offer->listing;

        return [
            ...self::mine($offer, $viewer),
            'listing' => [
                'id' => $listing->id,
                'kind' => $listing->kind->value,
                'kindLabel' => $listing->kind->label(),
                'status' => $listing->status->value,
                'statusLabel' => $listing->status->label(),
                'companyName' => $listing->company->name,
                'zone' => $listing->company->zone->name,
                'neededByLabel' => $listing->needed_by?->isToday() === true
                    ? 'Hoy'
                    : $listing->needed_by?->translatedFormat('D j M'),
            ],
        ];
    }

    /**
     * REGLA 6. El celular de la otra empresa, solo con trato cerrado.
     *
     * @return array<string, mixed>|null
     */
    private static function contact(Offer $offer, User $viewer): ?array
    {
        if ($offer->status !== OfferStatus::Accepted) {
            return null;
        }

        $listing = $offer->listing;
        $isPublisher = $listing->company_id === $viewer->company_id;
        $isOfferer = $offer->company_id === $viewer->company_id;

        // Un tercero nunca ve el contacto, ni siquiera con la oferta aceptada.
        if (! $isPublisher && ! $isOfferer) {
            return null;
        }

        $other = $isPublisher ? $offer->user : $listing->user;
        $otherCompany = $isPublisher ? $offer->company : $listing->company;

        return [
            'name' => $other->name,
            'companyName' => $otherCompany->name,
            'phone' => PhoneNumber::display($other->phone),
            'whatsappUrl' => 'https://wa.me/'.PhoneNumber::whatsapp($other->phone)
                .'?text='.rawurlencode(self::whatsappMessage($offer, $viewer, $isPublisher)),
        ];
    }

    /**
     * "Hola, soy María de Poscosecha Rosales del Norte. Acepté tu oferta en
     * Florea: 3 HB Explorer 70 a $0,45 el tallo. ¿Coordinamos la entrega?"
     */
    private static function whatsappMessage(Offer $offer, User $viewer, bool $isPublisher): string
    {
        $company = $viewer->company->name ?? '';
        $what = self::summary($offer);
        $price = $offer->price_per_stem !== null
            ? ' a $'.number_format((float) $offer->price_per_stem, 2, ',', '').' el tallo'
            : '';

        // Quien publicó acepta; quien ofertó recibe la aceptación.
        $verb = $isPublisher ? 'Acepté tu oferta' : 'Aceptaste mi oferta';

        return "Hola, soy {$viewer->name} de {$company}. {$verb} en Florea: {$what}{$price}. ¿Coordinamos la entrega?";
    }

    /**
     * "3 HB Explorer 70, 2 HB Freedom 60"
     */
    public static function summary(Offer $offer): string
    {
        return $offer->items
            ->map(fn (OfferItem $item) => trim(implode(' ', array_filter([
                self::quantity($item),
                $item->unit->label(),
                $item->listingItem->variety->name,
                $item->length !== null ? (string) $item->length : null,
            ]))))
            ->implode(', ');
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

    /**
     * Lo que se puede mostrar de la empresa que oferta. Sin celular.
     *
     * @return array<string, mixed>
     */
    private static function company(Company $company): array
    {
        return [
            'name' => $company->name,
            'type' => $company->type->value,
            'typeLabel' => $company->type->label(),
            'zone' => $company->zone->name,
            'verified' => $company->isVerified(),
            'reputation' => Reputation::of($company),
        ];
    }

    private static function quantity(OfferItem $item): string
    {
        $value = rtrim(rtrim($item->quantity, '0'), '.');

        return $value === '' ? '0' : $value;
    }
}
