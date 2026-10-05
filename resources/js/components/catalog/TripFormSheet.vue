<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
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
import { store } from '@/routes/trips';

const props = defineProps<{
    teamSlug: string;
}>();

const open = ref(false);

const timezones = [
    'America/Mexico_City',
    'America/Monterrey',
    'America/Chihuahua',
    'America/Mazatlan',
    'America/Hermosillo',
    'America/Tijuana',
    'America/Cancun',
];

const form = useForm({
    status: 'planned',
    planned_start_at: '',
    planned_end_at: '',
    timezone: 'America/Mexico_City',
    notes: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        planned_start_at: data.planned_start_at || null,
        planned_end_at: data.planned_end_at || null,
    }));

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
            <Button size="sm" data-test="new-trip">
                <Plus class="size-4" />
                {{ $t('New trip') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-lg">
            <SheetHeader>
                <SheetTitle>{{ $t('New trip') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('A planned execution using assigned resources.') }}
                </SheetDescription>
            </SheetHeader>

            <form
                class="grid gap-4 px-4 py-4 sm:grid-cols-2"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="trip-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="trip-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="planned">
                                {{ $t('Planned') }}
                            </SelectItem>
                            <SelectItem value="dispatched">
                                {{ $t('Dispatched') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-timezone">{{ $t('Timezone') }}</Label>
                    <Select v-model="form.timezone">
                        <SelectTrigger id="trip-timezone" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="zone in timezones"
                                :key="zone"
                                :value="zone"
                            >
                                {{ zone }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.timezone" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-start">{{ $t('Planned start') }}</Label>
                    <Input
                        id="trip-start"
                        v-model="form.planned_start_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.planned_start_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-end">{{ $t('Planned end') }}</Label>
                    <Input
                        id="trip-end"
                        v-model="form.planned_end_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.planned_end_at" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="trip-notes">{{ $t('Notes') }}</Label>
                    <Textarea id="trip-notes" v-model="form.notes" />
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
                    data-test="save-trip"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
