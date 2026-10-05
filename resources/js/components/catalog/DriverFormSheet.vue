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
import { store } from '@/routes/drivers';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    carriers: Option[];
}>();

const open = ref(false);

const form = useForm({
    name: '',
    phone: '',
    license_number: '',
    license_expires_at: '',
    carrier_party_id: 'none',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
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
            <Button size="sm" data-test="new-driver">
                <Plus class="size-4" />
                {{ $t('New driver') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ $t('New driver') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('The drivers that operate your fleet.') }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="driver-name">{{ $t('Name') }}</Label>
                    <Input id="driver-name" v-model="form.name" v-focus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="driver-phone">{{ $t('Phone') }}</Label>
                    <Input id="driver-phone" v-model="form.phone" />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="driver-license-number">
                        {{ $t('Licence number') }}
                    </Label>
                    <Input
                        id="driver-license-number"
                        v-model="form.license_number"
                        class="uppercase"
                    />
                    <InputError :message="form.errors.license_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="driver-license-expires">
                        {{ $t('Licence expires') }}
                    </Label>
                    <Input
                        id="driver-license-expires"
                        v-model="form.license_expires_at"
                        type="date"
                    />
                    <InputError :message="form.errors.license_expires_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="driver-carrier">{{ $t('Carrier') }}</Label>
                    <Select v-model="form.carrier_party_id">
                        <SelectTrigger id="driver-carrier" class="w-full">
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
                    data-test="save-driver"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
