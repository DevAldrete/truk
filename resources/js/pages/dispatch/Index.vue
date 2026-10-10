<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Package } from '@lucide/vue';
import { computed, reactive } from 'vue';
import DispatchTripCard from '@/components/catalog/DispatchTripCard.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store as assignTripShipment } from '@/routes/trips/shipments';
import { show as showShipment } from '@/routes/shipments';
import type { DispatchPoolShipment, DispatchTrip, Option } from '@/types';

const props = defineProps<{
    trips: DispatchTrip[];
    pool: DispatchPoolShipment[];
    drivers: Option[];
    vehicles: Option[];
    trailers: Option[];
    can: {
        manage: boolean;
        overrideCapacity: boolean;
        overrideCompliance: boolean;
    };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const selection = reactive<Record<number, string>>({});

const assignTo = (shipmentId: number) => {
    const tripId = selection[shipmentId];

    if (!tripId || tripId === 'none') {
        return;
    }

    router.post(
        assignTripShipment.url({
            current_team: teamSlug.value,
            trip: Number(tripId),
        }),
        { shipment_id: shipmentId },
        {
            preserveScroll: true,
            onSuccess: () => {
                selection[shipmentId] = 'none';
            },
        },
    );
};
</script>

<template>
    <Head :title="$t('Dispatch')" />

    <div class="flex h-full min-h-0 flex-col gap-4 overflow-y-auto p-4">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">
                    {{ $t('Dispatch board') }}
                </h1>
                <p class="text-xs text-muted-foreground">
                    {{ $t('Assign shipments to trips and dispatch them.') }}
                </p>
            </div>
        </header>

        <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <DispatchTripCard
                    v-for="trip in trips"
                    :key="trip.id"
                    :trip="trip"
                    :team-slug="teamSlug"
                    :drivers="drivers"
                    :vehicles="vehicles"
                    :trailers="trailers"
                    :pool="pool"
                    :can-manage="can.manage"
                    :can-override="can.overrideCapacity"
                    :can-override-compliance="can.overrideCompliance"
                />

                <div
                    v-if="trips.length === 0"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground sm:col-span-2 xl:col-span-3"
                >
                    {{
                        $t('No open trips. Create a trip to start dispatching.')
                    }}
                </div>
            </section>

            <aside class="rounded-xl border p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">
                        {{ $t('Unassigned shipments') }}
                    </h2>
                    <span class="text-xs text-muted-foreground">
                        {{ pool.length }}
                    </span>
                </div>

                <ul v-if="pool.length" class="mt-3 space-y-2">
                    <li
                        v-for="shipment in pool"
                        :key="shipment.id"
                        class="rounded-lg border p-2"
                        :data-test="`pool-shipment-${shipment.id}`"
                    >
                        <div class="flex items-center gap-2">
                            <Package class="size-4 shrink-0 opacity-50" />
                            <div class="min-w-0 flex-1">
                                <Link
                                    class="truncate text-sm font-medium hover:underline"
                                    :href="
                                        showShipment({
                                            current_team: teamSlug,
                                            shipment: shipment.id,
                                        })
                                    "
                                >
                                    {{ shipment.number }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        shipment.customer_name ??
                                        $t('No customer')
                                    }}
                                    · {{ shipment.pieces }} {{ $t('pcs') }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="can.manage && trips.length"
                            class="mt-2 flex items-center gap-2"
                        >
                            <Select
                                :model-value="selection[shipment.id] ?? 'none'"
                                @update:model-value="
                                    (value) =>
                                        (selection[shipment.id] = String(value))
                                "
                            >
                                <SelectTrigger class="h-8 flex-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        {{ $t('Select a trip') }}
                                    </SelectItem>
                                    <SelectItem
                                        v-for="trip in trips"
                                        :key="trip.id"
                                        :value="String(trip.id)"
                                    >
                                        {{ trip.number }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                size="sm"
                                variant="outline"
                                :disabled="
                                    (selection[shipment.id] ?? 'none') ===
                                    'none'
                                "
                                @click="assignTo(shipment.id)"
                            >
                                {{ $t('Assign') }}
                            </Button>
                        </div>
                    </li>
                </ul>

                <p v-else class="mt-3 text-sm text-muted-foreground">
                    {{ $t('Every shipment is assigned.') }}
                </p>
            </aside>
        </div>
    </div>
</template>
