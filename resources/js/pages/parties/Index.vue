<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import PartyDetail from '@/components/catalog/PartyDetail.vue';
import PartyFormSheet from '@/components/catalog/PartyFormSheet.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
        <section
            class="flex w-full min-w-0 flex-col border-r lg:w-[23rem] lg:shrink-0"
        >
            <header
                class="flex items-center justify-between gap-2 border-b px-4 py-3"
            >
                <div>
                    <h1 class="text-sm font-semibold">{{ $t('Parties') }}</h1>
                    <p class="text-xs text-muted-foreground">
                        {{ $t(':count records', { count: parties.total }) }}
                    </p>
                </div>

                <PartyFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :types="types"
                />
            </header>

            <div class="space-y-2 border-b p-3">
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 opacity-50"
                    />
                    <Input
                        v-model="term"
                        class="pl-8"
                        :placeholder="$t('Search by name or RFC')"
                        data-test="party-search"
                        @input="list.search(term)"
                    />
                </div>

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
            </div>

            <ul class="min-h-0 flex-1 overflow-y-auto">
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
                                    item.contacts_count > 0 ||
                                    item.locations_count > 0
                                "
                                class="truncate text-[11px] text-muted-foreground"
                            >
                                {{
                                    $t(
                                        ':contacts contacts · :locations sites',
                                        {
                                            contacts: item.contacts_count,
                                            locations: item.locations_count,
                                        },
                                    )
                                }}
                            </p>
                        </div>
                    </Link>
                </li>

                <li
                    v-if="parties.data.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    {{ $t('No parties match the filter.') }}
                </li>
            </ul>

            <footer
                v-if="parties.last_page > 1"
                class="flex items-center justify-between border-t px-3 py-2 text-xs text-muted-foreground"
            >
                <span>
                    {{ parties.from }}–{{ parties.to }} / {{ parties.total }}
                </span>
                <div class="flex gap-1">
                    <Button
                        v-for="link in parties.links.filter(
                            (l) =>
                                l.label === '&laquo; Previous' ||
                                l.label === 'Next &raquo;',
                        )"
                        :key="link.label"
                        as-child
                        variant="ghost"
                        size="sm"
                        :disabled="!link.url"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                        >
                            {{ link.label.includes('Previous') ? '‹' : '›' }}
                        </Link>
                        <span v-else>
                            {{ link.label.includes('Previous') ? '‹' : '›' }}
                        </span>
                    </Button>
                </div>
            </footer>
        </section>

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
