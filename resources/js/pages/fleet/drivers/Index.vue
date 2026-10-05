<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import DriverDetail from '@/components/catalog/DriverDetail.vue';
import DriverFormSheet from '@/components/catalog/DriverFormSheet.vue';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/drivers';
import type { Driver, Option, Paginated } from '@/types';

const props = defineProps<{
    drivers: Paginated<Driver>;
    filters: { search: string | null };
    carriers: Option[];
    members: Option[];
    documentTypes: Option[];
    driver?: Driver;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.driver
        ? show.url({ current_team: teamSlug.value, driver: props.driver.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['drivers', 'filters'],
    filters: { search: props.filters.search },
});

const term = ref(props.filters.search ?? '');
</script>

<template>
    <Head :title="$t('Drivers')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Drivers')"
            :subtitle="$t(':count drivers', { count: drivers.total })"
            :paginator="drivers"
            :placeholder="$t('Search by name, phone, or licence')"
            search-test="driver-search"
            @search="list.search"
        >
            <template #actions>
                <DriverFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :carriers="carriers"
                    :members="members"
                />
            </template>

            <li v-for="item in drivers.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, driver: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        driver?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="driver-row"
                >
                    <Users class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.name }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ item.phone }}
                            <template v-if="item.license_number">
                                · {{ item.license_number }}
                            </template>
                        </p>
                        <p
                            v-if="item.carrier_name"
                            class="truncate text-[11px] text-muted-foreground"
                        >
                            {{ item.carrier_name }}
                        </p>
                    </div>
                    <span
                        v-if="item.license_expired"
                        class="shrink-0 rounded bg-destructive/10 px-1.5 py-0.5 text-[10px] font-medium text-destructive"
                    >
                        {{ $t('Expired') }}
                    </span>
                    <span
                        v-else-if="item.has_expired_documents"
                        class="shrink-0 rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-medium text-amber-600"
                    >
                        {{ $t('Expires soon') }}
                    </span>
                </Link>
            </li>

            <template #empty>
                {{ $t('No drivers match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <DriverDetail
                v-if="driver"
                :key="driver.id"
                :driver="driver"
                :team-slug="teamSlug"
                :carriers="carriers"
                :members="members"
                :document-types="documentTypes"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a driver from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
