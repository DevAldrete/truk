<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, Container, LayoutGrid, MapPin, Truck, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { t } from '@/lib/i18n';
import { index as driversIndex } from '@/routes/drivers';
import { index as locationsIndex } from '@/routes/locations';
import { index as partiesIndex } from '@/routes/parties';
import { index as trailersIndex } from '@/routes/trailers';
import { index as vehiclesIndex } from '@/routes/vehicles';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('Dashboard'),
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
    {
        title: t('Parties'),
        href: partiesIndex.url({ current_team: teamSlug.value }),
        icon: Building2,
    },
    {
        title: t('Locations'),
        href: locationsIndex.url({ current_team: teamSlug.value }),
        icon: MapPin,
    },
]);

const fleetNavItems = computed<NavItem[]>(() => [
    {
        title: t('Drivers'),
        href: driversIndex.url({ current_team: teamSlug.value }),
        icon: Users,
    },
    {
        title: t('Vehicles'),
        href: vehiclesIndex.url({ current_team: teamSlug.value }),
        icon: Truck,
    },
    {
        title: t('Trailers'),
        href: trailersIndex.url({ current_team: teamSlug.value }),
        icon: Container,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="t('Operations')" />
            <NavMain :items="fleetNavItems" :label="t('Fleet')" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
