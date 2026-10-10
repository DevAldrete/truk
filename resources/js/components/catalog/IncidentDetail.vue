<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/incidents';
import { show as showShipment } from '@/routes/shipments';
import { show as showTrip } from '@/routes/trips';
import type { IncidentDetail, IncidentStatus, Option } from '@/types';

const props = defineProps<{
    incident: IncidentDetail;
    teamSlug: string;
    statuses: Option[];
    canManage: boolean;
}>();

const transitions: Record<IncidentStatus, IncidentStatus[]> = {
    open: ['investigating', 'resolved', 'dismissed'],
    investigating: ['resolved', 'dismissed'],
    resolved: [],
    dismissed: [],
};

const allowedStatuses = computed(() =>
    props.statuses.filter((status) =>
        transitions[props.incident.status].includes(
            status.value as IncidentStatus,
        ),
    ),
);

const isTerminal = computed(
    () => transitions[props.incident.status].length === 0,
);

const form = useForm({
    status: '',
    resolution: '',
});

const save = () => {
    form.patch(
        update.url({
            current_team: props.teamSlug,
            incident: props.incident.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        },
    );
};

const severityClass = computed(() => {
    switch (props.incident.severity) {
        case 'critical':
            return 'border-red-300 text-red-700 dark:text-red-400';
        case 'high':
            return 'border-orange-300 text-orange-700 dark:text-orange-400';
        case 'medium':
            return 'border-amber-300 text-amber-700 dark:text-amber-400';
        default:
            return 'border-muted-foreground/30 text-muted-foreground';
    }
});

const ageLabel = computed(() => {
    const hours = props.incident.age_hours;

    return hours < 24 ? `${hours} h` : `${Math.floor(hours / 24)} d`;
});

const occurredLabel = computed(() =>
    new Date(props.incident.occurred_at).toLocaleString(),
);
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ incident.type_label }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs"
                        :class="severityClass"
                    >
                        {{ incident.severity_label }}
                    </span>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ incident.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    <template v-if="occurredLabel">
                        {{ $t('Occurred') }} {{ occurredLabel }}
                    </template>
                    <template v-if="ageLabel"> · {{ ageLabel }}</template>
                </p>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <section class="rounded-lg border p-3">
                <p class="text-sm whitespace-pre-line">
                    {{ incident.description }}
                </p>
            </section>

            <section
                v-if="
                    incident.trip_number ||
                    incident.shipment_number ||
                    incident.driver_name
                "
                class="mt-4 grid gap-3 sm:grid-cols-3"
            >
                <div v-if="incident.trip_number" class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Trip') }}
                    </p>
                    <Link
                        class="text-sm font-medium hover:underline"
                        :href="
                            showTrip({
                                current_team: teamSlug,
                                trip: incident.trip_id!,
                            })
                        "
                    >
                        {{ incident.trip_number }}
                    </Link>
                </div>

                <div
                    v-if="incident.shipment_number"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Shipment') }}
                    </p>
                    <Link
                        class="text-sm font-medium hover:underline"
                        :href="
                            showShipment({
                                current_team: teamSlug,
                                shipment: incident.shipment_id!,
                            })
                        "
                    >
                        {{ incident.shipment_number }}
                    </Link>
                </div>

                <div v-if="incident.driver_name" class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Driver') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ incident.driver_name }}
                    </p>
                </div>
            </section>

            <section
                v-if="incident.resolution"
                class="mt-4 rounded-lg border p-3"
            >
                <p class="text-xs text-muted-foreground">
                    {{ $t('Resolution') }}
                    <template v-if="incident.resolved_by_name">
                        · {{ incident.resolved_by_name }}
                    </template>
                </p>
                <p class="mt-1 text-sm whitespace-pre-line">
                    {{ incident.resolution }}
                </p>
            </section>

            <form
                v-if="canManage && !isTerminal"
                class="mt-6 grid gap-4 rounded-lg border p-4"
                @submit.prevent="save"
            >
                <h3 class="text-sm font-semibold">{{ $t('Resolve') }}</h3>

                <div class="grid gap-2">
                    <Label for="incident-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="incident-status" class="w-full">
                            <SelectValue :placeholder="$t('Select a status')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in allowedStatuses"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="incident-resolution">
                        {{ $t('Resolution notes') }}
                    </Label>
                    <Textarea
                        id="incident-resolution"
                        v-model="form.resolution"
                        :placeholder="
                            $t('Required when resolving or dismissing.')
                        "
                    />
                    <InputError :message="form.errors.resolution" />
                </div>

                <div>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing || form.status === ''"
                        data-test="resolve-incident"
                    >
                        {{ $t('Update incident') }}
                    </Button>
                </div>
            </form>

            <p
                v-else-if="isTerminal"
                class="mt-6 text-sm text-muted-foreground"
            >
                {{ $t('This incident is closed.') }}
            </p>
        </div>
    </div>
</template>
