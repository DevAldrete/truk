<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { useOnlineStatus } from '@/composables/useOnlineStatus';
import { t } from '@/lib/i18n';
import { update } from '@/routes/driver/trips/stops/status';
import type { DriverStop } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stop: DriverStop;
}>();

const { online } = useOnlineStatus();
const { enqueue } = useOfflineQueue();

const url = computed(() =>
    update.url({
        current_team: props.teamSlug,
        trip: props.tripId,
        stop: props.stop.id,
    }),
);

const form = useForm({ status: '', notes: null as string | null });

const change = (status: string) => {
    form.status = status;

    if (!online.value) {
        enqueue(url.value, 'patch', { status });
        toast.success(t('Saved offline. It will sync when you reconnect.'));

        return;
    }

    form.patch(url.value, { preserveScroll: true });
};
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <template v-if="stop.status === 'pending'">
            <Button
                size="sm"
                data-test="stop-arrive"
                @click="change('arrived')"
            >
                {{ $t('Arrive') }}
            </Button>
            <Button
                size="sm"
                variant="outline"
                data-test="stop-skip"
                @click="change('skipped')"
            >
                {{ $t('Skip') }}
            </Button>
            <Button
                size="sm"
                variant="outline"
                data-test="stop-fail"
                @click="change('failed')"
            >
                {{ $t('Fail') }}
            </Button>
        </template>

        <template v-else-if="stop.status === 'arrived'">
            <Button
                size="sm"
                data-test="stop-complete"
                @click="change('completed')"
            >
                {{ $t('Complete') }}
            </Button>
            <Button
                size="sm"
                variant="outline"
                data-test="stop-fail"
                @click="change('failed')"
            >
                {{ $t('Fail') }}
            </Button>
        </template>
    </div>
</template>
