<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Fuel, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { useOnlineStatus } from '@/composables/useOnlineStatus';
import { t } from '@/lib/i18n';
import {
    compressImage,
    formatMaxSize,
    validateEvidenceFiles,
} from '@/lib/evidence';
import { store } from '@/routes/driver/trips/expenses';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stopId?: number;
    expenseTypes: Option[];
}>();

const open = ref(false);
const { online } = useOnlineStatus();
const { enqueue } = useOfflineQueue();
const page = usePage();
const maxKilobytes = computed(() => page.props.uploadLimits.maxKilobytes);

const url = computed(() =>
    store.url({ current_team: props.teamSlug, trip: props.tripId }),
);

const form = useForm({
    type: 'fuel',
    amount: '',
    liters: '',
    price_per_liter: '',
    odometer_km: '',
    tank: '',
    vendor: '',
    receipt: null as File | null,
});

const isFuel = computed(() => form.type === 'fuel');

const clientReceiptError = ref<string | undefined>();

const receiptError = computed(
    () => clientReceiptError.value ?? form.errors.receipt,
);

const onReceipt = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    // Allow picking the same file again after removing it.
    input.value = '';
    clientReceiptError.value = undefined;

    if (file === null) {
        form.receipt = null;

        return;
    }

    const { errors } = validateEvidenceFiles(
        [file],
        'document',
        maxKilobytes.value,
        1,
    );

    if (errors.length > 0) {
        clientReceiptError.value = errors[0];
        toast.error(errors[0]);
        form.receipt = null;

        return;
    }

    form.receipt = file.type.startsWith('image/')
        ? await compressImage(file)
        : file;
};

const removeReceipt = () => {
    form.receipt = null;
    clientReceiptError.value = undefined;
};

const basePayload = () => ({
    type: form.type,
    amount: form.amount,
    liters: isFuel.value ? form.liters : null,
    price_per_liter: form.price_per_liter,
    odometer_km: form.odometer_km,
    tank: form.tank,
    vendor: form.vendor,
    stop_id: props.stopId ?? null,
    incurred_at: new Date().toISOString(),
    idempotency_key: crypto.randomUUID(),
});

const submit = () => {
    if (!online.value) {
        if (form.receipt !== null) {
            toast.error(
                t(
                    'The receipt needs a connection. Remove it or reconnect to save.',
                ),
            );

            return;
        }

        enqueue(url.value, 'post', basePayload());
        toast.success(t('Saved offline. It will sync when you reconnect.'));
        open.value = false;

        return;
    }

    form.transform(() => ({
        ...form.data(),
        stop_id: props.stopId ?? null,
        incurred_at: new Date().toISOString(),
        idempotency_key: crypto.randomUUID(),
    }));

    form.post(url.value, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            clientReceiptError.value = undefined;
        },
        onError: () => toast.error(t('Please fix the highlighted fields.')),
    });
};
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button size="sm" variant="outline" data-test="open-expense">
                <Fuel class="size-4" />
                {{ $t('Expense') }}
            </Button>
        </SheetTrigger>

        <SheetContent side="bottom" class="max-h-[90vh] overflow-y-auto">
            <SheetHeader>
                <SheetTitle>{{ $t('Log expense') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('Fuel, tolls, and other trip costs.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="expense-type">{{ $t('Type') }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="expense-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in expenseTypes"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="expense-amount">{{ $t('Amount') }}</Label>
                    <Input
                        id="expense-amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        inputmode="decimal"
                    />
                    <InputError :message="form.errors.amount" />
                </div>

                <template v-if="isFuel">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="expense-liters">
                                {{ $t('Litres') }}
                            </Label>
                            <Input
                                id="expense-liters"
                                v-model="form.liters"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                            />
                            <InputError :message="form.errors.liters" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="expense-price">
                                {{ $t('Price per litre') }}
                            </Label>
                            <Input
                                id="expense-price"
                                v-model="form.price_per_liter"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="expense-odometer">
                                {{ $t('Odometer (km)') }}
                            </Label>
                            <Input
                                id="expense-odometer"
                                v-model="form.odometer_km"
                                type="number"
                                step="0.1"
                                min="0"
                                inputmode="decimal"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="expense-tank">{{ $t('Tank') }}</Label>
                            <Input id="expense-tank" v-model="form.tank" />
                        </div>
                    </div>
                </template>

                <div class="grid gap-2">
                    <Label for="expense-vendor">{{ $t('Vendor') }}</Label>
                    <Input id="expense-vendor" v-model="form.vendor" />
                </div>

                <div v-if="online" class="grid gap-2">
                    <Label for="expense-receipt">{{ $t('Receipt') }}</Label>
                    <Input
                        id="expense-receipt"
                        type="file"
                        accept="image/*,application/pdf"
                        @input="onReceipt"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{
                            $t('A photo or PDF, up to :size.', {
                                size: formatMaxSize(maxKilobytes),
                            })
                        }}
                    </p>

                    <div
                        v-if="form.receipt"
                        class="flex items-center justify-between gap-2 rounded-md border px-2 py-1 text-xs"
                    >
                        <span class="min-w-0 truncate">{{
                            form.receipt.name
                        }}</span>
                        <button
                            type="button"
                            class="shrink-0 text-muted-foreground hover:text-destructive"
                            :aria-label="$t('Remove')"
                            @click="removeReceipt"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>

                    <InputError :message="receiptError" />
                </div>
            </form>

            <SheetFooter>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-expense"
                    @click="submit"
                >
                    {{ online ? $t('Save') : $t('Save offline') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
