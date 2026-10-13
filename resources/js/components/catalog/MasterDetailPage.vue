<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';

defineProps<{
    selected: boolean;
    backHref: string;
}>();
</script>

<template>
    <div class="flex h-full min-h-0">
        <div
            :class="[
                'min-h-0 w-full min-w-0 flex-col lg:flex lg:w-auto lg:shrink-0',
                selected ? 'hidden' : 'flex',
            ]"
        >
            <slot name="list" />
        </div>

        <section
            :class="[
                'min-h-0 flex-1 overflow-y-auto',
                selected ? 'block' : 'hidden lg:block',
            ]"
        >
            <div v-if="selected" class="border-b px-4 py-2 lg:hidden">
                <Link
                    :href="backHref"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    data-test="detail-back"
                >
                    <ChevronLeft class="size-4" />
                    {{ $t('Back to list') }}
                </Link>
            </div>

            <slot name="detail" />
        </section>
    </div>
</template>
