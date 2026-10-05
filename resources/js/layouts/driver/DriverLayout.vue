<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Truck } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import OfflineBadge from '@/components/driver/OfflineBadge.vue';
import { Toaster } from '@/components/ui/sonner';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { t } from '@/lib/i18n';
import { index as driverIndex } from '@/routes/driver';

const page = usePage();
const { flush } = useOfflineQueue();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const homeUrl = computed(() =>
    driverIndex.url({ current_team: teamSlug.value }),
);

onMounted(() => {
    void flush();
});
</script>

<template>
    <div class="flex min-h-screen w-full flex-col bg-muted/30">
        <header
            class="sticky top-0 z-20 flex items-center gap-3 border-b bg-background/95 px-4 py-3 backdrop-blur"
        >
            <Link
                :href="homeUrl"
                class="flex size-9 items-center justify-center rounded-full hover:bg-accent"
                :aria-label="t('Driver portal')"
            >
                <ArrowLeft class="size-5" />
            </Link>

            <div class="flex min-w-0 flex-1 items-center gap-2">
                <Truck class="size-4 shrink-0 opacity-60" />
                <span class="truncate text-sm font-semibold">
                    {{ $t('Driver portal') }}
                </span>
            </div>

            <OfflineBadge />
        </header>

        <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-4 pb-24">
            <slot />
        </main>

        <Toaster />
    </div>
</template>
