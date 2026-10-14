export type UserRole = 'owner' | 'seller' | 'admin';

export type User = {
    id: number;
    company_id: number | null;
    name: string;
    phone: string;
    email: string | null;
    role: UserRole;
    avatar?: string;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type CompanyType = 'postharvest' | 'grower';

export type AuthCompany = {
    id: number;
    name: string;
    type: CompanyType;
    verified: boolean;
};

export type Auth = {
    user: User;
    company: AuthCompany | null;
    isAdmin: boolean;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
