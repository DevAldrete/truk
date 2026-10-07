<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store as storeMember } from '@/routes/teams/members';
import type { RoleOption, Team } from '@/types';

type Props = {
    team: Team;
    availableRoles: RoleOption[];
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const memberRole = ref('member');
const formKey = ref(0);

function handleOpenChange(value: boolean) {
    emit('update:open', value);

    if (!value) {
        memberRole.value = 'member';
        formKey.value++;
    }
}
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent>
            <Form
                :key="formKey"
                v-bind="storeMember.form(props.team.slug)"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="emit('update:open', false)"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        $t('Create a member account')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            $t(
                                'No email needed. The person logs in with the username you set.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="member-name">{{ $t('Name') }}</Label>
                        <Input
                            id="member-name"
                            name="name"
                            data-test="member-name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="member-username">
                            {{ $t('Username') }}
                        </Label>
                        <Input
                            id="member-username"
                            name="username"
                            data-test="member-username"
                            autocomplete="off"
                            placeholder="juan"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            {{
                                $t(
                                    'The login is the organization slug and this username, for example :example.',
                                    { example: `${team.slug}/juan` },
                                )
                            }}
                        </p>
                        <InputError
                            :message="errors.username ?? errors.login"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="member-password">
                            {{ $t('Temporary password') }}
                        </Label>
                        <PasswordInput
                            id="member-password"
                            name="password"
                            data-test="member-password"
                            autocomplete="new-password"
                            required
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="member-role">{{ $t('Role') }}</Label>
                        <Select
                            v-model="memberRole"
                            name="role"
                            data-test="member-role"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="$t('Select a role')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in props.availableRoles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.role" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">
                            {{ $t('Cancel') }}
                        </Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        data-test="member-submit"
                        :disabled="processing"
                    >
                        {{ $t('Create account') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
