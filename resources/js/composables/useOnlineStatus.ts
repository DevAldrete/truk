import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Tracks the browser's connectivity so the portal can switch to the offline
 * queue without waiting for a failed request.
 */
export function useOnlineStatus() {
    const online = ref(
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );

    const update = () => {
        online.value = navigator.onLine;
    };

    onMounted(() => {
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
    });

    onUnmounted(() => {
        window.removeEventListener('online', update);
        window.removeEventListener('offline', update);
    });

    return { online };
}
