<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { FileSignature, X } from '@lucide/vue';
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
import {
    MAX_EVIDENCE_FILES,
    compressImage,
    formatMaxSize,
    validateEvidenceFiles,
} from '@/lib/evidence';
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
const page = usePage();
const maxKilobytes = computed(() => page.props.uploadLimits.maxKilobytes);

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

const clientPhotoErrors = ref<string[]>([]);

/**
 * The first server error attached to the photo collection (`photos` or
 * `photos.0`, `photos.1`, …), which the form does not otherwise display.
 */
const photoError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;
    const key = Object.keys(errors).find(
        (name) => name === 'photos' || name.startsWith('photos.'),
    );

    return key ? errors[key] : undefined;
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

const onPhotos = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const selected = Array.from(input.files ?? []);

    // Allow picking the same file again after removing it.
    input.value = '';

    const remaining = Math.max(MAX_EVIDENCE_FILES - photos.value.length, 0);

    const { accepted, errors } = validateEvidenceFiles(
        selected,
        'image',
        maxKilobytes.value,
        remaining,
    );

    clientPhotoErrors.value = errors;
    errors.forEach((message) => toast.error(message));

    const compressed = await Promise.all(
        accepted.map((file) => compressImage(file)),
    );

    photos.value = [...photos.value, ...compressed];
};

const removePhoto = (index: number) => {
    photos.value = photos.value.filter((_, i) => i !== index);
};

const submit = () => {
    if (!online.value) {
        if (photos.value.length > 0) {
            toast.error(
                t(
                    'Photos need a connection. Remove them or reconnect to save.',
                ),
            );

            return;
        }

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
        photos: photos.value,
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
            clientPhotoErrors.value = [];
        },
        onError: () => toast.error(t('Please fix the highlighted fields.')),
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
                    <p class="text-xs text-muted-foreground">
                        {{
                            $t('Up to :max images, :size each.', {
                                max: MAX_EVIDENCE_FILES,
                                size: formatMaxSize(maxKilobytes),
                            })
                        }}
                    </p>

                    <ul v-if="photos.length" class="grid gap-1">
                        <li
                            v-for="(photo, index) in photos"
                            :key="`${photo.name}-${index}`"
                            class="flex items-center justify-between gap-2 rounded-md border px-2 py-1 text-xs"
                        >
                            <span class="min-w-0 truncate">{{
                                photo.name
                            }}</span>
                            <button
                                type="button"
                                class="shrink-0 text-muted-foreground hover:text-destructive"
                                :aria-label="$t('Remove')"
                                @click="removePhoto(index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </li>
                    </ul>

                    <p
                        v-for="(message, index) in clientPhotoErrors"
                        :key="index"
                        class="text-sm text-red-600 dark:text-red-500"
                    >
                        {{ message }}
                    </p>

                    <InputError :message="photoError" />
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
