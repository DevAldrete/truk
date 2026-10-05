<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { MapPin, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import LocationDetail from '@/components/catalog/LocationDetail.vue';
import LocationFormSheet from '@/components/catalog/LocationFormSheet.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/locations';
import type { Location, Option, Paginated } from '@/types';

const props = defineProps<{
    locations: Paginated<Location>;
    filters: { search: string | null };
    parties: Option[];
    location?: Location;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.location
        ? show.url({
              current_team: teamSlug.value,
              location: props.location.id,
          })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['locations', 'filters'],
    filters: { search: props.filters.search },
});

const term = ref(props.filters.search ?? '');
</script>

<template>
    <Head :title="$t('Locations')" />

    <div class="flex h-full min-h-0">
        <section
            class="flex w-full min-w-0 flex-col border-r lg:w-[23rem] lg:shrink-0"
        >
            <header
                class="flex items-center justify-between gap-2 border-b px-4 py-3"
            >
                <div>
                    <h1 class="text-sm font-semibold">{{ $t('Locations') }}</h1>
                    <p class="text-xs text-muted-foreground">
                        {{ $t(':count sites', { count: locations.total }) }}
                    </p>
                </div>

                <LocationFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :parties="parties"
                />
            </header>

            <div class="border-b p-3">
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 opacity-50"
                    />
                    <Input
                        v-model="term"
                        class="pl-8"
                        :placeholder="$t('Search by name, city, or zip')"
                        data-test="location-search"
                        @input="list.search(term)"
                    />
                </div>
            </div>

            <ul class="min-h-0 flex-1 overflow-y-auto">
                <li v-for="item in locations.data" :key="item.id">
                    <Link
                        :href="
                            show({ current_team: teamSlug, location: item.id })
                        "
                        :class="[
                            'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                            location?.id === item.id
                                ? 'bg-accent'
                                : 'hover:bg-accent/40',
                        ]"
                        data-test="location-row"
                    >
                        <MapPin class="mt-0.5 size-4 shrink-0 opacity-50" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ item.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ item.street }}
                                <template v-if="item.exterior_number">
                                    {{ item.exterior_number }}
                                </template>
                                · {{ item.city }}
                            </p>
                            <p
                                v-if="item.party_name"
                                class="truncate text-[11px] text-muted-foreground"
                            >
                                {{ item.party_name }}
                            </p>
                        </div>
                    </Link>
                </li>

                <li
                    v-if="locations.data.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    {{ $t('No sites match the filter.') }}
                </li>
            </ul>

            <footer
                v-if="locations.last_page > 1"
                class="flex items-center justify-between border-t px-3 py-2 text-xs text-muted-foreground"
            >
                <span>
                    {{ locations.from }}–{{ locations.to }} /
                    {{ locations.total }}
                </span>
                <div class="flex gap-1">
                    <Button
                        v-for="link in locations.links.filter(
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
            <LocationDetail
                v-if="location"
                :key="location.id"
                :location="location"
                :team-slug="teamSlug"
                :parties="parties"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a site from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
