<template>
    <div class="m-auto">
        <form
            @submit.prevent="login"
            class="flex flex-col p-8 gap-2 bg-white rounded-lg w-[450px] shadow-lg"
        >
            <div class="text-3xl font-semibold">Login</div>
            <div>Sign In to your account</div>
            <div>
                <div
                    class="form-floating"
                    :class="{
                        'is-invalid': form.errors.email,
                    }"
                >
                    <input
                        type="text"
                        id="name"
                        placeholder="Email"
                        class="form bg-white"
                        v-model="form.email"
                    />
                    <label for="name">Email</label>
                </div>
                <span class="form-feedback">{{ form.errors.email }}</span>
            </div>
            <div>
                <div
                    class="form-floating"
                    :class="{
                        'is-invalid': form.errors.password,
                    }"
                >
                    <input
                        type="password"
                        id="name"
                        placeholder="Password"
                        class="form bg-white"
                        v-model="form.password"
                    />
                    <label for="name">Password</label>
                </div>
                <span class="form-feedback">{{ form.errors.password }}</span>
            </div>
            <div class="flex flex-row justify-between items-center mt-2">
                <button type="submit" class="btn btn-main">Login</button>
                <div class="text-blue-800 underline">
                    <Link href="#">Forget password</Link>
                </div>
            </div>
        </form>
    </div>
</template>
<script setup>
import BlankLayout from "@admin/Layout/BlankLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";

defineOptions({
    layout: BlankLayout,
});

const form = useForm({
    email: null,
    password: null,
});

const login = () => form.post(route("admin.login.attempt"));
</script>
