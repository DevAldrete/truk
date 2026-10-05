<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileSignature } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { store } from '@/routes/driver/trips/stops/pod';
import type { DriverStop } from '@/types';

const props = defineProps<{
    teamSlug: string;
    tripId: number;
    stop: DriverStop;
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

const canvas = ref<HTMLCanvasElement | null>(null);
const signature = ref('');
const photos = ref<File[]>([]);
let context: CanvasRenderingContext2D | null = null;
let drawing = false;

const form = useForm({
    recipient_name: '',
    consent: true,
    signature: '',
});

onMounted(() => {
    const element = canvas.value;

    if (!element) {
        return;
    }

    context = element.getContext('2d');
    context!.lineWidth = 2;
    context!.lineCap = 'round';
    context!.strokeStyle = '#111827';
});

const point = (event: PointerEvent) => {
    const element = canvas.value!;
    const rect = element.getBoundingClientRect();

    return {
        x: ((event.clientX - rect.left) * element.width) / rect.width,
        y: ((event.clientY - rect.top) * element.height) / rect.height,
    };
};

const start = (event: PointerEvent) => {
    drawing = true;
    const { x, y } = point(event);
    context?.beginPath();
    context?.moveTo(x, y);
};

const draw = (event: PointerEvent) => {
    if (!drawing) {
        return;
    }

    const { x, y } = point(event);
    context?.lineTo(x, y);
    context?.stroke();
};

const end = () => {
    if (!drawing) {
        return;
    }

    drawing = false;
    signature.value = canvas.value?.toDataURL('image/png') ?? '';
};

const clear = () => {
    const element = canvas.value;

    if (element && context) {
        context.clearRect(0, 0, element.width, element.height);
    }

    signature.value = '';
};

const onPhotos = (event: Event) => {
    photos.value = Array.from((event.target as HTMLInputElement).files ?? []);
};

const submit = () => {
    if (!online.value) {
        if (signature.value === '') {
            toast.error(t('Connect to the network to attach photos.'));

            return;
        }

        enqueue(url.value, 'post', {
            recipient_name: form.recipient_name,
            consent: form.consent,
            signature: signature.value,
            captured_at: new Date().toISOString(),
            idempotency_key: crypto.randomUUID(),
        });
        toast.success(t('Saved offline. It will sync when you reconnect.'));
        open.value = false;

        return;
    }

    form.transform(() => ({
        ...form.data(),
        signature: signature.value,
        photos,
        captured_at: new Date().toISOString(),
        idempotency_key: crypto.randomUUID(),
    }));

    form.post(url.value, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            open.value = false;
            clear();
            form.reset();
            photos.value = [];
        },
    });
};
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button size="sm" variant="outline" data-test="open-pod">
                <FileSignature class="size-4" />
                {{ $t('POD') }}
            </Button>
        </SheetTrigger>

        <SheetContent side="bottom" class="max-h-[90vh] overflow-y-auto">
            <SheetHeader>
                <SheetTitle>{{ $t('Proof of delivery') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('Recipient, signature, and photos.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="pod-recipient">
                        {{ $t('Recipient name') }}
                    </Label>
                    <Input id="pod-recipient" v-model="form.recipient_name" />
                    <InputError :message="form.errors.recipient_name" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ $t('Signature') }}</Label>
                    <canvas
                        ref="canvas"
                        width="600"
                        height="200"
                        class="h-40 w-full touch-none rounded-lg border bg-background"
                        @pointerdown="start"
                        @pointermove="draw"
                        @pointerup="end"
                        @pointerleave="end"
                    />
                    <div class="flex justify-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clear"
                        >
                            {{ $t('Clear') }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.signature" />
                </div>

                <div v-if="online" class="grid gap-2">
                    <Label for="pod-photos">{{ $t('Photos') }}</Label>
                    <Input
                        id="pod-photos"
                        type="file"
                        accept="image/*"
                        multiple
                        @input="onPhotos"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.consent"
                        type="checkbox"
                        class="size-4"
                    />
                    {{ $t('The recipient consents to this evidence.') }}
                </label>
            </form>

            <SheetFooter>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-pod"
                    @click="submit"
                >
                    {{ online ? $t('Save') : $t('Save offline') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
