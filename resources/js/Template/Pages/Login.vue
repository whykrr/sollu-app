<template>
    <div class="m-auto">
        <div
            class="flex flex-col p-8 gap-2 bg-white rounded-lg w-[450px] shadow-lg"
        >
            <div class="text-3xl font-semibold">Login</div>
            <div>Sign In to your account</div>
            <div class="form-floating">
                <input
                    type="email"
                    id="name"
                    placeholder="Email"
                    required
                    class="form bg-white"
                />
                <label for="name">Email</label>
            </div>
            <div class="form-floating">
                <input
                    type="password"
                    id="name"
                    placeholder="Password"
                    required
                    class="form bg-white"
                />
                <label for="name">Password</label>
            </div>
            <div class="flex flex-row justify-between items-center mt-2">
                <button class="btn btn-main">Login</button>
                <div class="text-blue-800 underline">
                    <Link href="#">Forget password</Link>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import BlankLayout from "@/Layout/BlankLayout.vue";
import { Link } from "@inertiajs/vue3";

defineOptions({
    layout: BlankLayout,
});
</script>
