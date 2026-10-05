<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
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
import { Textarea } from '@/components/ui/textarea';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import { useOnlineStatus } from '@/composables/useOnlineStatus';
import { t } from '@/lib/i18n';
import { store } from '@/routes/driver/trips/incidents';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stopId?: number;
    incidentTypes: Option[];
    incidentSeverities: Option[];
}>();

const open = ref(false);
const { online } = useOnlineStatus();
const { enqueue } = useOfflineQueue();

const url = computed(() =>
    store.url({ current_team: props.teamSlug, trip: props.tripId }),
);

const form = useForm({
    type: 'delay',
    severity: 'medium',
    description: '',
});

const payload = () => ({
    ...form.data(),
    stop_id: props.stopId ?? null,
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
            <Button size="sm" variant="outline" data-test="open-incident">
                <TriangleAlert class="size-4" />
                {{ $t('Incident') }}
            </Button>
        </SheetTrigger>

        <SheetContent side="bottom" class="max-h-[90vh] overflow-y-auto">
            <SheetHeader>
                <SheetTitle>{{ $t('Report incident') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('Flag a problem so dispatch can react.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="incident-type">{{ $t('Type') }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="incident-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in incidentTypes"
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
                    <Label for="incident-severity">
                        {{ $t('Severity') }}
                    </Label>
                    <Select v-model="form.severity">
                        <SelectTrigger id="incident-severity" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in incidentSeverities"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.severity" />
                </div>

                <div class="grid gap-2">
                    <Label for="incident-description">
                        {{ $t('Description') }}
                    </Label>
                    <Textarea
                        id="incident-description"
                        v-model="form.description"
                    />
                    <InputError :message="form.errors.description" />
                </div>
            </form>

            <SheetFooter>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-incident"
                    @click="submit"
                >
                    {{ online ? $t('Save') : $t('Save offline') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
