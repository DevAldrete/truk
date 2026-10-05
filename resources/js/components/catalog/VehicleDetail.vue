<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import ComplianceDocsSection from '@/components/catalog/ComplianceDocsSection.vue';
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
import { destroy, update } from '@/routes/vehicles';
import {
    destroy as destroyDocument,
    store as storeDocument,
    update as updateDocument,
} from '@/routes/vehicles/documents';
import type { Option, Vehicle } from '@/types';

const props = defineProps<{
    vehicle: Vehicle;
    teamSlug: string;
    carriers: Option[];
    documentTypes: Option[];
    canManage: boolean;
}>();

const form = useForm({
    name: props.vehicle.name,
    plate: props.vehicle.plate,
    configuration: props.vehicle.configuration,
    max_payload_kg: String(props.vehicle.max_payload_kg),
    max_volume_m3:
        props.vehicle.max_volume_m3 === null
            ? ''
            : String(props.vehicle.max_volume_m3),
    carrier_party_id: props.vehicle.carrier_party_id
        ? String(props.vehicle.carrier_party_id)
        : 'none',
});

const save = () => {
    form.transform((data) => ({
        ...data,
        max_volume_m3: data.max_volume_m3 === '' ? null : data.max_volume_m3,
        carrier_party_id:
            data.carrier_party_id === 'none' ? null : data.carrier_party_id,
    }));

    form.patch(
        update.url({
            current_team: props.teamSlug,
            vehicle: props.vehicle.id,
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
                    {{ vehicle.name }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ vehicle.plate }} · {{ vehicle.configuration }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ vehicle.max_payload_kg }} kg
                    <template v-if="vehicle.max_volume_m3 !== null">
                        · {{ vehicle.max_volume_m3 }} m³
                    </template>
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="
                    destroy.url({
                        current_team: teamSlug,
                        vehicle: vehicle.id,
                    })
                "
                :title="$t('Delete :name?', { name: vehicle.name })"
                :description="
                    $t('The vehicle will stop appearing in the lists.')
                "
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-6" @submit.prevent="save">
                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-name">{{ $t('Name') }}</Label>
                    <Input
                        id="vehicle-name"
                        v-model="form.name"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-plate">{{ $t('Plate') }}</Label>
                    <Input
                        id="vehicle-plate"
                        v-model="form.plate"
                        class="uppercase"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.plate" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-configuration">
                        {{ $t('Configuration') }}
                    </Label>
                    <Input
                        id="vehicle-configuration"
                        v-model="form.configuration"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.configuration" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-carrier">{{ $t('Carrier') }}</Label>
                    <Select
                        v-model="form.carrier_party_id"
                        :disabled="!canManage"
                    >
                        <SelectTrigger id="vehicle-carrier" class="w-full">
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

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-payload">
                        {{ $t('Payload') }} (kg)
                    </Label>
                    <Input
                        id="vehicle-payload"
                        v-model="form.max_payload_kg"
                        type="number"
                        step="0.001"
                        min="0"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.max_payload_kg" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="vehicle-volume">{{ $t('Volume') }} (m³)</Label>
                    <Input
                        id="vehicle-volume"
                        v-model="form.max_volume_m3"
                        type="number"
                        step="0.001"
                        min="0"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.max_volume_m3" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-6"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-vehicle-changes"
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

            <ComplianceDocsSection
                :documents="vehicle.documents ?? []"
                :types="documentTypes"
                :store-url="
                    storeDocument.url({
                        current_team: teamSlug,
                        vehicle: vehicle.id,
                    })
                "
                :can-manage="canManage"
                :update-url="
                    (document) =>
                        updateDocument.url({
                            current_team: teamSlug,
                            vehicle: vehicle.id,
                            document: document.id,
                        })
                "
                :destroy-url="
                    (document) =>
                        destroyDocument.url({
                            current_team: teamSlug,
                            vehicle: vehicle.id,
                            document: document.id,
                        })
                "
            />
        </div>
    </div>
</template>
