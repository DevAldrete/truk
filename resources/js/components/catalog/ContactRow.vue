<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Pencil, Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, store, update } from '@/routes/parties/contacts';
import type { PartyContact } from '@/types';

const props = defineProps<{
    partyId: number;
    teamSlug: string;
    contact: PartyContact | null;
    canManage: boolean;
}>();

const emit = defineEmits<{ done: [] }>();

const editing = ref(props.contact === null);

const form = useForm({
    name: props.contact?.name ?? '',
    position: props.contact?.position ?? '',
    email: props.contact?.email ?? '',
    phone: props.contact?.phone ?? '',
});

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
            emit('done');
        },
    };

    if (props.contact) {
        form.patch(
            update.url({
                current_team: props.teamSlug,
                party: props.partyId,
                contact: props.contact.id,
            }),
            options,
        );

        return;
    }

    form.post(
        store.url({ current_team: props.teamSlug, party: props.partyId }),
        options,
    );
};
</script>

<template>
    <div
        v-if="!editing"
        class="group flex items-start gap-3 border-b py-2 last:border-0"
    >
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">
                {{ contact?.name }}
                <span
                    v-if="contact?.position"
                    class="font-normal text-muted-foreground"
                >
                    · {{ contact.position }}
                </span>
            </p>
            <p class="truncate text-xs text-muted-foreground">
                {{
                    [contact?.email, contact?.phone].filter(Boolean).join(' · ')
                }}
            </p>
        </div>

        <div
            v-if="canManage"
            class="flex items-center gap-1 opacity-0 transition group-hover:opacity-100"
        >
            <Button
                variant="ghost"
                size="icon"
                class="size-7 text-muted-foreground"
                data-test="edit-contact"
                @click="editing = true"
            >
                <Pencil class="size-3.5" />
            </Button>
            <DeleteButton
                icon-only
                :url="
                    destroy.url({
                        current_team: teamSlug,
                        party: partyId,
                        contact: contact!.id,
                    })
                "
                :title="$t('Delete contact')"
                :description="
                    $t('The contact will stop appearing in the directory.')
                "
            />
        </div>
    </div>

    <form
        v-else
        class="grid gap-2 border-b px-1 py-3 last:border-0"
        @submit.prevent="save"
    >
        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-1">
                <Input
                    v-model="form.name"
                    v-focus
                    :placeholder="$t('Name')"
                    data-test="contact-name"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-1">
                <Input v-model="form.position" :placeholder="$t('Position')" />
                <InputError :message="form.errors.position" />
            </div>
            <div class="grid gap-1">
                <Input
                    v-model="form.email"
                    type="email"
                    :placeholder="$t('Email')"
                />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-1">
                <Input v-model="form.phone" :placeholder="$t('Phone')" />
                <InputError :message="form.errors.phone" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Button
                type="submit"
                size="sm"
                :disabled="form.processing"
                data-test="save-contact"
            >
                <Plus class="size-3.5" />
                {{ $t('Save') }}
            </Button>
            <Button
                v-if="contact"
                type="button"
                variant="ghost"
                size="sm"
                @click="editing = false"
            >
                <X class="size-3.5" />
                {{ $t('Cancel') }}
            </Button>
        </div>
    </form>
</template>
