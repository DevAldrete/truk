<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { Paginated } from '@/types';

defineProps<{
    title: string;
    subtitle: string;
    description?: string;
    paginator: Paginated<unknown>;
    /**
     * Name of the page prop that holds this paginator (e.g. "parties"). The
     * server wraps that prop in `Inertia::scroll()`, which is what lets the
     * InfiniteScroll component page through it.
     */
    data: string;
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

        <InfiniteScroll
            :data="data"
            only-next
            manual
            as="ul"
            class="min-h-0 flex-1 overflow-y-auto"
        >
            <slot />

            <li
                v-if="paginator.data.length === 0"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                <slot name="empty" />
            </li>

            <template #next="{ loading, hasMore, fetch }">
                <div
                    v-if="paginator.data.length > 0"
                    class="flex items-center justify-between gap-2 border-t px-3 py-2 text-xs text-muted-foreground"
                >
                    <span data-test="list-count">
                        {{
                            $t('Showing :loaded of :total', {
                                loaded: paginator.data.length,
                                total: paginator.total,
                            })
                        }}
                    </span>

                    <Button
                        v-if="hasMore"
                        size="sm"
                        variant="outline"
                        :disabled="loading"
                        data-test="load-more"
                        @click="fetch"
                    >
                        <Spinner v-if="loading" />
                        {{ loading ? $t('Loading…') : $t('Load more') }}
                    </Button>
                    <span v-else data-test="list-end">
                        {{ $t('End of list') }}
                    </span>
                </div>
            </template>
        </InfiniteScroll>
    </section>
</template>
