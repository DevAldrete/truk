<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import OrderItemsEditor from '@/components/catalog/OrderItemsEditor.vue';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/orders';
import type { Option, OrderLineInput } from '@/types';

const props = defineProps<{
    teamSlug: string;
    customers: Option[];
}>();

const open = ref(false);

const emptyLine = (): OrderLineInput => ({
    description: '',
    quantity: 1,
    unit: 'piece',
    weight_kg: '',
    volume_m3: '',
    hazmat: false,
});

const form = useForm({
    customer_party_id: 'none',
    status: 'draft',
    currency: 'MXN',
    requested_pickup_at: '',
    requested_delivery_at: '',
    notes: '',
    items: [emptyLine()] as OrderLineInput[],
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        customer_party_id:
            data.customer_party_id === 'none' ? null : data.customer_party_id,
        requested_pickup_at: data.requested_pickup_at || null,
        requested_delivery_at: data.requested_delivery_at || null,
        items: data.items.map((item) => ({
            ...item,
            quantity: Number(item.quantity),
        })),
    }));

    form.post(store.url({ current_team: props.teamSlug }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button size="sm" data-test="new-order">
                <Plus class="size-4" />
                {{ $t('New order') }}
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 overflow-y-auto sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>{{ $t('New order') }}</SheetTitle>
                <SheetDescription>
                    {{ $t('A commercial request to move goods.') }}
                </SheetDescription>
            </SheetHeader>

            <form
                class="grid gap-4 px-4 py-4 sm:grid-cols-2"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="order-customer">{{ $t('Customer') }}</Label>
                    <Select v-model="form.customer_party_id">
                        <SelectTrigger id="order-customer" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">
                                {{ $t('No customer') }}
                            </SelectItem>
                            <SelectItem
                                v-for="item in customers"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.customer_party_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-status">{{ $t('Status') }}</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="order-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">
                                {{ $t('Draft') }}
                            </SelectItem>
                            <SelectItem value="confirmed">
                                {{ $t('Confirmed') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-pickup">
                        {{ $t('Requested pickup') }}
                    </Label>
                    <Input
                        id="order-pickup"
                        v-model="form.requested_pickup_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.requested_pickup_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="order-delivery">
                        {{ $t('Requested delivery') }}
                    </Label>
                    <Input
                        id="order-delivery"
                        v-model="form.requested_delivery_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.requested_delivery_at" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="order-notes">{{ $t('Notes') }}</Label>
                    <Textarea id="order-notes" v-model="form.notes" />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="sm:col-span-2">
                    <OrderItemsEditor
                        v-model="form.items"
                        :errors="form.errors"
                    />
                </div>
            </form>

            <SheetFooter>
                <Button variant="outline" type="button" @click="open = false">
                    {{ $t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    data-test="save-order"
                    @click="submit"
                >
                    {{ $t('Save') }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
