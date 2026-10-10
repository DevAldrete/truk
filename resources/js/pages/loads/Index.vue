<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Layers } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import LoadDetail from '@/components/catalog/LoadDetail.vue';
import LoadFormSheet from '@/components/catalog/LoadFormSheet.vue';
import MasterDetailPage from '@/components/catalog/MasterDetailPage.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFilteredList } from '@/composables/useFilteredList';
import { t } from '@/lib/i18n';
import { index, show } from '@/routes/loads';
import type {
    Load,
    LoadDetail as LoadDetailType,
    Option,
    Paginated,
    Team,
} from '@/types';

const props = defineProps<{
    loads: Paginated<Load>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
    availableShipments: Option[];
    load?: LoadDetailType;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.load
        ? show.url({ current_team: teamSlug.value, load: props.load.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['loads', 'filters'],
    filters: { search: props.filters.search, status: props.filters.status },
});

const term = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: t('Loads'),
                href: pageProps.currentTeam
                    ? index.url({ current_team: pageProps.currentTeam.slug })
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="$t('Loads')" />

    <MasterDetailPage
        :selected="!!load"
        :back-href="index.url({ current_team: teamSlug })"
    >
        <template #list>
            <CatalogListLayout
                v-model="term"
                :title="$t('Loads')"
                :subtitle="$t(':count records', { count: loads.total })"
                :description="$t('Groups of shipments planned together.')"
                :paginator="loads"
                :placeholder="$t('Search by number')"
                search-test="load-search"
                @search="list.search"
            >
                <template #actions>
                    <LoadFormSheet v-if="can.manage" :team-slug="teamSlug" />
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
                            data-test="load-status-filter"
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

                <li v-for="item in loads.data" :key="item.id">
                    <Link
                        :href="show({ current_team: teamSlug, load: item.id })"
                        :class="[
                            'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                            load?.id === item.id
                                ? 'bg-accent'
                                : 'hover:bg-accent/40',
                        ]"
                        data-test="load-row"
                    >
                        <Layers class="mt-0.5 size-4 shrink-0 opacity-50" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ item.number }}
                            </p>
                            <p
                                class="truncate text-[11px] text-muted-foreground"
                            >
                                {{ item.status_label }} ·
                                {{ item.shipments_count }}
                                {{ $t('shipments') }}
                            </p>
                        </div>
                    </Link>
                </li>

                <template #empty>
                    {{ $t('No loads match the filter.') }}
                </template>
            </CatalogListLayout>
        </template>

        <template #detail>
            <LoadDetail
                v-if="load"
                :key="load.id"
                :load="load"
                :team-slug="teamSlug"
                :available-shipments="availableShipments"
                :statuses="statuses"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a load from the list or press ⌘K to search.') }}
            </div>
        </template>
    </MasterDetailPage>
</template>
