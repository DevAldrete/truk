<script setup lang="ts">
import { CloudOff, RefreshCw } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { useOnlineStatus } from '@/composables/useOnlineStatus';

const { online } = useOnlineStatus();
const { pending, flush } = useOfflineQueue();
const syncing = ref(false);

const sync = async () => {
    syncing.value = true;
    await flush();
    syncing.value = false;
};

const onOnline = () => {
    void sync();
};

onMounted(() => {
    window.addEventListener('online', onOnline);
    void sync();
});

onUnmounted(() => {
    window.removeEventListener('online', onOnline);
});
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            v-if="!online"
            type="button"
            class="flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-1 text-[11px] font-medium text-amber-700 dark:text-amber-400"
            data-test="offline-badge"
        >
            <CloudOff class="size-3.5" />
            {{ $t('Offline') }}
        </button>

        <button
            v-if="pending > 0"
            type="button"
            class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-[11px] font-medium text-primary"
            :disabled="syncing"
            data-test="pending-sync"
            @click="sync"
        >
            <RefreshCw class="size-3.5" :class="{ 'animate-spin': syncing }" />
            {{ $t(':count pending', { count: pending }) }}
        </button>
    </div>
</template>
