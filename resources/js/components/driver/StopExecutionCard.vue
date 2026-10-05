<script setup lang="ts">
import { CheckCircle2, MapPin, Package } from '@lucide/vue';
import DeliveryAttemptSheet from '@/components/driver/DeliveryAttemptSheet.vue';
import ExpenseSheet from '@/components/driver/ExpenseSheet.vue';
import IncidentSheet from '@/components/driver/IncidentSheet.vue';
import PodSheet from '@/components/driver/PodSheet.vue';
import ScanSheet from '@/components/driver/ScanSheet.vue';
import type { DriverOptions, DriverStop } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stop: DriverStop;
    options: DriverOptions;
}>();

const statusClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400';
        case 'failed':
            return 'bg-destructive/15 text-destructive';
        case 'arrived':
            return 'bg-blue-500/15 text-blue-700 dark:text-blue-400';
        case 'skipped':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const hasPackages = (index: number) =>
    (props.stop.shipments[index]?.packages.length ?? 0) > 0;
</script>

<template>
    <article class="rounded-xl border bg-background">
        <header class="flex items-start gap-3 border-b p-4">
            <span
                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold"
            >
                {{ stop.sequence }}
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold">
                        {{ stop.type_label }}
                    </span>
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                        :class="statusClass(stop.status)"
                    >
                        {{ stop.status_label }}
                    </span>
                </div>

                <p
                    v-if="stop.location_name || stop.location_snapshot"
                    class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <MapPin class="size-3.5" />
                    {{ stop.location_name ?? stop.location_snapshot?.name }}
                    <template v-if="stop.location_snapshot?.city">
                        · {{ stop.location_snapshot.city }}
                    </template>
                </p>

                <p
                    v-if="stop.planned_at"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    {{ stop.planned_at }}
                </p>
            </div>
        </header>

        <div class="grid gap-3 p-4">
            <div
                v-for="(shipment, index) in stop.shipments"
                :key="shipment.id"
                class="rounded-lg border p-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="truncate text-sm font-medium">
                        {{ shipment.number }}
                    </p>
                    <span class="text-[11px] text-muted-foreground">
                        {{ shipment.status_label }}
                    </span>
                </div>
                <p class="truncate text-xs text-muted-foreground">
                    {{ shipment.customer_name }}
                </p>
                <p class="mt-1 text-xs">
                    {{ $t('Delivered') }} {{ shipment.delivered_quantity }} /
                    {{ shipment.pieces }}
                    <span
                        v-if="shipment.remaining_quantity > 0"
                        class="text-muted-foreground"
                    >
                        · {{ shipment.remaining_quantity }} {{ $t('left') }}
                    </span>
                </p>

                <div
                    v-if="hasPackages(index)"
                    class="mt-2 flex flex-wrap gap-1"
                >
                    <span
                        v-for="pack in shipment.packages"
                        :key="pack.id"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px]"
                        :class="
                            pack.terminal
                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <CheckCircle2 v-if="pack.terminal" class="size-3" />
                        <Package v-else class="size-3" />
                        {{ pack.code }}
                    </span>
                </div>
            </div>

            <p
                v-if="stop.shipments.length === 0"
                class="text-xs text-muted-foreground"
            >
                {{ $t('No shipments on this stop.') }}
            </p>

            <ul
                v-if="stop.attempts.length > 0"
                class="grid gap-1 border-t pt-3 text-xs text-muted-foreground"
            >
                <li
                    v-for="attempt in stop.attempts"
                    :key="attempt.id"
                    class="flex items-center justify-between gap-2"
                >
                    <span>
                        {{ attempt.outcome_label }}
                        <template v-if="attempt.failure_reason_label">
                            · {{ attempt.failure_reason_label }}
                        </template>
                    </span>
                    <span>{{ attempt.occurred_at }}</span>
                </li>
            </ul>
        </div>

        <footer class="flex flex-wrap gap-2 border-t p-3">
            <DeliveryAttemptSheet
                :team-slug="teamSlug"
                :trip-id="tripId"
                :stop="stop"
                :outcomes="options.outcomes"
                :failure-reasons="options.failureReasons"
            />
            <ScanSheet
                :team-slug="teamSlug"
                :trip-id="tripId"
                :stop="stop"
                :scan-types="options.scanTypes"
            />
            <PodSheet :team-slug="teamSlug" :trip-id="tripId" :stop="stop" />
            <IncidentSheet
                :team-slug="teamSlug"
                :trip-id="tripId"
                :stop-id="stop.id"
                :incident-types="options.incidentTypes"
                :incident-severities="options.incidentSeverities"
            />
            <ExpenseSheet
                :team-slug="teamSlug"
                :trip-id="tripId"
                :stop-id="stop.id"
                :expense-types="options.expenseTypes"
            />
        </footer>
    </article>
</template>
