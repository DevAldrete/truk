<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/catalog/DeleteButton.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { destroy, update } from '@/routes/locations';
import { show as showParty } from '@/routes/parties';
import type { Location, Option } from '@/types';

const props = defineProps<{
    location: Location;
    teamSlug: string;
    parties: Option[];
    canManage: boolean;
}>();

const form = useForm({
    party_id: props.location.party_id
        ? String(props.location.party_id)
        : 'none',
    name: props.location.name,
    street: props.location.street,
    exterior_number: props.location.exterior_number ?? '',
    interior_number: props.location.interior_number ?? '',
    neighborhood: props.location.neighborhood ?? '',
    city: props.location.city,
    state: props.location.state,
    postal_code: props.location.postal_code,
    references: props.location.references ?? '',
});

const save = () => {
    form.transform((data) => ({
        ...data,
        party_id: data.party_id === 'none' ? null : data.party_id,
    }));

    form.patch(
        update.url({
            current_team: props.teamSlug,
            location: props.location.id,
        }),
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
                    {{ location.name }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ location.street }}
                    <template v-if="location.exterior_number">
                        {{ location.exterior_number }}
                    </template>
                    · {{ location.city }}, {{ location.state }}
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="
                    destroy.url({
                        current_team: teamSlug,
                        location: location.id,
                    })
                "
                :title="$t('Delete :name?', { name: location.name })"
                :description="$t('The site will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-6" @submit.prevent="save">
                <div class="grid gap-2 sm:col-span-3">
                    <Label for="location-name">{{ $t('Name') }}</Label>
                    <Input
                        id="location-name"
                        v-model="form.name"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 sm:col-span-3">
                    <Label for="location-party">{{ $t('Party') }}</Label>
                    <Select v-model="form.party_id" :disabled="!canManage">
                        <SelectTrigger id="location-party" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('No party') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in parties"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.party_id" />
                </div>

                <div class="grid gap-2 sm:col-span-4">
                    <Label for="location-street">{{ $t('Street') }}</Label>
                    <Input
                        id="location-street"
                        v-model="form.street"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.street" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="location-exterior">{{ $t('No.') }}</Label>
                    <Input
                        id="location-exterior"
                        v-model="form.exterior_number"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.exterior_number" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="location-interior">{{ $t('Int.') }}</Label>
                    <Input
                        id="location-interior"
                        v-model="form.interior_number"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.interior_number" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="location-neighborhood">{{
                        $t('Neighborhood')
                    }}</Label>
                    <Input
                        id="location-neighborhood"
                        v-model="form.neighborhood"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.neighborhood" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="location-city">{{ $t('City') }}</Label>
                    <Input
                        id="location-city"
                        v-model="form.city"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.city" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="location-state">{{ $t('State') }}</Label>
                    <Input
                        id="location-state"
                        v-model="form.state"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.state" />
                </div>

                <div class="grid gap-2 sm:col-span-1">
                    <Label for="location-postal-code">{{ $t('Zip') }}</Label>
                    <Input
                        id="location-postal-code"
                        v-model="form.postal_code"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.postal_code" />
                </div>

                <div class="grid gap-2 sm:col-span-6">
                    <Label for="location-references">
                        {{ $t('Directions') }}
                    </Label>
                    <Textarea
                        id="location-references"
                        v-model="form.references"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.references" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-6"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-location-changes"
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

            <p
                v-if="location.party_name"
                class="mt-6 text-sm text-muted-foreground"
            >
                {{ $t('Belongs to') }}
                <Link
                    class="font-medium hover:underline"
                    :href="
                        showParty({
                            current_team: teamSlug,
                            party: location.party_id!,
                        })
                    "
                >
                    {{ location.party_name }}
                </Link>
            </p>
        </div>
    </div>
</template>
