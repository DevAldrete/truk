<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import PartyDetail from '@/components/catalog/PartyDetail.vue';
import PartyFormSheet from '@/components/catalog/PartyFormSheet.vue';
import { Button } from '@/components/ui/button';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/parties';
import type {
    Option,
    Paginated,
    Party,
    PartyDetail as PartyDetailType,
} from '@/types';

const props = defineProps<{
    parties: Paginated<Party>;
    filters: { search: string | null; type: string | null };
    types: Option[];
    party?: PartyDetailType;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.party
        ? show.url({ current_team: teamSlug.value, party: props.party.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['parties', 'filters'],
    filters: { search: props.filters.search, type: props.filters.type },
});

const term = ref(props.filters.search ?? '');
</script>

<template>
    <Head :title="$t('Parties')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Parties')"
            :subtitle="$t(':count records', { count: parties.total })"
            :paginator="parties"
            :placeholder="$t('Search by name or RFC')"
            search-test="party-search"
            @search="list.search"
        >
            <template #actions>
                <PartyFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :types="types"
                />
            </template>

            <template #filters>
                <div class="flex flex-wrap gap-1">
                    <Button
                        size="sm"
                        :variant="filters.type === null ? 'secondary' : 'ghost'"
                        @click="list.filter('type', null)"
                    >
                        {{ $t('All') }}
                    </Button>
                    <Button
                        v-for="type in types"
                        :key="type.value"
                        size="sm"
                        :variant="
                            filters.type === type.value ? 'secondary' : 'ghost'
                        "
                        @click="list.filter('type', type.value)"
                    >
                        {{ type.label }}
                    </Button>
                </div>
            </template>

            <li v-for="item in parties.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, party: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        party?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="party-row"
                >
                    <Building2 class="mt-0.5 size-4 shrink-0 opacity-50" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ item.name }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ item.type_label }}
                            <template v-if="item.rfc">
                                · {{ item.rfc }}
                            </template>
                        </p>
                        <p
                            v-if="
                                item.contacts_count > 0 || item.locations_count > 0
                            "
                            class="truncate text-[11px] text-muted-foreground"
                        >
                            {{
                                $t(':contacts contacts · :locations sites', {
                                    contacts: item.contacts_count,
                                    locations: item.locations_count,
                                })
                            }}
                        </p>
                    </div>
                </Link>
            </li>

            <template #empty>
                {{ $t('No parties match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <PartyDetail
                v-if="party"
                :key="party.id"
                :party="party"
                :team-slug="teamSlug"
                :types="types"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a party from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
