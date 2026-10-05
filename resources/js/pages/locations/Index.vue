<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import LocationDetail from '@/components/catalog/LocationDetail.vue';
import LocationFormSheet from '@/components/catalog/LocationFormSheet.vue';
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
        <CatalogListLayout
            v-model="term"
            :title="$t('Locations')"
            :subtitle="$t(':count sites', { count: locations.total })"
            :paginator="locations"
            :placeholder="$t('Search by name, city, or zip')"
            search-test="location-search"
            @search="list.search"
        >
            <template #actions>
                <LocationFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :parties="parties"
                />
            </template>

            <li v-for="item in locations.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, location: item.id })"
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

            <template #empty>
                {{ $t('No sites match the filter.') }}
            </template>
        </CatalogListLayout>

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
