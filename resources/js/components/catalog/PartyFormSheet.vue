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
import { store } from '@/routes/parties';
import type { Option } from '@/types';

const props = defineProps<{
    teamSlug: string;
    types: Option[];
}>();

const open = ref(false);

const form = useForm({
    type: props.types[0]?.value ?? 'customer',
    name: '',
    legal_name: '',
    rfc: '',
    email: '',
    phone: '',
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
            <Button size="sm" data-test="new-party">
                <Plus class="size-4" />
                {{ $t('New party') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ $t('New party') }}</SheetTitle>
                <SheetDescription>
                    {{
                        $t(
                            'The customers, carriers, and suppliers you work with.',
                        )
                    }}
                </SheetDescription>
            </SheetHeader>

            <form class="grid gap-4 px-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="party-type">{{ $t('Type') }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="party-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="party-name">{{ $t('Name') }}</Label>
                    <Input id="party-name" v-model="form.name" v-focus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="party-legal-name">{{ $t('Legal name') }}</Label>
                    <Input id="party-legal-name" v-model="form.legal_name" />
                    <InputError :message="form.errors.legal_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="party-rfc">{{ $t('RFC') }}</Label>
                    <Input
                        id="party-rfc"
                        v-model="form.rfc"
                        class="uppercase"
                        maxlength="13"
                        placeholder="XAXX010101000"
                    />
                    <InputError :message="form.errors.rfc" />
                </div>

                <div class="grid gap-2">
                    <Label for="party-email">{{ $t('Email') }}</Label>
                    <Input id="party-email" v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="party-phone">{{ $t('Phone') }}</Label>
                    <Input id="party-phone" v-model="form.phone" />
                    <InputError :message="form.errors.phone" />
                </div>
            </form>

            <SheetFooter>
                <Button variant="outline" type="button" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-party"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
