export type Option = {
    value: string;
    label: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PartyType = 'customer' | 'carrier' | 'supplier';

export type Party = {
    id: number;
    type: PartyType;
    type_label: string;
    name: string;
    legal_name: string | null;
    rfc: string | null;
    email: string | null;
    phone: string | null;
    contacts_count: number;
    locations_count: number;
};

export type PartyContact = {
    id: number;
    name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
};

export type PartyLocation = {
    id: number;
    name: string;
    city: string;
    state: string;
};

export type PartyDetail = Party & {
    contacts: PartyContact[];
    locations: PartyLocation[];
};

export type Location = {
    id: number;
    name: string;
    party_id: number | null;
    party_name: string | null;
    street: string;
    exterior_number: string | null;
    interior_number: string | null;
    neighborhood: string | null;
    city: string;
    state: string;
    postal_code: string;
    references: string | null;
};

export type Driver = {
    id: number;
    name: string;
    phone: string;
    license_number: string | null;
    license_expires_at: string | null;
    license_expired: boolean;
    carrier_party_id: number | null;
    carrier_name: string | null;
};

export type SearchResult = {
    type: 'party' | 'location';
    title: string;
    subtitle: string;
    url: string;
};
