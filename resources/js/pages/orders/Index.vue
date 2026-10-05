<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import OrderDetail from '@/components/catalog/OrderDetail.vue';
import OrderFormSheet from '@/components/catalog/OrderFormSheet.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/orders';
import type {
    Option,
    Order,
    OrderDetail as OrderDetailType,
    Paginated,
} from '@/types';

const props = defineProps<{
    orders: Paginated<Order>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
    customers: Option[];
    locations: Option[];
    order?: OrderDetailType;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.order
        ? show.url({ current_team: teamSlug.value, order: props.order.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['orders', 'filters'],
    filters: { search: props.filters.search, status: props.filters.status },
});

const term = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
</script>

<template>
    <Head :title="$t('Orders')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Orders')"
            :subtitle="$t(':count records', { count: orders.total })"
            :paginator="orders"
            :placeholder="$t('Search by number or customer')"
            search-test="order-search"
            @search="list.search"
        >
            <template #actions>
                <OrderFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :customers="customers"
                />
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
                    <SelectTrigger
                        class="w-full"
                        data-test="order-status-filter"
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

            <li v-for="item in orders.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, order: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        order?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="order-row"
                >
                    <ClipboardList class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.number }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ item.customer_name ?? $t('No customer') }}
                        </p>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{ item.status_label }} · {{ item.items_count }}
                            {{ $t('lines') }} · {{ item.shipments_count }}
                            {{ $t('shipments') }}
                        </p>
                    </div>
                </Link>
            </li>

            <template #empty>
                {{ $t('No orders match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <OrderDetail
                v-if="order"
                :key="order.id"
                :order="order"
                :team-slug="teamSlug"
                :customers="customers"
                :locations="locations"
                :statuses="statuses"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick an order from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
