<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Package as PackageIcon, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy, update } from '@/routes/shipments';
import {
    destroy as destroyPackage,
    store as storePackages,
} from '@/routes/shipments/packages';
import { show as showLoad } from '@/routes/loads';
import { show as showOrder } from '@/routes/orders';
import { show as showTrip } from '@/routes/trips';
import type { Option, ShipmentDetail } from '@/types';

const props = defineProps<{
    shipment: ShipmentDetail;
    teamSlug: string;
    locations: Option[];
    canManage: boolean;
}>();

const form = useForm({
    pickup_location_id: props.shipment.pickup_location_id
        ? String(props.shipment.pickup_location_id)
        : 'none',
    delivery_location_id: props.shipment.delivery_location_id
        ? String(props.shipment.delivery_location_id)
        : 'none',
});

const save = () => {
    form.transform((data) => ({
        ...data,
        pickup_location_id:
            data.pickup_location_id === 'none' ? null : data.pickup_location_id,
        delivery_location_id:
            data.delivery_location_id === 'none'
                ? null
                : data.delivery_location_id,
    }));

    form.patch(
        update.url({
            current_team: props.teamSlug,
            shipment: props.shipment.id,
        }),
        { preserveScroll: true, onSuccess: () => form.defaults() },
    );
};

const packageForm = useForm({
    count: 1,
    weight_kg: '',
});

const addPackages = () => {
    packageForm.post(
        storePackages.url({
            current_team: props.teamSlug,
            shipment: props.shipment.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => packageForm.reset(),
        },
    );
};

const removePackage = (packageId: number) => {
    router.delete(
        destroyPackage.url({
            current_team: props.teamSlug,
            shipment: props.shipment.id,
            package: packageId,
        }),
        { preserveScroll: true },
    );
};

const totalWeightKg = computed(() => props.shipment.weight_grams / 1000);
const totalVolumeM3 = computed(() => props.shipment.volume_cm3 / 1000000);
const packageLimit = computed(() => props.shipment.package_limit);
const remainingPackages = computed(() =>
    Math.max(packageLimit.value - props.shipment.packages_count, 0),
);
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ shipment.number }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ shipment.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ shipment.customer_name ?? $t('No customer') }}
                </p>

                <div
                    v-if="
                        shipment.order_number ||
                        shipment.load_number ||
                        shipment.trip_number
                    "
                    class="mt-2 flex flex-wrap items-center gap-1.5 text-xs"
                >
                    <Link
                        v-if="shipment.order_number"
                        class="rounded-full border px-2 py-0.5 hover:underline"
                        :href="
                            showOrder({
                                current_team: teamSlug,
                                order: shipment.order_id!,
                            })
                        "
                    >
                        {{ shipment.order_number }}
                    </Link>
                    <span
                        v-if="shipment.order_number && shipment.load_number"
                        class="text-muted-foreground"
                    >
                        →
                    </span>
                    <Link
                        v-if="shipment.load_number"
                        class="rounded-full border px-2 py-0.5 hover:underline"
                        :href="
                            showLoad({
                                current_team: teamSlug,
                                load: shipment.load_id!,
                            })
                        "
                    >
                        {{ shipment.load_number }}
                    </Link>
                    <span
                        v-if="
                            (shipment.order_number || shipment.load_number) &&
                            shipment.trip_number
                        "
                        class="text-muted-foreground"
                    >
                        →
                    </span>
                    <Link
                        v-if="shipment.trip_number"
                        class="rounded-full border px-2 py-0.5 hover:underline"
                        :href="
                            showTrip({
                                current_team: teamSlug,
                                trip: shipment.trip_id!,
                            })
                        "
                    >
                        {{ shipment.trip_number }}
                    </Link>
                </div>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="
                    destroy.url({
                        current_team: teamSlug,
                        shipment: shipment.id,
                    })
                "
                :title="$t('Delete :name?', { name: shipment.number })"
                :description="
                    $t('The shipment will stop appearing in the lists.')
                "
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-3" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="shipment-pickup">{{ $t('Pickup site') }}</Label>
                    <Select
                        v-model="form.pickup_location_id"
                        :disabled="!canManage"
                    >
                        <SelectTrigger id="shipment-pickup" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('None') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in locations"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.pickup_location_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="shipment-delivery">
                        {{ $t('Delivery site') }}
                    </Label>
                    <Select
                        v-model="form.delivery_location_id"
                        :disabled="!canManage"
                    >
                        <SelectTrigger id="shipment-delivery" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('None') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in locations"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.delivery_location_id" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-3"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-shipment-changes"
                    >
                        {{ $t('Save changes') }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="form.reset()"
                    >
                        {{ $t('Discard') }}
                    </Button>
                </div>
            </form>

            <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Weight') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ totalWeightKg.toLocaleString() }} kg
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Volume') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ totalVolumeM3.toLocaleString() }} m³
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Pieces') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ shipment.pieces.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Packages') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ shipment.packages_count }}
                    </p>
                </div>
            </section>

            <section
                v-if="shipment.pickup_snapshot || shipment.delivery_snapshot"
                class="mt-6 grid gap-3 sm:grid-cols-2"
            >
                <div
                    v-if="shipment.pickup_snapshot"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Pickup') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ shipment.pickup_snapshot.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ shipment.pickup_snapshot.street }}
                        {{ shipment.pickup_snapshot.city }},
                        {{ shipment.pickup_snapshot.state }}
                    </p>
                </div>
                <div
                    v-if="shipment.delivery_snapshot"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Delivery') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ shipment.delivery_snapshot.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ shipment.delivery_snapshot.street }}
                        {{ shipment.delivery_snapshot.city }},
                        {{ shipment.delivery_snapshot.state }}
                    </p>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="text-sm font-semibold">{{ $t('Goods') }}</h3>
                <ul class="mt-2 divide-y rounded-lg border">
                    <li
                        v-for="item in shipment.items"
                        :key="item.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span>
                            {{ item.description }}
                            <span
                                v-if="item.hazmat"
                                class="ml-2 rounded-full border border-amber-300 px-2 py-0.5 text-[11px] text-amber-700 dark:text-amber-400"
                            >
                                {{ $t('Hazmat') }}
                            </span>
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ item.quantity }} {{ item.unit }} ·
                            {{ item.weight_kg.toLocaleString() }} kg
                        </span>
                    </li>
                    <li
                        v-if="shipment.items.length === 0"
                        class="px-3 py-4 text-center text-sm text-muted-foreground"
                    >
                        {{ $t('No goods recorded.') }}
                    </li>
                </ul>
            </section>

            <section class="mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">
                        {{ $t('Packages') }}
                    </h3>
                    <span class="text-xs text-muted-foreground">
                        {{
                            $t(':count of :max', {
                                count: shipment.packages_count,
                                max: packageLimit,
                            })
                        }}
                    </span>
                </div>

                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        $t(
                            'Packages are the individual units scanned on the road.',
                        )
                    }}
                </p>

                <ul
                    v-if="shipment.packages.length"
                    class="mt-2 divide-y rounded-lg border"
                >
                    <li
                        v-for="packageItem in shipment.packages"
                        :key="packageItem.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span class="flex items-center gap-2">
                            <PackageIcon class="size-4 opacity-50" />
                            {{ packageItem.code }}
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="text-xs text-muted-foreground">
                                {{ packageItem.status_label }}
                            </span>
                            <Button
                                v-if="canManage"
                                variant="ghost"
                                size="icon"
                                class="size-7 text-muted-foreground hover:text-destructive"
                                @click="removePackage(packageItem.id)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </span>
                    </li>
                </ul>

                <p v-else class="mt-2 text-sm text-muted-foreground">
                    {{ $t('No packages yet.') }}
                </p>

                <form
                    v-if="canManage"
                    class="mt-3 flex flex-wrap items-end gap-3 rounded-lg border p-3"
                    @submit.prevent="addPackages"
                >
                    <div class="grid gap-2">
                        <Label for="package-count">{{ $t('Quantity') }}</Label>
                        <Input
                            id="package-count"
                            v-model.number="packageForm.count"
                            type="number"
                            min="1"
                            :max="remainingPackages"
                            class="w-24"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="package-weight">
                            {{ $t('Weight (kg)') }}
                        </Label>
                        <Input
                            id="package-weight"
                            v-model="packageForm.weight_kg"
                            type="number"
                            step="0.001"
                            min="0"
                            class="w-32"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        variant="outline"
                        :disabled="
                            packageForm.processing || remainingPackages === 0
                        "
                        data-test="add-packages"
                    >
                        <Plus class="size-4" />
                        {{ $t('Add packages') }}
                    </Button>
                    <InputError :message="packageForm.errors.count" />
                    <InputError :message="packageForm.errors.weight_kg" />
                </form>
            </section>
        </div>
    </div>
</template>
