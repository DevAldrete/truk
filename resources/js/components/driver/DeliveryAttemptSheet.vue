<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { PackageCheck } from '@lucide/vue';
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
import { Textarea } from '@/components/ui/textarea';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { useOnlineStatus } from '@/composables/useOnlineStatus';
import { t } from '@/lib/i18n';
import { store } from '@/routes/driver/trips/stops/attempts';
import type { DriverStop, Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stop: DriverStop;
    outcomes: Option[];
    failureReasons: Option[];
}>();

const open = ref(false);
const { online } = useOnlineStatus();
const { enqueue } = useOfflineQueue();

const url = computed(() =>
    store.url({
        current_team: props.teamSlug,
        trip: props.tripId,
        stop: props.stop.id,
    }),
);

const quantities = ref<Record<number, number>>(
    Object.fromEntries(
        props.stop.shipments.map((shipment) => [
            shipment.id,
            shipment.remaining_quantity,
        ]),
    ),
);

const form = useForm({
    outcome: 'delivered',
    failure_reason: 'none',
    recipient_name: '',
    notes: '',
    lines: props.stop.shipments.map((shipment) => ({
        shipment_id: shipment.id,
        quantity: shipment.remaining_quantity,
        success: true,
    })),
});

const requiresReason = computed(() =>
    ['failed', 'returned'].includes(form.outcome),
);

const payload = () => ({
    ...form.data(),
    failure_reason: form.failure_reason === 'none' ? null : form.failure_reason,
    occurred_at: new Date().toISOString(),
    idempotency_key: crypto.randomUUID(),
    lines: form.lines.map((line) => ({
        ...line,
        quantity: line.success
            ? Number(quantities.value[line.shipment_id] ?? 0)
            : 0,
    })),
});

const submit = () => {
    if (!online.value) {
        enqueue(url.value, 'post', payload());
        toast.success(t('Saved offline. It will sync when you reconnect.'));
        open.value = false;

        return;
    }

    form.transform(() => payload());
    form.post(url.value, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button size="sm" variant="outline" data-test="open-attempt">
                <PackageCheck class="size-4" />
                {{ $t('Deliver') }}
            </Button>
        </SheetTrigger>

        <SheetContent side="bottom" class="max-h-[90vh] overflow-y-auto">
            <SheetHeader>
                <SheetTitle>{{ $t('Delivery attempt') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('Record what happened at this stop.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="attempt-outcome">{{ $t('Outcome') }}</Label>
                    <Select v-model="form.outcome">
                        <SelectTrigger id="attempt-outcome" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in outcomes"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.outcome" />
                </div>

                <div v-if="requiresReason" class="grid gap-2">
                    <Label for="attempt-reason">{{ $t('Reason') }}</Label>
                    <Select v-model="form.failure_reason">
                        <SelectTrigger id="attempt-reason" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('Pick a reason') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in failureReasons"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.failure_reason" />
                </div>

                <div class="grid gap-2">
                    <Label for="attempt-recipient">
                        {{ $t('Recipient name') }}
                    </Label>
                    <Input
                        id="attempt-recipient"
                        v-model="form.recipient_name"
                    />
                    <InputError :message="form.errors.recipient_name" />
                </div>

                <div
                    v-for="(line, index) in form.lines"
                    :key="line.shipment_id"
                    class="grid gap-2 rounded-lg border p-3"
                >
                    <p class="text-sm font-medium">
                        {{ stop.shipments[index]?.number }} ·
                        {{ stop.shipments[index]?.customer_name }}
                    </p>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="line.success"
                                type="checkbox"
                                class="size-4"
                            />
                            {{ $t('Delivered') }}
                        </label>
                        <Input
                            v-model.number="quantities[line.shipment_id]"
                            type="number"
                            min="0"
                            :disabled="!line.success"
                            class="w-24"
                        />
                        <span class="text-xs text-muted-foreground">
                            {{
                                $t('of :count', {
                                    count: stop.shipments[index]?.pieces,
                                })
                            }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="attempt-notes">{{ $t('Notes') }}</Label>
                    <Textarea id="attempt-notes" v-model="form.notes" />
                </div>

                <InputError :message="form.errors.lines" />
            </form>

            <SheetFooter>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-attempt"
                    @click="submit"
                >
                    {{ online ? $t('Save') : $t('Save offline') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
