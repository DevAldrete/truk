<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, FileWarning, IdCard } from '@lucide/vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { t } from '@/lib/i18n';
import { dashboard } from '@/routes';
import type { DashboardInvitation, Team } from '@/types';

const props = defineProps<{
    pendingInvitations?: DashboardInvitation[];
    fleetWarnings: {
        expired_licenses: number;
        expired_documents: number;
        expiring_documents: number;
    };
}>();

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
        <section class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
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
