<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ContactRow from '@/components/catalog/ContactRow.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy, update } from '@/routes/parties';
import { show as showLocation } from '@/routes/locations';
import type { Option, PartyDetail } from '@/types';

const props = defineProps<{
    party: PartyDetail;
    teamSlug: string;
    types: Option[];
    canManage: boolean;
}>();

const addingContact = ref(false);

const form = useForm({
    type: props.party.type,
    name: props.party.name,
    legal_name: props.party.legal_name ?? '',
    rfc: props.party.rfc ?? '',
    email: props.party.email ?? '',
    phone: props.party.phone ?? '',
});

const save = () => {
    form.patch(
        update.url({ current_team: props.teamSlug, party: props.party.id }),
        {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        },
    );
};
</script>

<template>
    <div class="flex h-full flex-col">
        <header
            class="flex items-start justify-between gap-4 border-b px-6 py-4"
        >
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold">
                    {{ party.name }}
                </h2>
                <div class="mt-1 flex items-center gap-2">
                    <Badge variant="secondary">{{ party.type_label }}</Badge>
                    <span
                        v-if="party.rfc"
                        class="text-xs text-muted-foreground"
                    >
                        {{ party.rfc }}
                    </span>
                </div>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="destroy.url({ current_team: teamSlug, party: party.id })"
                :title="$t('Delete :name?', { name: party.name })"
                :description="
                    $t(
                        'It will disappear from the lists. The history that references it is kept.',
                    )
                "
            />
        </header>

        <div class="flex-1 space-y-8 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="detail-type">{{ $t('Type') }}</Label>
                    <Select v-model="form.type" :disabled="!canManage">
                        <SelectTrigger id="detail-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="detail-name">{{ $t('Name') }}</Label>
                    <Input
                        id="detail-name"
                        v-model="form.name"
                        :disabled="!canManage"
                        data-test="party-name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="detail-legal-name">{{
                        $t('Legal name')
                    }}</Label>
                    <Input
                        id="detail-legal-name"
                        v-model="form.legal_name"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.legal_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="detail-rfc">{{ $t('RFC') }}</Label>
                    <Input
                        id="detail-rfc"
                        v-model="form.rfc"
                        class="uppercase"
                        maxlength="13"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.rfc" />
                </div>

                <div class="grid gap-2">
                    <Label for="detail-email">{{ $t('Email') }}</Label>
                    <Input
                        id="detail-email"
                        v-model="form.email"
                        type="email"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="detail-phone">{{ $t('Phone') }}</Label>
                    <Input
                        id="detail-phone"
                        v-model="form.phone"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.phone" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-2"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-party-changes"
                    >
                        {{ $t('Save changes') }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="form.reset()"
                    >
                        {{ $t('Discard') }}
                    </Button>
                </div>
            </form>

            <section>
                <header class="mb-1 flex items-center justify-between">
                    <h3 class="text-sm font-semibold">{{ $t('Contacts') }}</h3>
                    <Button
                        v-if="canManage && !addingContact"
                        variant="ghost"
                        size="sm"
                        data-test="add-contact"
                        @click="addingContact = true"
                    >
                        <Plus class="size-4" />
                        {{ $t('Add') }}
                    </Button>
                </header>

                <p
                    v-if="party.contacts.length === 0 && !addingContact"
                    class="py-2 text-sm text-muted-foreground"
                >
                    {{ $t('No contacts yet.') }}
                </p>

                <ContactRow
                    v-for="contact in party.contacts"
                    :key="contact.id"
                    :contact="contact"
                    :party-id="party.id"
                    :team-slug="teamSlug"
                    :can-manage="canManage"
                />

                <ContactRow
                    v-if="addingContact"
                    :key="'new'"
                    :contact="null"
                    :party-id="party.id"
                    :team-slug="teamSlug"
                    :can-manage="canManage"
                    @done="addingContact = false"
                />
            </section>

            <section>
                <h3 class="mb-1 text-sm font-semibold">
                    {{ $t('Locations') }}
                </h3>

                <p
                    v-if="party.locations.length === 0"
                    class="py-2 text-sm text-muted-foreground"
                >
                    {{ $t('No sites linked to this party.') }}
                </p>

                <ul class="divide-y">
                    <li
                        v-for="location in party.locations"
                        :key="location.id"
                        class="py-2"
                    >
                        <Link
                            class="text-sm font-medium hover:underline"
                            :href="
                                showLocation({
                                    current_team: teamSlug,
                                    location: location.id,
                                })
                            "
                        >
                            {{ location.name }}
                        </Link>
                        <p class="text-xs text-muted-foreground">
                            {{ location.city }}, {{ location.state }}
                        </p>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
