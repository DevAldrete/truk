<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
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
import { t } from '@/lib/i18n';
import type { OrderLineInput } from '@/types';

const model = defineModel<OrderLineInput[]>({ required: true });

defineProps<{
    errors?: Record<string, string>;
}>();

const units = [
    { value: 'piece', label: t('Piece') },
    { value: 'pallet', label: t('Pallet') },
    { value: 'box', label: t('Box') },
    { value: 'kilogram', label: t('Kilogram') },
    { value: 'tonne', label: t('Tonne') },
    { value: 'liter', label: t('Liter') },
];

const emptyLine = (): OrderLineInput => ({
    description: '',
    quantity: 1,
    unit: 'piece',
    weight_kg: '',
    volume_m3: '',
    hazmat: false,
});

const addLine = () => {
    model.value = [...model.value, emptyLine()];
};

const removeLine = (index: number) => {
    if (model.value.length <= 1) {
        return;
    }

    model.value = model.value.filter((_, i) => i !== index);
};
</script>

<template>
    <div class="grid gap-3">
        <div class="flex items-center justify-between">
            <Label>{{ $t('Goods') }}</Label>
            <Button
                type="button"
                variant="outline"
                size="sm"
                data-test="add-order-line"
                @click="addLine"
            >
                <Plus class="size-4" />
                {{ $t('Add line') }}
            </Button>
        </div>

        <div
            v-for="(line, index) in model"
            :key="index"
            class="grid gap-3 rounded-lg border p-3"
        >
            <div class="grid gap-2">
                <Label :for="`line-${index}-description`">
                    {{ $t('Description') }}
                </Label>
                <Input
                    :id="`line-${index}-description`"
                    v-model="line.description"
                    :placeholder="$t('What is being moved?')"
                />
                <InputError :message="errors?.[`items.${index}.description`]" />
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="grid gap-2">
                    <Label :for="`line-${index}-quantity`">
                        {{ $t('Quantity') }}
                    </Label>
                    <Input
                        :id="`line-${index}-quantity`"
                        v-model.number="line.quantity"
                        type="number"
                        min="1"
                    />
                </div>

                <div class="grid gap-2">
                    <Label :for="`line-${index}-unit`">{{ $t('Unit') }}</Label>
                    <Select v-model="line.unit">
                        <SelectTrigger
                            :id="`line-${index}-unit`"
                            class="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="unit in units"
                                :key="unit.value"
                                :value="unit.value"
                            >
                                {{ unit.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-2">
                    <Label :for="`line-${index}-weight`">
                        {{ $t('Weight (kg)') }}
                    </Label>
                    <Input
                        :id="`line-${index}-weight`"
                        v-model="line.weight_kg"
                        type="number"
                        step="0.001"
                        min="0"
                    />
                </div>

                <div class="grid gap-2">
                    <Label :for="`line-${index}-volume`">
                        {{ $t('Volume (m³)') }}
                    </Label>
                    <Input
                        :id="`line-${index}-volume`"
                        v-model="line.volume_m3"
                        type="number"
                        step="0.001"
                        min="0"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="line.hazmat"
                        type="checkbox"
                        class="size-4 rounded border-input"
                    />
                    {{ $t('Hazardous material') }}
                </label>

                <Button
                    v-if="model.length > 1"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground hover:text-destructive"
                    @click="removeLine(index)"
                >
                    <Trash2 class="size-4" />
                    {{ $t('Remove') }}
                </Button>
            </div>

            <InputError :message="errors?.[`items.${index}.quantity`]" />
        </div>

        <InputError :message="errors?.items" />
    </div>
</template>
