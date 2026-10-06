import { computed, ref } from 'vue';

export type QueuedCommand = {
    id: string;
    url: string;
    method: string;
    payload: Record<string, unknown>;
    queuedAt: string;
};

const STORAGE_KEY = 'truk.driver.queue';

const items = ref<QueuedCommand[]>(load());
let flushing = false;

/**
 * A small retry queue for driver mutations captured while offline.
 *
 * Every command already carries a client idempotency key, so replaying it is
 * safe: the server returns the existing row instead of a duplicate.
 */
export function useOfflineQueue() {
    const pending = computed(() => items.value.length);

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
        persist();
    };

    const flush = async () => {
        if (flushing || !navigator.onLine || items.value.length === 0) {
            return;
        }

        flushing = true;

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

                    // A 4xx means the server rejected the command; drop it so a
                    // bad payload cannot block the queue forever.
                    if (
                        response.ok ||
                        (response.status >= 400 && response.status < 500)
                    ) {
                        items.value = items.value.filter(
                            (queued) => queued.id !== item.id,
                        );
                        persist();
                    }
                } catch {
                    // Still offline or a transient error: keep it for later.
                }
            }
        } finally {
            flushing = false;
        }
    };

    return { items, pending, enqueue, flush };
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

function load(): QueuedCommand[] {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
    } catch {
        return [];
    }
}

function persist(): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value));
}
