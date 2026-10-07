<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

withDefaults(
    defineProps<{
        id: string;
        error?: string;
        disabled?: boolean;
    }>(),
    { disabled: false },
);

const model = defineModel<string>({ required: true });

/**
 * Common SICT / NOM-012 configuration codes. The field stays free text because
 * the catalog is long and changes, but these cover the usual tractor/trailer
 * combinations.
 */
const codes = [
    'C2',
    'C3',
    'C4',
    'T2-S1',
    'T2-S2',
    'T3-S2',
    'T3-S2-R4',
    'S1',
    'S2',
    'S3',
    'R2',
    'R3',
];
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">{{ $t('Configuration (SAT)') }}</Label>
        <Input
            :id="id"
            v-model="model"
            :list="`${id}-codes`"
            :disabled="disabled"
            placeholder="T3-S2-R4"
        />
        <datalist :id="`${id}-codes`">
            <option v-for="code in codes" :key="code" :value="code" />
        </datalist>
        <p class="text-xs text-muted-foreground">
            {{
                $t(
                    'The SICT/NOM-012 vehicle configuration, for example C2, C3, or T3-S2-R4.',
                )
            }}
        </p>
        <InputError :message="error" />
    </div>
</template>
