<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import OrderItemsEditor from '@/components/catalog/OrderItemsEditor.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { destroy, update } from '@/routes/orders';
import { store as convertOrder } from '@/routes/orders/shipments';
import { show as showShipment } from '@/routes/shipments';
import type { Option, OrderDetail, OrderItem, OrderLineInput } from '@/types';

const props = defineProps<{
    order: OrderDetail;
    teamSlug: string;
    customers: Option[];
    locations: Option[];
    statuses: Option[];
    canManage: boolean;
}>();

const toLocalInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const toLines = (items: OrderItem[]): OrderLineInput[] =>
    items.map((item) => ({
        description: item.description,
        quantity: item.quantity,
        unit: item.unit,
        weight_kg: item.weight_kg,
        volume_m3: item.volume_m3,
        hazmat: item.hazmat,
    }));

const form = useForm({
    customer_party_id: props.order.customer_party_id
        ? String(props.order.customer_party_id)
        : 'none',
    status: props.order.status,
    currency: props.order.currency,
    requested_pickup_at: toLocalInput(props.order.requested_pickup_at),
    requested_delivery_at: toLocalInput(props.order.requested_delivery_at),
    notes: props.order.notes ?? '',
    items: toLines(props.order.items),
});

const save = () => {
    form.transform((data) => ({
        ...data,
        customer_party_id:
            data.customer_party_id === 'none' ? null : data.customer_party_id,
        requested_pickup_at: data.requested_pickup_at || null,
        requested_delivery_at: data.requested_delivery_at || null,
        items: data.items.map((item) => ({
            ...item,
            quantity: Number(item.quantity),
        })),
    }));

    form.patch(
        update.url({ current_team: props.teamSlug, order: props.order.id }),
        {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        },
    );
};

const convert = useForm({
    pickup_location_id: 'none',
    delivery_location_id: 'none',
    package_count: 0,
});

const submitConvert = () => {
    convert.transform((data) => ({
        ...data,
        pickup_location_id:
            data.pickup_location_id === 'none' ? null : data.pickup_location_id,
        delivery_location_id:
            data.delivery_location_id === 'none'
                ? null
                : data.delivery_location_id,
    }));

    convert.post(
        convertOrder.url({
            current_team: props.teamSlug,
            order: props.order.id,
        }),
        { preserveScroll: true },
    );
};

const totalWeightKg = computed(() => props.order.totals.weight_grams / 1000);
const totalVolumeM3 = computed(() => props.order.totals.volume_cm3 / 1000000);
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ order.number }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ order.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ order.customer_name ?? $t('No customer') }}
                    <template v-if="order.customer_rfc">
                        · {{ order.customer_rfc }}
                    </template>
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="destroy.url({ current_team: teamSlug, order: order.id })"
                :title="$t('Delete :name?', { name: order.number })"
                :description="$t('The order will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="order-detail-customer">
                        {{ $t('Customer') }}
                    </Label>
                    <Select
                        v-model="form.customer_party_id"
                        :disabled="!canManage"
                    >
                        <SelectTrigger
                            id="order-detail-customer"
                            class="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('No customer') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in customers"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.customer_party_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-detail-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status" :disabled="!canManage">
                        <SelectTrigger id="order-detail-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-detail-pickup">
                        {{ $t('Requested pickup') }}
                    </Label>
                    <Input
                        id="order-detail-pickup"
                        v-model="form.requested_pickup_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.requested_pickup_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-detail-delivery">
                        {{ $t('Requested delivery') }}
                    </Label>
                    <Input
                        id="order-detail-delivery"
                        v-model="form.requested_delivery_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.requested_delivery_at" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="order-detail-notes">{{ $t('Notes') }}</Label>
                    <Textarea
                        id="order-detail-notes"
                        v-model="form.notes"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="sm:col-span-2">
                    <OrderItemsEditor
                        v-model="form.items"
                        :errors="form.errors"
                    />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-2"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-order-changes"
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
                        {{ order.totals.pieces.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('Hazmat') }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ order.totals.hazmat ? $t('Yes') : $t('No') }}
                    </p>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="text-sm font-semibold">
                    {{ $t('Shipments') }}
                </h3>

                <ul
                    v-if="order.shipments.length"
                    class="mt-2 divide-y rounded-lg border"
                >
                    <li
                        v-for="shipment in order.shipments"
                        :key="shipment.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <Link
                            class="font-medium hover:underline"
                            :href="
                                showShipment({
                                    current_team: teamSlug,
                                    shipment: shipment.id,
                                })
                            "
                        >
                            {{ shipment.number }}
                        </Link>
                        <span class="text-xs text-muted-foreground">
                            {{ shipment.status_label }} · {{ shipment.pieces }}
                            {{ $t('pcs') }}
                        </span>
                    </li>
                </ul>

                <p v-else class="mt-2 text-sm text-muted-foreground">
                    {{ $t('No shipments yet.') }}
                </p>
            </section>

            <section v-if="canManage" class="mt-6 rounded-lg border p-4">
                <h3 class="text-sm font-semibold">
                    {{ $t('Plan a shipment') }}
                </h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        $t(
                            'Copy these goods into a new shipment ready for dispatch.',
                        )
                    }}
                </p>

                <form
                    class="mt-3 grid gap-3 sm:grid-cols-3"
                    @submit.prevent="submitConvert"
                >
                    <div class="grid gap-2">
                        <Label for="convert-pickup">
                            {{ $t('Pickup site') }}
                        </Label>
                        <Select v-model="convert.pickup_location_id">
                            <SelectTrigger id="convert-pickup" class="w-full">
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
                        <InputError
                            :message="convert.errors.pickup_location_id"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="convert-delivery">
                            {{ $t('Delivery site') }}
                        </Label>
                        <Select v-model="convert.delivery_location_id">
                            <SelectTrigger id="convert-delivery" class="w-full">
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
                        <InputError
                            :message="convert.errors.delivery_location_id"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="convert-packages">
                            {{ $t('Packages to generate') }}
                        </Label>
                        <Input
                            id="convert-packages"
                            v-model.number="convert.package_count"
                            type="number"
                            min="0"
                        />
                        <InputError :message="convert.errors.package_count" />
                    </div>

                    <div class="sm:col-span-3">
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="convert.processing"
                            data-test="convert-order"
                        >
                            {{ $t('Create shipment') }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
