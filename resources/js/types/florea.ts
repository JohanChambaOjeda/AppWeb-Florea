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

export type NotificationKind =
    | 'offer_received'
    | 'offer_accepted'
    | 'offer_rejected'
    | 'listing_expired'
    | 'matching_listing';

export type AppNotification = {
    id: string;
    kind: NotificationKind;
    title: string;
    body: string;
    listingId: number | null;
    url: string | null;
    read: boolean;
    createdAt: string | null;
};

/** Los números públicos de una empresa. Nunca comentarios. */
export type Reputation = {
    isNew: boolean;
    stars: number | null;
    ratings: number;
    deals: number;
    /** Del productor: qué parte de sus entregas llegó a tiempo. */
    onTimePct: number | null;
    /** De la poscosecha: qué parte de sus pagos fue como se acordó. */
    paidPct: number | null;
};

/** Lo que se puede mostrar de la empresa que publica. Nunca el celular. */
export type ListingCompany = {
    name: string;
    type: 'postharvest' | 'grower';
    typeLabel: string;
    zone: string;
    verified: boolean;
    reputation: Reputation;
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

export type Delivery = 'i_deliver' | 'pickup' | 'to_agree';

export type OfferItem = {
    id: number;
    listingItemId: number;
    quantity: string;
    unit: Unit;
    unitLabel: string;
    length: number | null;
};

export type OfferPhoto = {
    id: number;
    url: string;
    takenAt: string | null;
};

/**
 * El contacto de la otra empresa. Solo llega cuando la oferta está aceptada
 * y solo a las dos partes del trato (regla 6).
 */
export type OfferContact = {
    name: string;
    companyName: string;
    phone: string;
    whatsappUrl: string;
    /** El trato que abrió el contacto. */
    dealId: number | null;
    /** Si esta empresa ya calificó, no se le vuelve a pedir. */
    rated: boolean;
};

type OfferBase = {
    id: number;
    status: OfferStatus;
    statusLabel: string;
    pricePerStem: string | null;
    delivery: Delivery;
    deliveryLabel: string;
    /** Solo en la oferta de quien compra: el plazo en que va a pagar. */
    paymentTerm: string | null;
    paymentTermLabel: string | null;
    notes: string | null;
    createdAt: string;
    updatedAt: string;
    items: OfferItem[];
    photos: OfferPhoto[];
    contact: OfferContact | null;
};

export type MyOffer = OfferBase & {
    canEdit: boolean;
    canWithdraw: boolean;
};

/** Una oferta recibida, vista por quien publicó. */
export type ReceivedOffer = OfferBase & {
    company: ListingCompany;
    canRespond: boolean;
};

/** Mi oferta más la publicación a la que pertenece, para "Mis ofertas". */
export type MyOfferWithListing = MyOffer & {
    listing: {
        id: number;
        kind: ListingKind;
        kindLabel: string;
        status: ListingStatus;
        statusLabel: string;
        companyName: string;
        zone: string;
        neededByLabel: string | null;
    };
};

/** Una línea de la oferta mientras se está llenando el formulario. */
export type OfferItemDraft = {
    checked: boolean;
    quantity: string;
    unit: Unit;
    length: number | null;
};

/** Paginador de Laravel tal como lo serializa Inertia::scroll(). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};
