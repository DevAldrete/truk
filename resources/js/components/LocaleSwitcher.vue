<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, Languages } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenuItem,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu';
import { update } from '@/routes/locale';

const page = usePage();

const locales = computed(() => page.props.availableLocales ?? []);
const currentLocale = computed(() => page.props.locale);

const selectLocale = (locale: string) => {
    router.post(
        update.url(),
        { locale },
        { preserveScroll: true, preserveState: false },
    );
};
</script>

<template>
    <DropdownMenuSub>
        <DropdownMenuSubTrigger>
            <Languages class="mr-2 h-4 w-4" />
            {{ $t('Language') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.value"
                class="cursor-pointer"
                data-test="locale-option"
                @click="selectLocale(locale.value)"
            >
                {{ locale.label }}
                <Check
                    v-if="currentLocale === locale.value"
                    class="ml-auto h-4 w-4"
                />
            </DropdownMenuItem>
        </DropdownMenuSubContent>
    </DropdownMenuSub>
</template>
