<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
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
import { destroy, update } from '@/routes/drivers';
import type { Driver, Option } from '@/types';

const props = defineProps<{
    driver: Driver;
    teamSlug: string;
    carriers: Option[];
    canManage: boolean;
}>();

const form = useForm({
    name: props.driver.name,
    phone: props.driver.phone,
    license_number: props.driver.license_number ?? '',
    license_expires_at: props.driver.license_expires_at ?? '',
    carrier_party_id: props.driver.carrier_party_id
        ? String(props.driver.carrier_party_id)
        : 'none',
});

const save = () => {
    form.transform((data) => ({
        ...data,
        carrier_party_id:
            data.carrier_party_id === 'none' ? null : data.carrier_party_id,
    }));

    form.patch(
        update.url({
            current_team: props.teamSlug,
            driver: props.driver.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        },
    );
};
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold">
                    {{ driver.name }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ driver.phone }}
                    <template v-if="driver.license_number">
                        · {{ driver.license_number }}
                    </template>
                </p>
                <p
                    v-if="driver.license_expires_at"
                    class="mt-1 text-xs"
                    :class="
                        driver.license_expired
                            ? 'font-medium text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ $t('Licence expires') }}
                    {{ driver.license_expires_at }}
                    <template v-if="driver.license_expired">
                        · {{ $t('Expired') }}
                    </template>
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="
                    destroy.url({
                        current_team: teamSlug,
                        driver: driver.id,
                    })
                "
                :title="$t('Delete :name?', { name: driver.name })"
                :description="$t('The driver will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-6" @submit.prevent="save">
                <div class="grid gap-2 sm:col-span-3">
                    <Label for="driver-name">{{ $t('Name') }}</Label>
                    <Input
                        id="driver-name"
                        v-model="form.name"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="driver-phone">{{ $t('Phone') }}</Label>
                    <Input
                        id="driver-phone"
                        v-model="form.phone"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="driver-license-number">
                        {{ $t('Licence number') }}
                    </Label>
                    <Input
                        id="driver-license-number"
                        v-model="form.license_number"
                        class="uppercase"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.license_number" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="driver-license-expires">
                        {{ $t('Licence expires') }}
                    </Label>
                    <Input
                        id="driver-license-expires"
                        v-model="form.license_expires_at"
                        type="date"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.license_expires_at" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="driver-carrier">{{ $t('Carrier') }}</Label>
                    <Select
                        v-model="form.carrier_party_id"
                        :disabled="!canManage"
                    >
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

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-6"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-driver-changes"
                    >
                        {{ $t('Save changes') }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="form.reset()"
                    >
                        {{ $t('Discard') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
