<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Truck } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import ShipmentDetail from '@/components/catalog/ShipmentDetail.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/shipments';
import type {
    Option,
    Paginated,
    Shipment,
    ShipmentDetail as ShipmentDetailType,
} from '@/types';

const props = defineProps<{
    shipments: Paginated<Shipment>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
    locations: Option[];
    shipment?: ShipmentDetailType;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.shipment
        ? show.url({
              current_team: teamSlug.value,
              shipment: props.shipment.id,
          })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['shipments', 'filters'],
    filters: { search: props.filters.search, status: props.filters.status },
});

const term = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
</script>

<template>
    <Head :title="$t('Shipments')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Shipments')"
            :subtitle="$t(':count records', { count: shipments.total })"
            :description="
                $t('Goods moving from a pickup site to a delivery site.')
            "
            :paginator="shipments"
            :placeholder="$t('Search by number or customer')"
            search-test="shipment-search"
            @search="list.search"
        >
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
                    <SelectTrigger
                        class="w-full"
                        data-test="shipment-status-filter"
                    >
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

            <li v-for="item in shipments.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, shipment: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        shipment?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="shipment-row"
                >
                    <Truck class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.number }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ item.customer_name ?? $t('No customer') }}
                        </p>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{ item.status_label }} · {{ item.pieces }}
                            {{ $t('pcs') }} · {{ item.packages_count }}
                            {{ $t('packages') }}
                        </p>
                    </div>
                </Link>
            </li>

            <template #empty>
                {{ $t('No shipments match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <ShipmentDetail
                v-if="shipment"
                :key="shipment.id"
                :shipment="shipment"
                :team-slug="teamSlug"
                :locations="locations"
                :statuses="statuses"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a shipment from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
