<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { destroy, update } from '@/routes/trips';
import { update as updateResources } from '@/routes/trips/resources';
import { t } from '@/lib/i18n';
import type { Option, TripDetail } from '@/types';

const props = defineProps<{
    trip: TripDetail;
    teamSlug: string;
    drivers: Option[];
    vehicles: Option[];
    trailers: Option[];
    statuses: Option[];
    canManage: boolean;
}>();

const timezones = [
    'America/Mexico_City',
    'America/Monterrey',
    'America/Chihuahua',
    'America/Mazatlan',
    'America/Hermosillo',
    'America/Tijuana',
    'America/Cancun',
];

const toLocalInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const form = useForm({
    status: props.trip.status,
    planned_start_at: toLocalInput(props.trip.planned_start_at),
    planned_end_at: toLocalInput(props.trip.planned_end_at),
    timezone: props.trip.timezone ?? 'America/Mexico_City',
    notes: props.trip.notes ?? '',
});

const save = () => {
    form.transform((data) => ({
        ...data,
        planned_start_at: data.planned_start_at || null,
        planned_end_at: data.planned_end_at || null,
    }));

    form.patch(update.url({ current_team: props.teamSlug, trip: props.trip.id }), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
};

const resources = useForm({
    driver_id: props.trip.driver_id ? String(props.trip.driver_id) : 'none',
    vehicle_id: props.trip.vehicle_id ? String(props.trip.vehicle_id) : 'none',
    trailer_id: props.trip.trailer_id ? String(props.trip.trailer_id) : 'none',
});

const saveResources = () => {
    resources.transform((data) => ({
        driver_id: data.driver_id === 'none' ? null : data.driver_id,
        vehicle_id: data.vehicle_id === 'none' ? null : data.vehicle_id,
        trailer_id: data.trailer_id === 'none' ? null : data.trailer_id,
    }));

    resources.put(
        updateResources.url({ current_team: props.teamSlug, trip: props.trip.id }),
        { preserveScroll: true, onSuccess: () => resources.defaults() },
    );
};

const hasResources = computed(
    () =>
        props.trip.driver_name ||
        props.trip.vehicle_name ||
        props.trip.trailer_name,
);

const resourceLabel = (resource: string): string =>
    resource === 'driver'
        ? t('Driver')
        : resource === 'vehicle'
          ? t('Vehicle')
          : t('Trailer');
</script>

<template>
    <div class="flex h-full flex-col">
        <header class="flex items-start justify-between gap-4 border-b px-6 py-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{ trip.number }}
                    </h2>
                    <span
                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ trip.status_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ trip.driver_name ?? $t('No driver') }}
                    <template v-if="trip.vehicle_name">
                        · {{ trip.vehicle_name }}
                    </template>
                </p>
            </div>

            <DeleteButton
                v-if="canManage"
                :url="destroy.url({ current_team: teamSlug, trip: trip.id })"
                :title="$t('Delete :name?', { name: trip.number })"
                :description="$t('The trip will stop appearing in the lists.')"
            />
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="trip-detail-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status" :disabled="!canManage">
                        <SelectTrigger id="trip-detail-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in statuses"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-detail-timezone">
                        {{ $t('Timezone') }}
                    </Label>
                    <Select v-model="form.timezone" :disabled="!canManage">
                        <SelectTrigger
                            id="trip-detail-timezone"
                            class="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="zone in timezones"
                                :key="zone"
                                :value="zone"
                            >
                                {{ zone }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.timezone" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-detail-start">
                        {{ $t('Planned start') }}
                    </Label>
                    <Input
                        id="trip-detail-start"
                        v-model="form.planned_start_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.planned_start_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="trip-detail-end">{{ $t('Planned end') }}</Label>
                    <Input
                        id="trip-detail-end"
                        v-model="form.planned_end_at"
                        type="datetime-local"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.planned_end_at" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="trip-detail-notes">{{ $t('Notes') }}</Label>
                    <Textarea
                        id="trip-detail-notes"
                        v-model="form.notes"
                        :disabled="!canManage"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div
                    v-if="form.isDirty"
                    class="sticky bottom-0 flex items-center gap-2 rounded-md border bg-background/95 px-3 py-2 backdrop-blur sm:col-span-2"
                >
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        data-test="save-trip-changes"
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

            <section class="mt-6 rounded-lg border p-4">
                <h3 class="text-sm font-semibold">{{ $t('Resources') }}</h3>

                <form
                    class="mt-3 grid gap-3 sm:grid-cols-3"
                    @submit.prevent="saveResources"
                >
                    <div class="grid gap-2">
                        <Label for="trip-driver">{{ $t('Driver') }}</Label>
                        <Select
                            v-model="resources.driver_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-driver" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in drivers"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.driver_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="trip-vehicle">{{ $t('Vehicle') }}</Label>
                        <Select
                            v-model="resources.vehicle_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-vehicle" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in vehicles"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.vehicle_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="trip-trailer">{{ $t('Trailer') }}</Label>
                        <Select
                            v-model="resources.trailer_id"
                            :disabled="!canManage"
                        >
                            <SelectTrigger id="trip-trailer" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ $t('None') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="item in trailers"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="resources.errors.trailer_id" />
                    </div>

                    <div
                        v-if="resources.isDirty"
                        class="flex items-center gap-2 sm:col-span-3"
                    >
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="resources.processing"
                            data-test="save-trip-resources"
                        >
                            {{ $t('Assign') }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resources.reset()"
                        >
                            {{ $t('Discard') }}
                        </Button>
                    </div>
                </form>

                <p
                    v-if="!hasResources"
                    class="mt-3 text-xs text-muted-foreground"
                >
                    {{ $t('No resources assigned yet.') }}
                </p>
            </section>

            <section v-if="trip.assignments.length" class="mt-6">
                <h3 class="text-sm font-semibold">
                    {{ $t('Assignment history') }}
                </h3>
                <ul class="mt-2 divide-y rounded-lg border">
                    <li
                        v-for="assignment in trip.assignments"
                        :key="assignment.id"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span>
                            {{ assignment.name }}
                            <span class="text-xs text-muted-foreground">
                                · {{ resourceLabel(assignment.resource) }}
                            </span>
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ assignment.released_at ? $t('Released') : $t('Active') }}
                        </span>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
