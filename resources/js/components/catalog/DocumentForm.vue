<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
import { Textarea } from '@/components/ui/textarea';
import type { ComplianceDocument, Option } from '@/types';

const props = defineProps<{
    types: Option[];
    submitUrl: string;
    method: 'post' | 'patch';
    document?: ComplianceDocument;
}>();

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const form = useForm({
    type: props.document?.type ?? props.types[0]?.value ?? 'other',
    number: props.document?.number ?? '',
    issued_at: props.document?.issued_at ?? '',
    expires_at: props.document?.expires_at ?? '',
    notes: props.document?.notes ?? '',
});

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    };

    if (props.method === 'patch') {
        form.patch(props.submitUrl, options);
    } else {
        form.post(props.submitUrl, options);
    }
};
</script>

<template>
    <form
        class="grid gap-3 rounded-md border bg-muted/30 p-3 sm:grid-cols-2"
        @submit.prevent="submit"
    >
        <div class="grid gap-1.5">
            <Label :for="`document-type-${document?.id ?? 'new'}`">
                {{ $t('Document type') }}
            </Label>
            <Select v-model="form.type">
                <SelectTrigger
                    :id="`document-type-${document?.id ?? 'new'}`"
                    class="w-full"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="item in types"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.type" />
        </div>

        <div class="grid gap-1.5">
            <Label :for="`document-number-${document?.id ?? 'new'}`">
                {{ $t('Number') }}
            </Label>
            <Input
                :id="`document-number-${document?.id ?? 'new'}`"
                v-model="form.number"
            />
            <InputError :message="form.errors.number" />
        </div>

        <div class="grid gap-1.5">
            <Label :for="`document-issued-${document?.id ?? 'new'}`">
                {{ $t('Issued') }}
            </Label>
            <Input
                :id="`document-issued-${document?.id ?? 'new'}`"
                v-model="form.issued_at"
                type="date"
            />
            <InputError :message="form.errors.issued_at" />
        </div>

        <div class="grid gap-1.5">
            <Label :for="`document-expires-${document?.id ?? 'new'}`">
                {{ $t('Expires') }}
            </Label>
            <Input
                :id="`document-expires-${document?.id ?? 'new'}`"
                v-model="form.expires_at"
                type="date"
            />
            <InputError :message="form.errors.expires_at" />
        </div>

        <div class="grid gap-1.5 sm:col-span-2">
            <Label :for="`document-notes-${document?.id ?? 'new'}`">
                {{ $t('Notes') }}
            </Label>
            <Textarea
                :id="`document-notes-${document?.id ?? 'new'}`"
                v-model="form.notes"
                rows="2"
            />
            <InputError :message="form.errors.notes" />
        </div>

        <div class="flex items-center gap-2 sm:col-span-2">
            <Button type="submit" size="sm" :disabled="form.processing">
                {{ $t('Save document') }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="emit('cancel')"
            >
                {{ $t('Cancel') }}
            </Button>
        </div>
    </form>
</template>
