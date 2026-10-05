<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Truck } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import VehicleDetail from '@/components/catalog/VehicleDetail.vue';
import VehicleFormSheet from '@/components/catalog/VehicleFormSheet.vue';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/vehicles';
import type { Option, Paginated, Vehicle } from '@/types';

const props = defineProps<{
    vehicles: Paginated<Vehicle>;
    filters: { search: string | null };
    carriers: Option[];
    documentTypes: Option[];
    vehicle?: Vehicle;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.vehicle
        ? show.url({ current_team: teamSlug.value, vehicle: props.vehicle.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['vehicles', 'filters'],
    filters: { search: props.filters.search },
});

const term = ref(props.filters.search ?? '');
</script>

<template>
    <Head :title="$t('Vehicles')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Vehicles')"
            :subtitle="$t(':count vehicles', { count: vehicles.total })"
            :paginator="vehicles"
            :placeholder="$t('Search by name, plate, or type')"
            search-test="vehicle-search"
            @search="list.search"
        >
            <template #actions>
                <VehicleFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :carriers="carriers"
                />
            </template>

            <li v-for="item in vehicles.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, vehicle: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        vehicle?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="vehicle-row"
                >
                    <Truck class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.name }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ item.plate }} · {{ item.configuration }}
                        </p>
                        <p
                            v-if="item.carrier_name"
                            class="truncate text-[11px] text-muted-foreground"
                        >
                            {{ item.carrier_name }}
                        </p>
                    </div>
                    <span
                        v-if="item.has_expired_documents"
                        class="shrink-0 rounded bg-destructive/10 px-1.5 py-0.5 text-[10px] font-medium text-destructive"
                    >
                        {{ $t('Expired') }}
                    </span>
                </Link>
            </li>

            <template #empty>
                {{ $t('No vehicles match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <VehicleDetail
                v-if="vehicle"
                :key="vehicle.id"
                :vehicle="vehicle"
                :team-slug="teamSlug"
                :carriers="carriers"
                :document-types="documentTypes"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a vehicle from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
