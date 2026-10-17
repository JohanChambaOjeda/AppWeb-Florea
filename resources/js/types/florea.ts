export type Variety = {
    id: number;
    name: string;
    color: string | null;
};

/** Opción de un enum de PHP: { value, label }. */
export type Option = {
    value: string;
    label: string;
};

export type LengthRange = {
    min: number;
    max: number;
};

export type ListingKind = 'want' | 'have';

export type ListingStatus = 'open' | 'closed' | 'expired';

export type OfferStatus = 'sent' | 'accepted' | 'rejected' | 'withdrawn';

export type Unit = 'fb' | 'hb' | 'qb' | 'bunch' | 'mesh' | 'stem';

/** Una línea del formulario de publicación, antes de enviarse. */
export type ListingItemDraft = {
    variety_id: number | null;
    lengths: number[];
    quantity: string;
    unit: Unit;
};
