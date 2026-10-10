<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { destroy, update } from '@/routes/trips';
import { update as updateResources } from '@/routes/trips/resources';
import {
    destroy as destroyStop,
    reorder,
    store as storeStop,
} from '@/routes/trips/stops';
import {
    destroy as detachStopShipment,
    store as attachStopShipment,
} from '@/routes/trips/stops/shipments';
import { t } from '@/lib/i18n';
import { show as showShipment } from '@/routes/shipments';
import type { Option, TripDetail } from '@/types';

const props = defineProps<{
    trip: TripDetail;
    teamSlug: string;
    drivers: Option[];
    vehicles: Option[];
    trailers: Option[];
    locations: Option[];
    shipments: Option[];
    statuses: Option[];
    stopTypes: Option[];
    stopStatuses: Option[];
    canManage: boolean;
    canOverride: boolean;
    canOverrideCompliance: boolean;
}>();

const timezones = [
    'America/Mexico_City',
    'America/Monterrey',
    'America/Chihuahua',
    'America/Mazatlan',
    'America/Hermosillo',
    'America/Tijuana',
    'America/Cancun',
];

const toLocalInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const form = useForm({
    status: props.trip.status,
    planned_start_at: toLocalInput(props.trip.planned_start_at),
    planned_end_at: toLocalInput(props.trip.planned_end_at),
    timezone: props.trip.timezone ?? 'America/Mexico_City',
    notes: props.trip.notes ?? '',
    capacity_override_reason: props.trip.capacity_override_reason ?? '',
    compliance_override_reason: props.trip.compliance.override_reason ?? '',
});

const capacity = computed(() => props.trip.capacity);

const save = () => {
    form.transform((data) => ({
        ...data,
        planned_start_at: data.planned_start_at || null,
        planned_end_at: data.planned_end_at || null,
    }));

    form.patch(
        update.url({ current_team: props.teamSlug, trip: props.trip.id }),
        {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        },
    );
};

const resources = useForm({
    driver_id: props.trip.driver_id ? String(props.trip.driver_id) : 'none',
    vehicle_id: props.trip.vehicle_id ? String(props.trip.vehicle_id) : 'none',
    trailer_id: props.trip.trailer_id ? String(props.trip.trailer_id) : 'none',
});

const saveResources = () => {
    resources.transform((data) => ({
        driver_id: data.driver_id === 'none' ? null : data.driver_id,
        vehicle_id: data.vehicle_id === 'none' ? null : data.vehicle_id,
        trailer_id: data.trailer_id === 'none' ? null : data.trailer_id,
    }));

    resources.put(
        updateResources.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
        }),
        { preserveScroll: true, onSuccess: () => resources.defaults() },
    );
};

const hasResources = computed(
    () =>
        props.trip.driver_name ||
        props.trip.vehicle_name ||
        props.trip.trailer_name,
);

const resourceLabel = (resource: string): string =>
    resource === 'driver'
        ? t('Driver')
        : resource === 'vehicle'
          ? t('Vehicle')
          : t('Trailer');

const stopForm = useForm({
    type: 'delivery',
    location_id: 'none',
    planned_at: '',
    status: 'pending',
    notes: '',
});

const submitStop = () => {
    stopForm.transform((data) => ({
        ...data,
        location_id: data.location_id === 'none' ? null : data.location_id,
        planned_at: data.planned_at || null,
    }));

    stopForm.post(
        storeStop.url({ current_team: props.teamSlug, trip: props.trip.id }),
        {
            preserveScroll: true,
            onSuccess: () => stopForm.reset(),
        },
    );
};

const attachSelection = reactive<Record<number, string>>({});

const attachShipmentToStop = (stopId: number) => {
    const shipmentId = attachSelection[stopId];

    if (!shipmentId || shipmentId === 'none') {
        return;
    }

    router.post(
        attachStopShipment.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
            stop: stopId,
        }),
        { shipment_id: shipmentId },
        {
            preserveScroll: true,
            onSuccess: () => {
                attachSelection[stopId] = 'none';
            },
        },
    );
};

const detachShipmentFromStop = (stopId: number, shipmentId: number) => {
    router.delete(
        detachStopShipment.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
            stop: stopId,
            shipment: shipmentId,
        }),
        { preserveScroll: true },
    );
};

const removeStop = (stopId: number) => {
    router.delete(
        destroyStop.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
            stop: stopId,
        }),
        { preserveScroll: true },
    );
};

const moveStop = (index: number, direction: number) => {
    const ids = props.trip.stops.map((stop) => stop.id);
    const target = index + direction;

    if (target < 0 || target >= ids.length) {
        return;
    }

    [ids[index], ids[target]] = [ids[target], ids[index]];

    router.put(
        reorder.url({ current_team: props.teamSlug, trip: props.trip.id }),
        { stop_ids: ids },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ trip.number }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ trip.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ trip.driver_name ?? $t('No driver') }}
                    <template v-if="trip.vehicle_name">
                        · {{ trip.vehicle_name }}
                    </template>
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="destroy.url({ current_team: teamSlug, trip: trip.id })"
                :title="$t('Delete :name?', { name: trip.number })"
                :description="$t('The trip will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="trip-detail-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status" :disabled="!canManage">
                        <SelectTrigger id="trip-detail-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in statuses"
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
                    <Label for="trip-detail-timezone">
                        {{ $t('Timezone') }}
                    </Label>
                    <Select v-model="form.timezone" :disabled="!canManage">
                        <SelectTrigger id="trip-detail-timezone" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="zone in timezones"
                                :key="zone"
                                :value="zone"
                            >
                                {{ zone }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.timezone" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-detail-start">
                        {{ $t('Planned start') }}
                    </Label>
                    <Input
                        id="trip-detail-start"
                        v-model="form.planned_start_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.planned_start_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-detail-end">{{ $t('Planned end') }}</Label>
                    <Input
                        id="trip-detail-end"
                        v-model="form.planned_end_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.planned_end_at" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="trip-detail-notes">{{ $t('Notes') }}</Label>
                    <Textarea
                        id="trip-detail-notes"
                        v-model="form.notes"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-2"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-trip-changes"
                    >
                        {{ $t('Save changes') }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="form.reset()"
                    >
                        {{ $t('Discard') }}
                    </Button>
                </div>
            </form>

            <section
                class="mt-6 rounded-lg border p-4"
                :class="capacity.over ? 'border-destructive/50' : ''"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">{{ $t('Capacity') }}</h3>
                    <span
                        v-if="capacity.over"
                        class="rounded-full bg-destructive/10 px-2 py-0.5 text-xs text-destructive"
                    >
                        {{ $t('Over capacity') }}
                    </span>
                </div>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <div
                            class="flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span>{{ $t('Weight') }}</span>
                            <span>
                                {{
                                    (
                                        capacity.weight_grams / 1000
                                    ).toLocaleString()
                                }}
                                kg
                                <template v-if="capacity.weight_limit_grams">
                                    /
                                    {{
                                        (
                                            capacity.weight_limit_grams / 1000
                                        ).toLocaleString()
                                    }}
                                    kg
                                </template>
                            </span>
                        </div>
                        <div
                            class="mt-1 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="
                                    capacity.over_weight
                                        ? 'bg-destructive'
                                        : 'bg-primary'
                                "
                                :style="{
                                    width:
                                        Math.min(
                                            capacity.weight_utilization ?? 0,
                                            100,
                                        ) + '%',
                                }"
                            />
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                capacity.weight_utilization !== null
                                    ? capacity.weight_utilization + '%'
                                    : $t('No limit')
                            }}
                        </p>
                    </div>

                    <div>
                        <div
                            class="flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span>{{ $t('Volume') }}</span>
                            <span>
                                {{
                                    (
                                        capacity.volume_cm3 / 1000000
                                    ).toLocaleString()
                                }}
                                m³
                                <template v-if="capacity.volume_limit_cm3">
                                    /
                                    {{
                                        (
                                            capacity.volume_limit_cm3 / 1000000
                                        ).toLocaleString()
                                    }}
                                    m³
                                </template>
                            </span>
                        </div>
                        <div
                            class="mt-1 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="
                                    capacity.over_volume
                                        ? 'bg-destructive'
                                        : 'bg-primary'
                                "
                                :style="{
                                    width:
                                        Math.min(
                                            capacity.volume_utilization ?? 0,
                                            100,
                                        ) + '%',
                                }"
                            />
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                capacity.volume_utilization !== null
                                    ? capacity.volume_utilization + '%'
                                    : $t('No limit')
                            }}
                        </p>
                    </div>
                </div>

                <p class="mt-2 text-xs text-muted-foreground">
                    {{
                        $t(':count shipments', {
                            count: capacity.shipments_count,
                        })
                    }}
                </p>

                <div
                    v-if="capacity.over && canOverride"
                    class="mt-3 grid gap-2"
                >
                    <Label for="trip-override">
                        {{ $t('Override reason') }}
                    </Label>
                    <Textarea
                        id="trip-override"
                        v-model="form.capacity_override_reason"
                        :disabled="!canManage"
                    />
                    <InputError
                        :message="form.errors.capacity_override_reason"
                    />
                </div>
                <p
                    v-else-if="capacity.over"
                    class="mt-2 text-xs text-destructive"
                >
                    {{ $t('You cannot override this capacity.') }}
                </p>

                <p
                    v-if="trip.capacity_overridden_at"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{ $t('Overridden:') }}
                    {{ trip.capacity_override_reason }}
                </p>
            </section>

            <section
                class="mt-6 rounded-lg border p-4"
                :class="!trip.compliance.ok ? 'border-destructive/50' : ''"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">
                        {{ $t('Compliance') }}
                    </h3>
                    <span
                        v-if="!trip.compliance.ok"
                        class="rounded-full bg-destructive/10 px-2 py-0.5 text-xs text-destructive"
                    >
                        {{ $t('Non-compliant') }}
                    </span>
                </div>

                <ul
                    v-if="trip.compliance.violations.length"
                    class="mt-2 list-disc space-y-1 pl-5 text-xs text-destructive"
                >
                    <li
                        v-for="(violation, index) in trip.compliance.violations"
                        :key="index"
                    >
                        {{ violation.message }}
                    </li>
                </ul>
                <p v-else class="mt-2 text-xs text-muted-foreground">
                    {{ $t('All required documents are valid.') }}
                </p>

                <div
                    v-if="!trip.compliance.ok && canOverrideCompliance"
                    class="mt-3 grid gap-2"
                >
                    <Label for="trip-compliance-override">
                        {{ $t('Override reason') }}
                    </Label>
                    <Textarea
                        id="trip-compliance-override"
                        v-model="form.compliance_override_reason"
                        :disabled="!canManage"
                    />
                    <InputError
                        :message="form.errors.compliance_override_reason"
                    />
                </div>
                <p
                    v-else-if="!trip.compliance.ok"
                    class="mt-2 text-xs text-destructive"
                >
                    {{ $t('You cannot override compliance.') }}
                </p>

                <p
                    v-if="trip.compliance.overridden_at"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{ $t('Overridden:') }}
                    {{ trip.compliance.override_reason }}
                </p>
            </section>

            <section class="mt-6 rounded-lg border p-4">
                <h3 class="text-sm font-semibold">{{ $t('Resources') }}</h3>

                <form
                    class="mt-3 grid gap-3 sm:grid-cols-3"
                    @submit.prevent="saveResources"
                >
                    <div class="grid gap-2">
                        <Label for="trip-driver">{{ $t('Driver') }}</Label>
                        <Select
                            v-model="resources.driver_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-driver" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in drivers"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.driver_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="trip-vehicle">{{ $t('Vehicle') }}</Label>
                        <Select
                            v-model="resources.vehicle_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-vehicle" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in vehicles"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.vehicle_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="trip-trailer">{{ $t('Trailer') }}</Label>
                        <Select
                            v-model="resources.trailer_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-trailer" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in trailers"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.trailer_id" />
                    </div>

                    <div
                        v-if="resources.isDirty"
                        class="flex items-center gap-2 sm:col-span-3"
                    >
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="resources.processing"
                            data-test="save-trip-resources"
                        >
                            {{ $t('Assign') }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resources.reset()"
                        >
                            {{ $t('Discard') }}
                        </Button>
                    </div>
                </form>

                <p
                    v-if="!hasResources"
                    class="mt-3 text-xs text-muted-foreground"
                >
                    {{ $t('No resources assigned yet.') }}
                </p>
            </section>

            <section v-if="trip.assignments.length" class="mt-6">
                <h3 class="text-sm font-semibold">
                    {{ $t('Assignment history') }}
                </h3>
                <ul class="mt-2 divide-y rounded-lg border">
                    <li
                        v-for="assignment in trip.assignments"
                        :key="assignment.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span>
                            {{ assignment.name }}
                            <span class="text-xs text-muted-foreground">
                                · {{ resourceLabel(assignment.resource) }}
                            </span>
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{
                                assignment.released_at
                                    ? $t('Released')
                                    : $t('Active')
                            }}
                        </span>
                    </li>
                </ul>
            </section>

            <section class="mt-6">
                <h3 class="text-sm font-semibold">{{ $t('Stops') }}</h3>

                <ol v-if="trip.stops.length" class="mt-2 space-y-2">
                    <li
                        v-for="(stop, index) in trip.stops"
                        :key="stop.id"
                        class="rounded-lg border p-3"
                        :data-test="`stop-${stop.id}`"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium">
                                    <span class="text-muted-foreground">
                                        {{ stop.sequence }}.
                                    </span>
                                    {{ stop.type_label }}
                                    <span class="text-xs text-muted-foreground">
                                        · {{ stop.status_label }}
                                    </span>
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ stop.location_name ?? $t('No site') }}
                                </p>
                                <p
                                    v-if="stop.location_snapshot"
                                    class="truncate text-[11px] text-muted-foreground"
                                >
                                    {{ stop.location_snapshot.street }}
                                    <template
                                        v-if="
                                            stop.location_snapshot
                                                .exterior_number
                                        "
                                    >
                                        {{
                                            stop.location_snapshot
                                                .exterior_number
                                        }}
                                    </template>
                                    · {{ stop.location_snapshot.city }}
                                </p>
                            </div>

                            <div
                                v-if="canManage"
                                class="flex items-center gap-1"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :disabled="index === 0"
                                    @click="moveStop(index, -1)"
                                >
                                    <ArrowUp class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :disabled="index === trip.stops.length - 1"
                                    @click="moveStop(index, 1)"
                                >
                                    <ArrowDown class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-muted-foreground hover:text-destructive"
                                    @click="removeStop(stop.id)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="stop.shipments.length"
                            class="mt-2 flex flex-wrap gap-2"
                        >
                            <span
                                v-for="shipment in stop.shipments"
                                :key="shipment.id"
                                class="flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs"
                            >
                                <Link
                                    class="hover:underline"
                                    :href="
                                        showShipment({
                                            current_team: teamSlug,
                                            shipment: shipment.id,
                                        })
                                    "
                                >
                                    {{ shipment.number }}
                                </Link>
                                <button
                                    v-if="canManage"
                                    type="button"
                                    class="text-muted-foreground hover:text-destructive"
                                    @click="
                                        detachShipmentFromStop(
                                            stop.id,
                                            shipment.id,
                                        )
                                    "
                                >
                                    ×
                                </button>
                            </span>
                        </div>

                        <div
                            v-if="canManage"
                            class="mt-2 flex items-center gap-2"
                        >
                            <Select
                                :model-value="
                                    attachSelection[stop.id] ?? 'none'
                                "
                                @update:model-value="
                                    (value) =>
                                        (attachSelection[stop.id] =
                                            String(value))
                                "
                            >
                                <SelectTrigger class="h-8 min-w-0 flex-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        {{ $t('Select a shipment') }}
                                    </SelectItem>
                                    <SelectItem
                                        v-for="item in shipments"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="attachShipmentToStop(stop.id)"
                            >
                                <Plus class="size-3.5" />
                            </Button>
                        </div>
                    </li>
                </ol>

                <p v-else class="mt-2 text-sm text-muted-foreground">
                    {{ $t('No stops yet.') }}
                </p>

                <form
                    v-if="canManage"
                    class="mt-3 grid gap-3 rounded-lg border p-3 sm:grid-cols-4"
                    @submit.prevent="submitStop"
                >
                    <div class="grid gap-2">
                        <Label for="stop-type">{{ $t('Type') }}</Label>
                        <Select v-model="stopForm.type">
                            <SelectTrigger id="stop-type" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="item in stopTypes"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="stopForm.errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="stop-location">{{ $t('Site') }}</Label>
                        <Select v-model="stopForm.location_id">
                            <SelectTrigger id="stop-location" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in locations"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="stopForm.errors.location_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="stop-planned">{{ $t('Planned at') }}</Label>
                        <Input
                            id="stop-planned"
                            v-model="stopForm.planned_at"
                            type="datetime-local"
                        />
                        <InputError :message="stopForm.errors.planned_at" />
                    </div>

                    <div class="flex items-end">
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="stopForm.processing"
                            data-test="add-stop"
                        >
                            <Plus class="size-4" />
                            {{ $t('Add stop') }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
