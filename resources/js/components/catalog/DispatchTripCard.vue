<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import CapacityGauge from '@/components/catalog/CapacityGauge.vue';
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
import { update as updateResources } from '@/routes/trips/resources';
import { dispatch as dispatchTrip, show as showTrip } from '@/routes/trips';
import {
    destroy as removeTripShipment,
    store as assignTripShipment,
} from '@/routes/trips/shipments';
import { show as showShipment } from '@/routes/shipments';
import type { DispatchPoolShipment, DispatchTrip, Option } from '@/types';

const props = defineProps<{
    trip: DispatchTrip;
    teamSlug: string;
    drivers: Option[];
    vehicles: Option[];
    trailers: Option[];
    pool: DispatchPoolShipment[];
    canManage: boolean;
    canOverride: boolean;
    canOverrideCompliance: boolean;
}>();

const formatDate = (value: string | null): string => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
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

const dispatchForm = useForm({
    status: 'dispatched',
    capacity_override_reason: '',
    compliance_override_reason: '',
});

const dispatch = () => {
    dispatchForm.post(
        dispatchTrip.url({ current_team: props.teamSlug, trip: props.trip.id }),
        { preserveScroll: true },
    );
};

const assignSelection = ref('none');

const assign = () => {
    if (assignSelection.value === 'none') {
        return;
    }

    router.post(
        assignTripShipment.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
        }),
        { shipment_id: assignSelection.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignSelection.value = 'none';
            },
        },
    );
};

const removeShipment = (shipmentId: number) => {
    router.delete(
        removeTripShipment.url({
            current_team: props.teamSlug,
            trip: props.trip.id,
            shipment: shipmentId,
        }),
        { preserveScroll: true },
    );
};
</script>

<template>
    <article
        class="rounded-xl border p-4"
        :class="
            trip.capacity.over || !trip.compliance.ok
                ? 'border-destructive/50'
                : ''
        "
        :data-test="`dispatch-trip-${trip.id}`"
    >
        <header class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <Link
                    class="text-sm font-semibold hover:underline"
                    :href="showTrip({ current_team: teamSlug, trip: trip.id })"
                >
                    {{ trip.number }}
                </Link>
                <p class="text-xs text-muted-foreground">
                    {{ trip.status_label }}
                    <template v-if="trip.planned_start_at">
                        · {{ formatDate(trip.planned_start_at) }}
                    </template>
                </p>
            </div>
        </header>

        <div class="mt-3 grid grid-cols-3 gap-2">
            <Select v-model="resources.driver_id" :disabled="!canManage">
                <SelectTrigger class="h-8 w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">{{ $t('Driver') }}</SelectItem>
                    <SelectItem
                        v-for="item in drivers"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="resources.vehicle_id" :disabled="!canManage">
                <SelectTrigger class="h-8 w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">{{ $t('Vehicle') }}</SelectItem>
                    <SelectItem
                        v-for="item in vehicles"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="resources.trailer_id" :disabled="!canManage">
                <SelectTrigger class="h-8 w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">{{ $t('Trailer') }}</SelectItem>
                    <SelectItem
                        v-for="item in trailers"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="resources.isDirty" class="mt-2 flex items-center gap-2">
            <Button
                size="sm"
                variant="outline"
                :disabled="resources.processing"
                @click="saveResources"
            >
                {{ $t('Assign') }}
            </Button>
            <Button size="sm" variant="ghost" @click="resources.reset()">
                {{ $t('Discard') }}
            </Button>
        </div>
        <InputError :message="resources.errors.driver_id" />
        <InputError :message="resources.errors.vehicle_id" />
        <InputError :message="resources.errors.trailer_id" />

        <div class="mt-4">
            <CapacityGauge :capacity="trip.capacity" />
        </div>

        <div
            v-if="!trip.compliance.ok"
            class="mt-3 rounded-md border border-destructive/40 p-2"
        >
            <p class="text-xs font-medium text-destructive">
                {{ $t('Non-compliant') }}
            </p>
            <ul
                class="mt-1 list-disc space-y-0.5 pl-4 text-[11px] text-destructive"
            >
                <li
                    v-for="(violation, index) in trip.compliance.violations"
                    :key="index"
                >
                    {{ violation.message }}
                </li>
            </ul>
        </div>

        <div class="mt-4">
            <p class="text-xs font-medium text-muted-foreground">
                {{ $t('Shipments') }}
            </p>

            <div v-if="trip.shipments.length" class="mt-2 flex flex-wrap gap-2">
                <span
                    v-for="shipment in trip.shipments"
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
                        @click="removeShipment(shipment.id)"
                    >
                        <X class="size-3" />
                    </button>
                </span>
            </div>
            <p v-else class="mt-2 text-xs text-muted-foreground">
                {{ $t('No shipments yet.') }}
            </p>

            <div v-if="canManage" class="mt-2 flex items-center gap-2">
                <Select v-model="assignSelection">
                    <SelectTrigger class="h-8 flex-1">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">
                            {{ $t('Select a shipment') }}
                        </SelectItem>
                        <SelectItem
                            v-for="item in pool"
                            :key="item.id"
                            :value="String(item.id)"
                        >
                            {{ item.number }} ·
                            {{ item.customer_name ?? $t('No customer') }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="assignSelection === 'none'"
                    @click="assign"
                >
                    <Plus class="size-3.5" />
                </Button>
            </div>
        </div>

        <div
            v-if="canManage && trip.status === 'planned'"
            class="mt-4 flex flex-col gap-3"
        >
            <div v-if="trip.capacity.over && canOverride" class="grid gap-2">
                <Label :for="`override-${trip.id}`">
                    {{ $t('Override reason') }}
                </Label>
                <Textarea
                    :id="`override-${trip.id}`"
                    v-model="dispatchForm.capacity_override_reason"
                />
                <InputError
                    :message="dispatchForm.errors.capacity_override_reason"
                />
            </div>

            <div
                v-if="!trip.compliance.ok && canOverrideCompliance"
                class="grid gap-2"
            >
                <Label :for="`compliance-override-${trip.id}`">
                    {{ $t('Compliance override reason') }}
                </Label>
                <Textarea
                    :id="`compliance-override-${trip.id}`"
                    v-model="dispatchForm.compliance_override_reason"
                />
                <InputError
                    :message="dispatchForm.errors.compliance_override_reason"
                />
            </div>

            <div>
                <Button
                    size="sm"
                    :disabled="dispatchForm.processing"
                    @click="dispatch"
                >
                    {{ $t('Dispatch') }}
                </Button>
            </div>
        </div>
        <InputError :message="dispatchForm.errors.status" />
    </article>
</template>
