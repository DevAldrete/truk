<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ScanLine } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { store } from '@/routes/driver/trips/scans';
import type { DriverStop, Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stop: DriverStop;
    scanTypes: Option[];
}>();

const open = ref(false);
const { online } = useOnlineStatus();
const { enqueue } = useOfflineQueue();

const packages = computed(() =>
    props.stop.shipments.flatMap((shipment) =>
        shipment.packages.map((pack) => ({
            ...pack,
            shipmentNumber: shipment.number,
        })),
    ),
);

const url = computed(() =>
    store.url({ current_team: props.teamSlug, trip: props.tripId }),
);

const form = useForm({
    package_id: '',
    type: 'loaded',
    notes: '',
});

const payload = () => ({
    ...form.data(),
    package_id: Number(form.package_id),
    occurred_at: new Date().toISOString(),
    idempotency_key: crypto.randomUUID(),
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
            <Button size="sm" variant="outline" data-test="open-scan">
                <ScanLine class="size-4" />
                {{ $t('Scan') }}
            </Button>
        </SheetTrigger>

        <SheetContent side="bottom" class="max-h-[90vh] overflow-y-auto">
            <SheetHeader>
                <SheetTitle>{{ $t('Scan package') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('Update package custody at this stop.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="scan-package">{{ $t('Package') }}</Label>
                    <Select v-model="form.package_id">
                        <SelectTrigger id="scan-package" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="pack in packages"
                                :key="pack.id"
                                :value="String(pack.id)"
                            >
                                {{ pack.code }} · {{ pack.shipmentNumber }} ·
                                {{ pack.status_label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.package_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="scan-type">{{ $t('Event') }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="scan-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in scanTypes"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>
            </form>

            <SheetFooter>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-scan"
                    @click="submit"
                >
                    {{ online ? $t('Save') : $t('Save offline') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
