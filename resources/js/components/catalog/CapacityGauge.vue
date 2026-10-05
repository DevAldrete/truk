<script setup lang="ts">
import type { TripCapacity } from '@/types';

defineProps<{
    capacity: TripCapacity;
}>();
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">{{ $t('Capacity') }}</h3>
            <span
                v-if="capacity.over"
                class="rounded-full bg-destructive/10 px-2 py-0.5 text-xs text-destructive"
            >
                {{ $t('Over capacity') }}
            </span>
        </div>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <div
                    class="flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>{{ $t('Weight') }}</span>
                    <span>
                        {{ (capacity.weight_grams / 1000).toLocaleString() }} kg
                        <template v-if="capacity.weight_limit_grams">
                            /
                            {{
                                (
                                    capacity.weight_limit_grams / 1000
                                ).toLocaleString()
                            }}
                            kg
                        </template>
                    </span>
                </div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full"
                        :class="
                            capacity.over_weight
                                ? 'bg-destructive'
                                : 'bg-primary'
                        "
                        :style="{
                            width:
                                Math.min(
                                    capacity.weight_utilization ?? 0,
                                    100,
                                ) + '%',
                        }"
                    />
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        capacity.weight_utilization !== null
                            ? capacity.weight_utilization + '%'
                            : $t('No limit')
                    }}
                </p>
            </div>

            <div>
                <div
                    class="flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>{{ $t('Volume') }}</span>
                    <span>
                        {{ (capacity.volume_cm3 / 1000000).toLocaleString() }}
                        m³
                        <template v-if="capacity.volume_limit_cm3">
                            /
                            {{
                                (
                                    capacity.volume_limit_cm3 / 1000000
                                ).toLocaleString()
                            }}
                            m³
                        </template>
                    </span>
                </div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full"
                        :class="
                            capacity.over_volume
                                ? 'bg-destructive'
                                : 'bg-primary'
                        "
                        :style="{
                            width:
                                Math.min(
                                    capacity.volume_utilization ?? 0,
                                    100,
                                ) + '%',
                        }"
                    />
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        capacity.volume_utilization !== null
                            ? capacity.volume_utilization + '%'
                            : $t('No limit')
                    }}
                </p>
            </div>
        </div>

        <p class="mt-2 text-xs text-muted-foreground">
            {{
                $t(':count shipments', {
                    count: capacity.shipments_count,
                })
            }}
        </p>
    </div>
</template>
