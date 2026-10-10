<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarClock, ChevronRight, MapPin, Truck } from '@lucide/vue';
import { computed } from 'vue';
import { formatDateTime } from '@/lib/datetime';
import { show as tripShow } from '@/routes/driver/trips';
import type { DriverTripSummary } from '@/types';

defineProps<{
    trips: DriverTripSummary[];
    driver: { id: number; name: string } | null;
}>();

const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
</script>

<template>
    <Head :title="$t('Driver portal')" />

    <div class="grid gap-4">
        <div>
            <h1 class="text-xl font-semibold">{{ $t('My trips') }}</h1>
            <p v-if="driver" class="mt-1 text-sm text-muted-foreground">
                {{ driver.name }}
            </p>
        </div>

        <p
            v-if="trips.length === 0"
            class="rounded-xl border bg-background p-6 text-center text-sm text-muted-foreground"
        >
            {{ $t('You have no open trips assigned.') }}
        </p>

        <ul v-else class="grid gap-3">
            <li v-for="trip in trips" :key="trip.id">
                <Link
                    :href="tripShow({ current_team: teamSlug, trip: trip.id })"
                    class="flex items-center gap-3 rounded-xl border bg-background p-4 transition-colors hover:bg-accent/40"
                    data-test="driver-trip-row"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-base font-semibold">
                            {{ trip.number }}
                        </p>
                        <p
                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <MapPin class="size-3.5" />
                            {{
                                $t(':count stops', { count: trip.stops_count })
                            }}
                        </p>
                        <p
                            v-if="trip.planned_start_at"
                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <CalendarClock class="size-3.5" />
                            {{ formatDateTime(trip.planned_start_at) }}
                        </p>
                        <p
                            v-if="trip.vehicle_name"
                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Truck class="size-3.5" />
                            {{ trip.vehicle_name }}
                            <template v-if="trip.trailer_name">
                                · {{ trip.trailer_name }}
                            </template>
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full bg-muted px-2 py-1 text-[11px] font-medium"
                    >
                        {{ trip.status_label }}
                    </span>

                    <ChevronRight class="size-5 shrink-0 opacity-40" />
                </Link>
            </li>
        </ul>
    </div>
</template>
