<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Building2,
    ClipboardList,
    Layers,
    LayoutGrid,
    MapPin,
    Package,
    Route,
    Search,
    Settings,
    Truck,
    Users,
    Waypoints,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { dashboard, dispatch, search as searchRoute } from '@/routes';
import { index as driversIndex } from '@/routes/drivers';
import { index as loadsIndex } from '@/routes/loads';
import { index as locationsIndex } from '@/routes/locations';
import { index as ordersIndex } from '@/routes/orders';
import { index as partiesIndex } from '@/routes/parties';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as tripsIndex } from '@/routes/trips';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { SearchResult } from '@/types';

const props = defineProps<{
    teamSlug: string;
}>();

type Item = {
    key: string;
    label: string;
    hint?: string;
    icon: typeof Search;
    url?: string;
};

const open = ref(false);
const term = ref('');
const activeIndex = ref(0);
const loading = ref(false);
const results = ref<SearchResult[]>([]);

const commands = computed<Item[]>(() => [
    {
        key: 'dashboard',
        label: t('Dashboard'),
        icon: LayoutGrid,
        url: dashboard.url({ current_team: props.teamSlug }),
    },
    {
        key: 'dispatch',
        label: t('Dispatch'),
        icon: Waypoints,
        url: dispatch.url({ current_team: props.teamSlug }),
    },
    {
        key: 'parties',
        label: t('Parties'),
        icon: Building2,
        url: partiesIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'locations',
        label: t('Locations'),
        icon: MapPin,
        url: locationsIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'orders',
        label: t('Orders'),
        icon: ClipboardList,
        url: ordersIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'shipments',
        label: t('Shipments'),
        icon: Package,
        url: shipmentsIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'loads',
        label: t('Loads'),
        icon: Layers,
        url: loadsIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'trips',
        label: t('Trips'),
        icon: Route,
        url: tripsIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'drivers',
        label: t('Drivers'),
        icon: Users,
        url: driversIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'vehicles',
        label: t('Vehicles'),
        icon: Truck,
        url: vehiclesIndex.url({ current_team: props.teamSlug }),
    },
    {
        key: 'settings',
        label: t('Settings'),
        icon: Settings,
        url: '/settings/profile',
    },
]);

const items = computed<Item[]>(() => {
    const needle = term.value.trim().toLowerCase();

    const matches =
        needle === ''
            ? commands.value
            : commands.value.filter((command) =>
                  command.label.toLowerCase().includes(needle),
              );

    return [
        ...matches,
        ...results.value.map((result) => ({
            key: `${result.type}-${result.url}`,
            label: result.title,
            hint: result.subtitle,
            icon:
                result.type === 'party'
                    ? Building2
                    : result.type === 'location'
                      ? MapPin
                      : result.type === 'driver'
                        ? Users
                        : result.type === 'order'
                          ? ClipboardList
                          : result.type === 'shipment'
                            ? Package
                            : result.type === 'load'
                              ? Layers
                              : result.type === 'trip'
                                ? Route
                                : Truck,
            url: result.url,
        })),
    ];
});

let controller: AbortController | undefined;
let timer: ReturnType<typeof setTimeout> | undefined;

const search = async (value: string) => {
    controller?.abort();

    if (value.trim().length < 2) {
        results.value = [];

        return;
    }

    controller = new AbortController();
    loading.value = true;

    try {
        const response = await fetch(
            searchRoute.url(
                { current_team: props.teamSlug },
                { query: { q: value } },
            ),
            {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            },
        );

        results.value = response.ok
            ? ((await response.json()).results ?? [])
            : [];
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
};

watch(term, (value) => {
    clearTimeout(timer);
    activeIndex.value = 0;
    timer = setTimeout(() => void search(value), 200);
});

watch(open, (isOpen) => {
    if (!isOpen) {
        term.value = '';
        results.value = [];
        activeIndex.value = 0;
    }
});

const go = (item?: Item) => {
    const target = item ?? items.value[activeIndex.value];

    if (!target?.url) {
        return;
    }

    open.value = false;
    router.visit(target.url);
};

const move = (delta: number) => {
    const total = items.value.length;

    if (total === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + delta + total) % total;
};

const onKeydown = (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = true;

        return;
    }

    if (!open.value) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        go();
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    controller?.abort();
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="top-[12%] max-w-xl translate-y-0 gap-0 overflow-hidden p-0"
            :show-close-button="false"
        >
            <DialogTitle class="sr-only">{{ $t('Search') }}</DialogTitle>
            <DialogDescription class="sr-only">
                {{ $t('Search parties, sites, orders, shipments, and fleet') }}
            </DialogDescription>

            <div class="flex items-center gap-2 border-b px-3">
                <Search class="size-4 shrink-0 opacity-50" />
                <Input
                    v-model="term"
                    :placeholder="$t('Search or jump to…')"
                    class="h-11 border-0 bg-transparent shadow-none focus-visible:ring-0"
                    data-test="command-input"
                />
                <kbd
                    class="hidden rounded border px-1.5 py-0.5 text-[10px] text-muted-foreground sm:block"
                >
                    esc
                </kbd>
            </div>

            <div class="max-h-80 overflow-y-auto p-1.5">
                <p
                    v-if="items.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ $t('No results') }}
                </p>

                <button
                    v-for="(item, index) in items"
                    :key="item.key"
                    type="button"
                    :class="[
                        'flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm',
                        index === activeIndex
                            ? 'bg-accent text-accent-foreground'
                            : 'hover:bg-accent/50',
                    ]"
                    data-test="command-item"
                    @mouseenter="activeIndex = index"
                    @click="go(item)"
                >
                    <component :is="item.icon" class="size-4 opacity-60" />
                    <span class="flex-1 truncate">{{ item.label }}</span>
                    <span
                        v-if="item.hint"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ item.hint }}
                    </span>
                </button>
            </div>

            <div
                class="flex items-center justify-between border-t px-3 py-2 text-[11px] text-muted-foreground"
            >
                <span>{{ $t('↑↓ to move · ↵ to open') }}</span>
                <span v-if="loading">{{ $t('Searching…') }}</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
