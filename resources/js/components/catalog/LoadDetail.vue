<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Link2, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { destroy, update } from '@/routes/loads';
import {
    destroy as detachShipment,
    store as attachShipment,
} from '@/routes/loads/shipments';
import { show as showShipment } from '@/routes/shipments';
import type { LoadDetail, Option } from '@/types';

const props = defineProps<{
    load: LoadDetail;
    teamSlug: string;
    availableShipments: Option[];
    statuses: Option[];
    canManage: boolean;
}>();

const form = useForm({
    status: props.load.status,
    notes: props.load.notes ?? '',
});

const save = () => {
    form.patch(
        update.url({ current_team: props.teamSlug, load: props.load.id }),
        {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        },
    );
};

const attach = useForm({ shipment_id: 'none' });

const submitAttach = () => {
    if (attach.shipment_id === 'none') {
        return;
    }

    attach.post(
        attachShipment.url({
            current_team: props.teamSlug,
            load: props.load.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => attach.reset(),
        },
    );
};

const removeShipment = (shipmentId: number) => {
    router.delete(
        detachShipment.url({
            current_team: props.teamSlug,
            load: props.load.id,
            shipment: shipmentId,
        }),
        { preserveScroll: true },
    );
};

const totalWeightKg = computed(() => props.load.totals.weight_grams / 1000);
const totalVolumeM3 = computed(() => props.load.totals.volume_cm3 / 1000000);
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ load.number }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ load.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ load.shipments_count }} {{ $t('shipments') }}
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="destroy.url({ current_team: teamSlug, load: load.id })"
                :title="$t('Delete :name?', { name: load.number })"
                :description="$t('The load will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-3" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="load-detail-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status" :disabled="!canManage">
                        <SelectTrigger id="load-detail-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in statuses"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="load-detail-notes">{{ $t('Notes') }}</Label>
                    <Textarea
                        id="load-detail-notes"
                        v-model="form.notes"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-3"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-load-changes"
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

            <section class="mt-6 grid grid-cols-3 gap-3">
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
                        {{ load.totals.pieces.toLocaleString() }}
                    </p>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="text-sm font-semibold">
                    {{ $t('Shipments in this load') }}
                </h3>

                <ul
                    v-if="load.shipments.length"
                    class="mt-2 divide-y rounded-lg border"
                >
                    <li
                        v-for="shipment in load.shipments"
                        :key="shipment.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <Link2 class="size-4 shrink-0 opacity-50" />
                            <Link
                                class="truncate font-medium hover:underline"
                                :href="
                                    showShipment({
                                        current_team: teamSlug,
                                        shipment: shipment.id,
                                    })
                                "
                            >
                                {{ shipment.number }}
                            </Link>
                            <span
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{
                                    shipment.customer_name ?? $t('No customer')
                                }}
                            </span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="text-xs text-muted-foreground">
                                {{ shipment.pieces }} {{ $t('pcs') }}
                            </span>
                            <Button
                                v-if="canManage"
                                variant="ghost"
                                size="icon"
                                class="size-7 text-muted-foreground hover:text-destructive"
                                :data-test="`detach-shipment-${shipment.id}`"
                                @click="removeShipment(shipment.id)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </span>
                    </li>
                </ul>

                <p v-else class="mt-2 text-sm text-muted-foreground">
                    {{ $t('No shipments yet.') }}
                </p>

                <form
                    v-if="canManage"
                    class="mt-3 flex flex-wrap items-end gap-3 rounded-lg border p-3"
                    @submit.prevent="submitAttach"
                >
                    <div class="grid min-w-0 flex-1 gap-2">
                        <Label for="load-attach">
                            {{ $t('Add a shipment') }}
                        </Label>
                        <Select v-model="attach.shipment_id">
                            <SelectTrigger id="load-attach" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('Select a shipment') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in availableShipments"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="attach.errors.shipment_id" />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        variant="outline"
                        :disabled="
                            attach.processing || attach.shipment_id === 'none'
                        "
                        data-test="attach-shipment"
                    >
                        <Plus class="size-4" />
                        {{ $t('Add') }}
                    </Button>
                </form>
            </section>
        </div>
    </div>
</template>
