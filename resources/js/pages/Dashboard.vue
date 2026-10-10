<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarClock,
    Clock,
    FileWarning,
    IdCard,
    Package,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { t } from '@/lib/i18n';
import { dashboard, dispatch } from '@/routes';
import { index as incidentsIndex } from '@/routes/incidents';
import type { DashboardInvitation, DashboardOperations, Team } from '@/types';

const props = defineProps<{
    pendingInvitations?: DashboardInvitation[];
    operations: DashboardOperations;
    fleetWarnings: {
        expired_licenses: number;
        expired_documents: number;
        expiring_documents: number;
    };
}>();

const page = usePage();

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const totalWarnings =
    props.fleetWarnings.expired_licenses +
    props.fleetWarnings.expired_documents +
    props.fleetWarnings.expiring_documents;

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: t('Dashboard'),
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="$t('Dashboard')" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <section
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold">{{ $t('Operations today') }}</h2>

            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="dispatch({ current_team: teamSlug })"
                    class="rounded-lg border p-4 transition-colors hover:bg-accent/40"
                >
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <CalendarClock class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Dispatching today') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ operations.dispatching_today }}
                    </p>
                </Link>

                <Link
                    :href="dispatch({ current_team: teamSlug })"
                    class="rounded-lg border p-4 transition-colors hover:bg-accent/40"
                >
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Clock class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Delayed stops') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ operations.delayed_stops }}
                    </p>
                </Link>

                <Link
                    :href="
                        incidentsIndex(
                            { current_team: teamSlug },
                            { query: { status: 'open' } },
                        )
                    "
                    class="rounded-lg border p-4 transition-colors hover:bg-accent/40"
                >
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <TriangleAlert class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Open incidents') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ operations.open_incidents }}
                    </p>
                </Link>

                <Link
                    :href="dispatch({ current_team: teamSlug })"
                    class="rounded-lg border p-4 transition-colors hover:bg-accent/40"
                >
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Package class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Unassigned shipments') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ operations.unassigned_shipments }}
                    </p>
                </Link>
            </div>
        </section>

        <section
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold">{{ $t('Fleet warnings') }}</h2>

            <p
                v-if="totalWarnings === 0"
                class="mt-3 text-sm text-muted-foreground"
            >
                {{ $t('No fleet warnings today.') }}
            </p>

            <div v-else class="mt-3 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <IdCard class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Expired licences') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ fleetWarnings.expired_licenses }}
                    </p>
                </div>

                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <FileWarning class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Expired documents') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ fleetWarnings.expired_documents }}
                    </p>
                </div>

                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <AlertTriangle class="size-4" />
                        <span class="text-xs font-medium">
                            {{ $t('Expiring soon') }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ fleetWarnings.expiring_documents }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
