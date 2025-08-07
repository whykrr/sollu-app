<template>
    <CardTransparent :title="$t('link.changePassword')">
        <form
            @submit.prevent="submitData"
            class="grid grid-cols-2 gap-x-4 gap-y-2 mb-0"
        >
            <div>
                <label for="old_password">{{ $t("field.newPassword") }}</label>
                <input
                    type="password"
                    class="form"
                    id="old_password"
                    :class="{
                        'is-invalid': form.errors.old_password,
                    }"
                    v-model="form.old_password"
                />
                <span class="form-feedback">{{
                    form.errors.old_password
                }}</span>
            </div>
            <div>
                <label for="password">{{ $t("field.oldPassword") }}</label>
                <input
                    type="password"
                    class="form"
                    id="password"
                    :class="{
                        'is-invalid': form.errors.password,
                    }"
                    v-model="form.password"
                />
                <span class="form-feedback">{{ form.errors.password }}</span>
            </div>
            <div>
                <label for="password_confirmation">{{
                    $t("field.passwordConfirmation")
                }}</label>
                <input
                    type="password"
                    class="form"
                    id="password_confirmation"
                    :class="{
                        'is-invalid': form.errors.password_confirmation,
                    }"
                    v-model="form.password_confirmation"
                />
                <span class="form-feedback">{{
                    form.errors.password_confirmation
                }}</span>
            </div>

            <div class="col-span-2 flex flex-1 ml-auto">
                <div>
                    <button type="submit" class="btn btn-main">
                        {{ $t("action.submit") }}
                    </button>
                </div>
            </div>
        </form>
    </CardTransparent>
</template>
<script setup>
import CardTransparent from "@/Components/Dashboard/Cards/CardTransparent.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    user: Object,
});

const form = useForm({
    old_password: "",
    password: "",
    password_confirmation: "",
});

const submitData = () => {
    form.post(
        route("dashboard.admin.change_password.store", {
            user: props.user.id,
        })
    );
};
</script>
