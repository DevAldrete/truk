<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import DocumentForm from '@/components/catalog/DocumentForm.vue';
import DocumentRow from '@/components/catalog/DocumentRow.vue';
import { Button } from '@/components/ui/button';
import type { ComplianceDocument, Option } from '@/types';

defineProps<{
    documents: ComplianceDocument[];
    types: Option[];
    storeUrl: string;
    canManage: boolean;
    updateUrl: (document: ComplianceDocument) => string;
    destroyUrl: (document: ComplianceDocument) => string;
}>();

const adding = ref(false);
</script>

<template>
    <section class="mt-8 border-t">
        <header class="flex items-center justify-between gap-2 px-6 py-3">
            <h3 class="text-sm font-semibold">
                {{ $t('Compliance documents') }}
            </h3>

            <Button
                v-if="canManage && !adding"
                variant="outline"
                size="sm"
                data-test="add-document"
                @click="adding = true"
            >
                <Plus class="size-4" />
                {{ $t('Add document') }}
            </Button>
        </header>

        <div v-if="adding" class="px-6 pb-3">
            <DocumentForm
                :types="types"
                :submit-url="storeUrl"
                method="post"
                @saved="adding = false"
                @cancel="adding = false"
            />
        </div>

        <ul>
            <DocumentRow
                v-for="document in documents"
                :key="document.id"
                :document="document"
                :types="types"
                :can-manage="canManage"
                :update-url="updateUrl(document)"
                :destroy-url="destroyUrl(document)"
            />

            <li
                v-if="documents.length === 0"
                class="px-6 py-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('No documents yet.') }}
            </li>
        </ul>
    </section>
</template>
