<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    ClipboardList,
    Container,
    Layers,
    LayoutGrid,
    MapPin,
    Navigation,
    Package,
    Route,
    Truck,
    Users,
    Waypoints,
} from '@lucide/vue';
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
import { index as driverIndex } from '@/routes/driver';
import { index as driversIndex } from '@/routes/drivers';
import { index as loadsIndex } from '@/routes/loads';
import { index as locationsIndex } from '@/routes/locations';
import { index as ordersIndex } from '@/routes/orders';
import { index as partiesIndex } from '@/routes/parties';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as trailersIndex } from '@/routes/trailers';
import { index as tripsIndex } from '@/routes/trips';
import { index as vehiclesIndex } from '@/routes/vehicles';
import { dashboard, dispatch, help } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const dispatchUrl = computed(() =>
    page.props.currentTeam ? dispatch(page.props.currentTeam.slug).url : '/',
);

const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const driverUrl = computed(() =>
    page.props.currentTeam
        ? driverIndex.url({ current_team: teamSlug.value })
        : '/',
);

const canDrive = computed(() =>
    ['owner', 'admin', 'dispatcher', 'driver'].includes(
        page.props.currentTeam?.role ?? '',
    ),
);

const isDriver = computed(() => page.props.currentTeam?.role === 'driver');

const mainNavItems = computed<NavItem[]>(() => {
    if (isDriver.value) {
        return [
            {
                title: t('Driver portal'),
                href: driverUrl.value,
                icon: Navigation,
            },
            {
                title: t('Help'),
                href: help.url({ current_team: teamSlug.value }),
                icon: BookOpen,
            },
        ];
    }

    return [
        {
            title: t('Dashboard'),
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
        {
            title: t('Dispatch'),
            href: dispatchUrl.value,
            icon: Waypoints,
        },
        ...(canDrive.value
            ? [
                  {
                      title: t('Driver portal'),
                      href: driverUrl.value,
                      icon: Navigation,
                  },
              ]
            : []),
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
        {
            title: t('Orders'),
            href: ordersIndex.url({ current_team: teamSlug.value }),
            icon: ClipboardList,
        },
        {
            title: t('Shipments'),
            href: shipmentsIndex.url({ current_team: teamSlug.value }),
            icon: Package,
        },
        {
            title: t('Loads'),
            href: loadsIndex.url({ current_team: teamSlug.value }),
            icon: Layers,
        },
        {
            title: t('Trips'),
            href: tripsIndex.url({ current_team: teamSlug.value }),
            icon: Route,
        },
        {
            title: t('Help'),
            href: help.url({ current_team: teamSlug.value }),
            icon: BookOpen,
        },
    ];
});

const fleetNavItems = computed<NavItem[]>(() => {
    if (isDriver.value) {
        return [];
    }

    return [
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
    ];
});
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
