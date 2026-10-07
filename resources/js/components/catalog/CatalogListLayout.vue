<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

defineProps<{
    title: string;
    subtitle: string;
    description?: string;
    paginator: Paginated<unknown>;
    modelValue: string;
    placeholder: string;
    searchTest?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [value: string];
}>();

const onInput = (value: string | number) => {
    const text = String(value);
    emit('update:modelValue', text);
    emit('search', text);
};
</script>

<template>
    <section
        class="flex w-full min-w-0 flex-col border-r lg:w-[23rem] lg:shrink-0"
    >
        <header
            class="flex items-center justify-between gap-2 border-b px-4 py-3"
        >
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold">{{ title }}</h1>
                <p class="truncate text-xs text-muted-foreground">
                    {{ subtitle }}
                </p>
                <p
                    v-if="description"
                    class="mt-1 line-clamp-2 text-xs text-muted-foreground/80"
                >
                    {{ description }}
                </p>
            </div>

            <div class="shrink-0">
                <slot name="actions" />
            </div>
        </header>

        <div class="space-y-2 border-b p-3">
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 opacity-50"
                />
                <Input
                    :model-value="modelValue"
                    class="pl-8"
                    :placeholder="placeholder"
                    :data-test="searchTest"
                    @update:model-value="onInput"
                />
            </div>

            <slot name="filters" />
        </div>

        <ul class="min-h-0 flex-1 overflow-y-auto">
            <slot />

            <li
                v-if="paginator.data.length === 0"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                <slot name="empty" />
            </li>
        </ul>

        <footer
            v-if="paginator.last_page > 1"
            class="flex items-center justify-between border-t px-3 py-2 text-xs text-muted-foreground"
        >
            <span
                >{{ paginator.from }}–{{ paginator.to }} /
                {{ paginator.total }}</span
            >
            <div class="flex gap-1">
                <Button
                    v-for="link in paginator.links.filter(
                        (l) =>
                            l.label === '&laquo; Previous' ||
                            l.label === 'Next &raquo;',
                    )"
                    :key="link.label"
                    as-child
                    variant="ghost"
                    size="sm"
                    :disabled="!link.url"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                    >
                        {{ link.label.includes('Previous') ? '‹' : '›' }}
                    </Link>
                    <span v-else>
                        {{ link.label.includes('Previous') ? '‹' : '›' }}
                    </span>
                </Button>
            </div>
        </footer>
    </section>
</template>
