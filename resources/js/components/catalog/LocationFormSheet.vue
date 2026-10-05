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
import { store } from '@/routes/locations';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    parties: Option[];
}>();

const open = ref(false);

const form = useForm({
    party_id: 'none',
    name: '',
    street: '',
    exterior_number: '',
    interior_number: '',
    neighborhood: '',
    city: '',
    state: '',
    postal_code: '',
    references: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        party_id: data.party_id === 'none' ? null : data.party_id,
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
            <Button size="sm" data-test="new-location">
                <Plus class="size-4" />
                {{ $t('New site') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-lg">
            <SheetHeader>
                <SheetTitle>{{ $t('New site') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('A pickup, delivery, or warehouse address.') }}
                </SheetDescription>
            </SheetHeader>

            <form
                class="grid gap-4 px-4 sm:grid-cols-6"
                @submit.prevent="submit"
            >
                <div class="grid gap-2 sm:col-span-4">
                    <Label for="new-location-name">{{ $t('Name') }}</Label>
                    <Input id="new-location-name" v-model="form.name" v-focus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="new-location-party">{{ $t('Party') }}</Label>
                    <Select v-model="form.party_id">
                        <SelectTrigger id="new-location-party" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('No party') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in parties"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.party_id" />
                </div>

                <div class="grid gap-2 sm:col-span-4">
                    <Label for="new-location-street">{{ $t('Street') }}</Label>
                    <Input id="new-location-street" v-model="form.street" />
                    <InputError :message="form.errors.street" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="new-location-exterior">{{ $t('No.') }}</Label>
                    <Input
                        id="new-location-exterior"
                        v-model="form.exterior_number"
                    />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="new-location-interior">{{ $t('Int.') }}</Label>
                    <Input
                        id="new-location-interior"
                        v-model="form.interior_number"
                    />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="new-location-neighborhood">
                        {{ $t('Neighborhood') }}
                    </Label>
                    <Input
                        id="new-location-neighborhood"
                        v-model="form.neighborhood"
                    />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="new-location-city">{{ $t('City') }}</Label>
                    <Input id="new-location-city" v-model="form.city" />
                    <InputError :message="form.errors.city" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="new-location-state">{{ $t('State') }}</Label>
                    <Input id="new-location-state" v-model="form.state" />
                    <InputError :message="form.errors.state" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="new-location-zip">{{ $t('Zip') }}</Label>
                    <Input id="new-location-zip" v-model="form.postal_code" />
                    <InputError :message="form.errors.postal_code" />
                </div>

                <div class="grid gap-2 sm:col-span-6">
                    <Label for="new-location-references">
                        {{ $t('Directions') }}
                    </Label>
                    <Textarea
                        id="new-location-references"
                        v-model="form.references"
                    />
                    <InputError :message="form.errors.references" />
                </div>
            </form>

            <SheetFooter>
                <Button variant="outline" type="button" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-location"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
