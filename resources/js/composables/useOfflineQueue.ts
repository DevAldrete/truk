import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { t } from '@/lib/i18n';

export type QueuedCommand = {
    id: string;
    url: string;
    method: string;
    payload: Record<string, unknown>;
    queuedAt: string;
};

const STORAGE_KEY = 'truk.driver.queue';
const FAILED_KEY = 'truk.driver.queue.failed';

const items = ref<QueuedCommand[]>(load(STORAGE_KEY));
const failed = ref<QueuedCommand[]>(load(FAILED_KEY));
let flushing = false;

/**
 * A small retry queue for driver mutations captured while offline.
 *
 * Every command already carries a client idempotency key, so replaying it is
 * safe: the server returns the existing row instead of a duplicate.
 */
export function useOfflineQueue() {
    const pending = computed(() => items.value.length);
    const failedCount = computed(() => failed.value.length);

    const enqueue = (
        url: string,
        method: string,
        payload: Record<string, unknown>,
    ) => {
        items.value = [
            ...items.value,
            {
                id: crypto.randomUUID(),
                url,
                method,
                payload,
                queuedAt: new Date().toISOString(),
            },
        ];
        persist(STORAGE_KEY, items.value);
    };

    const flush = async () => {
        if (flushing || !navigator.onLine || items.value.length === 0) {
            return;
        }

        flushing = true;
        let rejected = 0;

        try {
            for (const item of items.value) {
                try {
                    const method = item.method.toUpperCase();
                    const body =
                        method === 'POST'
                            ? item.payload
                            : { ...item.payload, _method: method };

                    const response = await fetch(item.url, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify(body),
                    });

                    if (response.ok) {
                        remove(item.id);

                        continue;
                    }

                    // Session expiry, throttling, and transient server errors
                    // are worth retrying once the situation changes.
                    if (
                        [408, 419, 429].includes(response.status) ||
                        response.status >= 500
                    ) {
                        continue;
                    }

                    // A permanent rejection (validation, forbidden, missing).
                    // Keep the payload so nothing is destroyed, but stop
                    // retrying it and tell the user it needs attention.
                    failed.value = [...failed.value, item];
                    persist(FAILED_KEY, failed.value);
                    remove(item.id);
                    rejected += 1;
                } catch {
                    // Still offline or a transient error: keep it for later.
                }
            }
        } finally {
            flushing = false;
        }

        if (rejected > 0) {
            toast.error(
                t(':count offline entries could not be synced.', {
                    count: rejected,
                }),
            );
        }
    };

    const clearFailed = () => {
        failed.value = [];
        persist(FAILED_KEY, failed.value);
    };

    return { items, failed, failedCount, pending, enqueue, flush, clearFailed };
}

function remove(id: string): void {
    items.value = items.value.filter((queued) => queued.id !== id);
    persist(STORAGE_KEY, items.value);
}

function headers(): Record<string, string> {
    return {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken(),
    };
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function load(key: string): QueuedCommand[] {
    try {
        return JSON.parse(localStorage.getItem(key) ?? '[]');
    } catch {
        return [];
    }
}

function persist(key: string, value: QueuedCommand[]): void {
    localStorage.setItem(key, JSON.stringify(value));
}
