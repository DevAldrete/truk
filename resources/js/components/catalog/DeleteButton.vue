<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    url: string;
    title: string;
    description: string;
    label?: string;
    iconOnly?: boolean;
}>();

const open = ref(false);

const confirm = () => {
    router.delete(props.url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <Button
            v-if="iconOnly"
            variant="ghost"
            size="icon"
            class="size-7 text-muted-foreground hover:text-destructive"
            data-test="delete-record"
            @click="open = true"
        >
            <Trash2 class="size-3.5" />
        </Button>
        <Button
            v-else
            variant="outline"
            size="sm"
            class="text-muted-foreground hover:text-destructive"
            data-test="delete-record"
            @click="open = true"
        >
            <Trash2 class="size-4" />
            {{ label ?? $t('Delete') }}
        </Button>

        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    data-test="confirm-delete"
                    @click="confirm"
                >
                    {{ $t('Delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
