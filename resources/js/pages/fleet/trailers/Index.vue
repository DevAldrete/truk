<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Container } from '@lucide/vue';
import { computed, ref } from 'vue';
import CatalogListLayout from '@/components/catalog/CatalogListLayout.vue';
import TrailerDetail from '@/components/catalog/TrailerDetail.vue';
import TrailerFormSheet from '@/components/catalog/TrailerFormSheet.vue';
import { useFilteredList } from '@/composables/useFilteredList';
import { index, show } from '@/routes/trailers';
import type { Option, Paginated, Trailer } from '@/types';

const props = defineProps<{
    trailers: Paginated<Trailer>;
    filters: { search: string | null };
    carriers: Option[];
    documentTypes: Option[];
    trailer?: Trailer;
    can: { manage: boolean };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const listUrl = computed(() =>
    props.trailer
        ? show.url({ current_team: teamSlug.value, trailer: props.trailer.id })
        : index.url({ current_team: teamSlug.value }),
);

const list = useFilteredList({
    url: () => listUrl.value,
    only: ['trailers', 'filters'],
    filters: { search: props.filters.search },
});

const term = ref(props.filters.search ?? '');
</script>

<template>
    <Head :title="$t('Trailers')" />

    <div class="flex h-full min-h-0">
        <CatalogListLayout
            v-model="term"
            :title="$t('Trailers')"
            :subtitle="$t(':count trailers', { count: trailers.total })"
            :description="$t('The trailers attached to your vehicles.')"
            :paginator="trailers"
            :placeholder="$t('Search by name, plate, or type')"
            search-test="trailer-search"
            @search="list.search"
        >
            <template #actions>
                <TrailerFormSheet
                    v-if="can.manage"
                    :team-slug="teamSlug"
                    :carriers="carriers"
                />
            </template>

            <li v-for="item in trailers.data" :key="item.id">
                <Link
                    :href="show({ current_team: teamSlug, trailer: item.id })"
                    :class="[
                        'flex items-start gap-3 border-b px-4 py-3 transition-colors',
                        trailer?.id === item.id
                            ? 'bg-accent'
                            : 'hover:bg-accent/40',
                    ]"
                    data-test="trailer-row"
                >
                    <Container class="mt-0.5 size-4 shrink-0 opacity-50" />
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
                {{ $t('No trailers match the filter.') }}
            </template>
        </CatalogListLayout>

        <section class="hidden min-h-0 flex-1 overflow-y-auto lg:block">
            <TrailerDetail
                v-if="trailer"
                :key="trailer.id"
                :trailer="trailer"
                :team-slug="teamSlug"
                :carriers="carriers"
                :document-types="documentTypes"
                :can-manage="can.manage"
            />

            <div
                v-else
                class="flex h-full items-center justify-center p-8 text-center text-sm text-muted-foreground"
            >
                {{ $t('Pick a trailer from the list or press ⌘K to search.') }}
            </div>
        </section>
    </div>
</template>
