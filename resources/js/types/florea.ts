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

export type HomeTab = 'for-me' | 'available' | 'mine';

/** Lo que se puede mostrar de la empresa que publica. Nunca el celular. */
export type ListingCompany = {
    name: string;
    type: 'postharvest' | 'grower';
    typeLabel: string;
    zone: string;
    verified: boolean;
};

export type NeededBy = {
    date: string | null;
    label: string | null;
    isToday: boolean;
};

export type ListingCard = {
    id: number;
    kind: ListingKind;
    kindLabel: string;
    status: ListingStatus;
    statusLabel: string;
    isMine: boolean;
    isOpen: boolean;
    company: ListingCompany;
    summary: string;
    itemCount: number;
    neededBy: NeededBy;
    expiresAt: string;
    offersCount: number;
    newOffersCount: number;
    createdAt: string;
};

export type ListingDetailItem = {
    id: number;
    variety: string;
    color: string | null;
    lengths: number[];
    quantity: string | null;
    unit: Unit | null;
    unitLabel: string | null;
    summary: string;
};

export type ListingDetail = ListingCard & {
    items: ListingDetailItem[];
    destination: string | null;
    destinationLabel: string | null;
    cutPoint: string | null;
    cutPointLabel: string | null;
    notes: string | null;
};

/** Paginador de Laravel tal como lo serializa Inertia::scroll(). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};
