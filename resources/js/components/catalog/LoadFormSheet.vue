<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
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
import { store } from '@/routes/loads';

const props = defineProps<{
    teamSlug: string;
}>();

const open = ref(false);

const form = useForm({
    status: 'draft',
    notes: '',
});

const submit = () => {
    form.post(store.url({ current_team: props.teamSlug }), {
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
            <Button size="sm" data-test="new-load">
                <Plus class="size-4" />
                {{ $t('New load') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-lg">
            <SheetHeader>
                <SheetTitle>{{ $t('New load') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('A group of shipments planned together.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4 py-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="load-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="load-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">
                                {{ $t('Draft') }}
                            </SelectItem>
                            <SelectItem value="planned">
                                {{ $t('Planned') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="load-notes">{{ $t('Notes') }}</Label>
                    <Textarea id="load-notes" v-model="form.notes" />
                    <InputError :message="form.errors.notes" />
                </div>
            </form>

            <SheetFooter>
                <Button variant="outline" type="button" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-load"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
