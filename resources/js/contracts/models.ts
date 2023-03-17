import {Coordinate} from "./map";

export enum NotificationSubscriptionType {
    OWNERSHIP_CHANGES = 'ownership_changes',
    NEW_LISTINGS = 'new_listings',
}

export interface NotificationSubscriptionFilters {
    zip_codes?: string[];
    custom_areas?: Coordinate[][];
}

export interface NotificationSubscription {
    id: number;
    user_id: number;
    name: string;
    type: NotificationSubscriptionType;
    active: boolean;
    filters: NotificationSubscriptionFilters|null;
    created_at: string;
    updated_at: string;
}
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string|null;
    notification_subscriptions?: NotificationSubscription[];
    created_at: string;
    updated_at: string;
}
