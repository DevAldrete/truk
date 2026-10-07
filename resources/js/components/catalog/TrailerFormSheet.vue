<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ConfigurationField from '@/components/catalog/ConfigurationField.vue';
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
import { store } from '@/routes/trailers';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    carriers: Option[];
}>();

const open = ref(false);

const form = useForm({
    name: '',
    plate: '',
    configuration: '',
    max_payload_kg: '',
    max_volume_m3: '',
    carrier_party_id: 'none',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        max_volume_m3: data.max_volume_m3 === '' ? null : data.max_volume_m3,
        carrier_party_id:
            data.carrier_party_id === 'none' ? null : data.carrier_party_id,
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
            <Button size="sm" data-test="new-trailer">
                <Plus class="size-4" />
                {{ $t('New trailer') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ $t('New trailer') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('The vehicles and trailers that execute trips.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="trailer-name">{{ $t('Name') }}</Label>
                    <Input id="trailer-name" v-model="form.name" v-focus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="trailer-plate">{{ $t('Plate') }}</Label>
                    <Input
                        id="trailer-plate"
                        v-model="form.plate"
                        class="uppercase"
                        placeholder="ABC-12-34"
                    />
                    <InputError :message="form.errors.plate" />
                </div>

                <ConfigurationField
                    id="trailer-configuration"
                    v-model="form.configuration"
                    :error="form.errors.configuration"
                />

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="trailer-payload">
                            {{ $t('Payload') }} (kg)
                        </Label>
                        <Input
                            id="trailer-payload"
                            v-model="form.max_payload_kg"
                            type="number"
                            step="0.001"
                            min="0"
                        />
                        <InputError :message="form.errors.max_payload_kg" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="trailer-volume">
                            {{ $t('Volume') }} (m³)
                        </Label>
                        <Input
                            id="trailer-volume"
                            v-model="form.max_volume_m3"
                            type="number"
                            step="0.001"
                            min="0"
                        />
                        <InputError :message="form.errors.max_volume_m3" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="trailer-carrier">{{ $t('Carrier') }}</Label>
                    <Select v-model="form.carrier_party_id">
                        <SelectTrigger id="trailer-carrier" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('Own fleet') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in carriers"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.carrier_party_id" />
                </div>
            </form>

            <SheetFooter>
                <Button variant="outline" type="button" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-trailer"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
