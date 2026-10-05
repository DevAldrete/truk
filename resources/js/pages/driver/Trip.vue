<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarClock, Route, Truck } from '@lucide/vue';
import { computed } from 'vue';
import ExpenseSheet from '@/components/driver/ExpenseSheet.vue';
import IncidentSheet from '@/components/driver/IncidentSheet.vue';
import StopExecutionCard from '@/components/driver/StopExecutionCard.vue';
import type { DriverOptions, DriverTripDetail } from '@/types';

const props = defineProps<{
    trip: DriverTripDetail;
    options: DriverOptions;
}>();

const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const money = (minor: number, currency: string) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(minor / 100);
</script>

<template>
    <Head :title="trip.number" />

    <div class="grid gap-4">
        <section class="rounded-xl border bg-background p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold">
                        {{ trip.number }}
                    </h1>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ trip.driver_name }}
                        <template v-if="trip.vehicle_name">
                            · {{ trip.vehicle_name }}
                            <template v-if="trip.vehicle_plate">
                                ({{ trip.vehicle_plate }})
                            </template>
                        </template>
                    </p>
                    <p
                        v-if="trip.planned_start_at"
                        class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <CalendarClock class="size-3.5" />
                        {{ trip.planned_start_at }}
                    </p>
                    <p
                        v-if="trip.trailer_name"
                        class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <Truck class="size-3.5" />
                        {{ trip.trailer_name }}
                    </p>
                </div>

                <span
                    class="shrink-0 rounded-full bg-muted px-2 py-1 text-[11px] font-medium"
                >
                    {{ trip.status_label }}
                </span>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <IncidentSheet
                    :team-slug="teamSlug"
                    :trip-id="trip.id"
                    :incident-types="options.incidentTypes"
                    :incident-severities="options.incidentSeverities"
                />
                <ExpenseSheet
                    :team-slug="teamSlug"
                    :trip-id="trip.id"
                    :expense-types="options.expenseTypes"
                />
            </div>
        </section>

        <section v-if="trip.incidents.length > 0" class="grid gap-2">
            <h2 class="text-sm font-semibold">{{ $t('Incidents') }}</h2>
            <div
                v-for="incident in trip.incidents"
                :key="incident.id"
                class="rounded-xl border bg-background p-3 text-xs"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium">{{ incident.type_label }}</span>
                    <span class="text-muted-foreground">
                        {{ incident.severity_label }} ·
                        {{ incident.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-muted-foreground">
                    {{ incident.description }}
                </p>
            </div>
        </section>

        <section class="grid gap-3">
            <h2 class="flex items-center gap-2 text-sm font-semibold">
                <Route class="size-4" />
                {{ $t('Stops') }}
            </h2>

            <StopExecutionCard
                v-for="stop in trip.stops"
                :key="stop.id"
                :team-slug="teamSlug"
                :trip-id="trip.id"
                :stop="stop"
                :options="options"
            />

            <p
                v-if="trip.stops.length === 0"
                class="rounded-xl border bg-background p-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('This trip has no stops yet.') }}
            </p>
        </section>

        <section v-if="trip.expenses.length > 0" class="grid gap-2">
            <h2 class="text-sm font-semibold">{{ $t('Expenses') }}</h2>
            <ul class="divide-y rounded-xl border bg-background">
                <li
                    v-for="expense in trip.expenses"
                    :key="expense.id"
                    class="flex items-center justify-between px-3 py-2 text-xs"
                >
                    <span>
                        {{ expense.type_label }}
                        <template v-if="expense.vendor">
                            · {{ expense.vendor }}
                        </template>
                    </span>
                    <span class="font-medium">
                        {{ money(expense.amount_minor, expense.currency) }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
