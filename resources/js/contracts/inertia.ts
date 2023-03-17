import { User } from "./models";
import { usePage } from "@inertiajs/vue3";
import { Page, PageProps } from "@inertiajs/core";
import { get } from "lodash";

/** Keep AppPageProps in sync with HandleInertiaRequests.php */
export interface AppPageProps extends PageProps {
    appName: string;
    csrfToken: string;
    "auth.user": User|null;
}

export function getAppProp<K extends keyof AppPageProps>(prop: K): AppPageProps[K] {
    return get((usePage() as Page<AppPageProps>).props, prop);
}
