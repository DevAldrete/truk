<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import IncidentDetail from '@/components/catalog/IncidentDetail.vue';
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
import { index, show } from '@/routes/incidents';
import type {
    Incident,
    IncidentDetail as IncidentDetailType,
    Option,
    Paginated,
    Team,
} from '@/types';

const props = defineProps<{
    incidents: Paginated<Incident>;
    filters: {
        search: string | null;
        status: string | null;
        severity: string | null;
    };
    counts: { open: number };
    statuses: Option[];
    severities: Option[];
    incident?: IncidentDetailType;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.incident
        ? show.url({
              current_team: teamSlug.value,
              incident: props.incident.id,
          })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['incidents', 'filters', 'counts'],
    filters: {
        search: props.filters.search,
        status: props.filters.status,
        severity: props.filters.severity,
    },
});

const term = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const severity = ref(props.filters.severity ?? 'all');

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: t('Incidents'),
                href: pageProps.currentTeam
                    ? index.url({ current_team: pageProps.currentTeam.slug })
                    : '/',
            },
        ],
    }),
});

const severityClass = (value: string) => {
    switch (value) {
        case 'critical':
            return 'border-red-300 text-red-700 dark:text-red-400';
        case 'high':
            return 'border-orange-300 text-orange-700 dark:text-orange-400';
        case 'medium':
            return 'border-amber-300 text-amber-700 dark:text-amber-400';
        default:
            return 'border-muted-foreground/30 text-muted-foreground';
    }
};

const ageLabel = (hours: number) => {
    return hours < 24 ? `${hours} h` : `${Math.floor(hours / 24)} d`;
};
</script>

<template>
    <Head :title="$t('Incidents')" />

    <MasterDetailPage
        :selected="!!incident"
        :back-href="index.url({ current_team: teamSlug })"
    >
        <template #list>
            <CatalogListLayout
                v-model="term"
                :title="$t('Incidents')"
                :subtitle="$t(':count open', { count: counts.open })"
                :description="
                    $t('Problems reported from the road, for office triage.')
                "
                :paginator="incidents"
                :placeholder="$t('Search descriptions')"
                search-test="incident-search"
                @search="list.search"
            >
                <template #filters>
                    <div class="grid grid-cols-2 gap-2">
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
                                data-test="incident-status-filter"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {{ $t('All statuses') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in statuses"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            :model-value="severity"
                            @update:model-value="
                                (value) => {
                                    severity = String(value);
                                    list.filter(
                                        'severity',
                                        severity === 'all' ? null : severity,
                                    );
                                }
                            "
                        >
                            <SelectTrigger
                                class="w-full"
                                data-test="incident-severity-filter"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {{ $t('All severities') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in severities"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <li v-for="item in incidents.data" :key="item.id">
                    <Link
                        :href="
                            show({ current_team: teamSlug, incident: item.id })
                        "
                        :class="[
                            'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                            incident?.id === item.id
                                ? 'bg-accent'
                                : 'hover:bg-accent/40',
                        ]"
                        data-test="incident-row"
                    >
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0 opacity-50"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium">
                                    {{ item.type_label }}
                                </p>
                                <span
                                    class="shrink-0 rounded-full border px-2 py-0.5 text-[10px]"
                                    :class="severityClass(item.severity)"
                                >
                                    {{ item.severity_label }}
                                </span>
                            </div>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ item.description }}
                            </p>
                            <p
                                class="truncate text-[11px] text-muted-foreground"
                            >
                                {{ item.status_label }}
                                <template v-if="item.trip_number">
                                    · {{ item.trip_number }}
                                </template>
                                · {{ ageLabel(item.age_hours) }}
                            </p>
                        </div>
                    </Link>
                </li>

                <template #empty>
                    {{ $t('No incidents match the filter.') }}
                </template>
            </CatalogListLayout>
        </template>

        <template #detail>
            <IncidentDetail
                v-if="incident"
                :key="incident.id"
                :incident="incident"
                :team-slug="teamSlug"
                :statuses="statuses"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick an incident from the list or press ⌘K.') }}
            </div>
        </template>
    </MasterDetailPage>
</template>
