<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Route } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import TripDetail from '@/components/catalog/TripDetail.vue';
import TripFormSheet from '@/components/catalog/TripFormSheet.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/trips';
import type {
    Option,
    Paginated,
    Trip,
    TripDetail as TripDetailType,
} from '@/types';

const props = defineProps<{
    trips: Paginated<Trip>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
    drivers: Option[];
    vehicles: Option[];
    trailers: Option[];
    locations: Option[];
    shipments: Option[];
    stopTypes: Option[];
    stopStatuses: Option[];
    trip?: TripDetailType;
    can: { manage: boolean; overrideCapacity: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.trip
        ? show.url({ current_team: teamSlug.value, trip: props.trip.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['trips', 'filters'],
    filters: { search: props.filters.search, status: props.filters.status },
});

const term = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
</script>

<template>
    <Head :title="$t('Trips')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Trips')"
            :subtitle="$t(':count records', { count: trips.total })"
            :paginator="trips"
            :placeholder="$t('Search by number')"
            search-test="trip-search"
            @search="list.search"
        >
            <template #actions>
                <TripFormSheet v-if="can.manage" :team-slug="teamSlug" />
            </template>

            <template #filters>
                <Select
                    :model-value="status"
                    @update:model-value="
                        (value) => {
                            status = String(value);
                            list.filter(
                                'status',
                                status === 'all' ? null : status,
                            );
                        }
                    "
                >
                    <SelectTrigger class="w-full" data-test="trip-status-filter">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{{ $t('All') }}</SelectItem>
                        <SelectItem
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>

            <li v-for="item in trips.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, trip: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        trip?.id === item.id ? 'bg-accent' : 'hover:bg-accent/40',
                    ]"
                    data-test="trip-row"
                >
                    <Route class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.number }}
                        </p>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{ item.status_label }} ·
                            {{ item.driver_name ?? $t('No driver') }}
                            <template v-if="item.vehicle_name">
                                · {{ item.vehicle_name }}
                            </template>
                        </p>
                    </div>
                </Link>
            </li>

            <template #empty>
                {{ $t('No trips match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <TripDetail
                v-if="trip"
                :key="trip.id"
                :trip="trip"
                :team-slug="teamSlug"
                :drivers="drivers"
                :vehicles="vehicles"
                :trailers="trailers"
                :locations="locations"
                :shipments="shipments"
                :statuses="statuses"
                :stop-types="stopTypes"
                :stop-statuses="stopStatuses"
                :can-manage="can.manage"
                :can-override="can.overrideCapacity"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a trip from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
