<script setup lang="ts">
import { Pencil } from '@lucide/vue';
import { ref } from 'vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import DocumentForm from '@/components/catalog/DocumentForm.vue';
import { Button } from '@/components/ui/button';
import type { ComplianceDocument, Option } from '@/types';

const props = defineProps<{
    document: ComplianceDocument;
    types: Option[];
    canManage: boolean;
    updateUrl: string;
    destroyUrl: string;
}>();

const editing = ref(false);
</script>

<template>
    <li class="border-b px-6 py-3">
        <DocumentForm
            v-if="editing"
            :types="types"
            :document="document"
            :submit-url="updateUrl"
            method="patch"
            @saved="editing = false"
            @cancel="editing = false"
        />

        <div v-else class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-sm font-medium">
                    {{ document.type_label }}
                    <span
                        v-if="document.expired"
                        class="rounded bg-destructive/10 px-1.5 py-0.5 text-[10px] font-medium text-destructive"
                    >
                        {{ $t('Expired') }}
                    </span>
                    <span
                        v-else-if="document.expiring_soon"
                        class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-medium text-amber-600"
                    >
                        {{ $t('Expires soon') }}
                    </span>
                    <span
                        v-else
                        class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                    >
                        {{ $t('Valid') }}
                    </span>
                </p>
                <p class="truncate text-xs text-muted-foreground">
                    <template v-if="document.number">
                        {{ document.number }} ·
                    </template>
                    <template v-if="document.expires_at">
                        {{ $t('Expires') }} {{ document.expires_at }}
                    </template>
                </p>
                <p
                    v-if="document.notes"
                    class="mt-1 truncate text-xs text-muted-foreground"
                >
                    {{ document.notes }}
                </p>
            </div>

            <div v-if="canManage" class="flex shrink-0 items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7 text-muted-foreground"
                    data-test="edit-document"
                    @click="editing = true"
                >
                    <Pencil class="size-3.5" />
                </Button>
                <DeleteButton
                    icon-only
                    :url="destroyUrl"
                    :title="$t('Delete this document?')"
                    :description="
                        $t('The document will stop appearing in the lists.')
                    "
                />
            </div>
        </div>
    </li>
</template>
