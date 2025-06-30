<template>
    <div
        class="h-screen w-full grid grid-cols-12 gap-4 p-8 bg-radial-[at_50%_75%] from-main via-secondary to-secondary-lighter"
    >
        <div class="col-span-6 max-h-full min-h-full">
            <div
                class="rounded-lg w-full h-full overflow-hidden shadow-xl relative"
            >
                <a
                    href="#"
                    class="btn bg-gray-800/25 rounded-full text-white absolute right-4 top-4"
                >
                    Back to website <fa icon="fa-arrow-right" />
                </a>
                <img
                    class="h-full w-full object-cover"
                    src="img/sollu-verse.png"
                    alt="banner"
                />
            </div>
        </div>
        <div class="col-span-6 mx-20">
            <form @submit.prevent="login">
                <div class="flex flex-col gap-8 min-h-full">
                    <div>
                        <img
                            class="h-[75px]"
                            src="img/logo-fit-white.png"
                            alt="banner"
                        />
                    </div>
                    <div class="grow">
                        <div class="flex flex-col gap-2">
                            <div class="text-xl text-white">
                                Login sekarang dan mulai kelola bisnismu dengan
                                mudah.
                            </div>
                            <!-- <div>Sign In to your account</div> -->
                            <div></div>
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
                                        class="form bg-white/40"
                                        v-model="form.email"
                                    />
                                    <label for="name">Email</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.email
                                }}</span>
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
                                        class="form bg-white/40"
                                        v-model="form.password"
                                    />
                                    <label for="name">Password</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.password
                                }}</span>
                            </div>
                            <div
                                class="flex flex-row justify-end items-center mt-2"
                            >
                                <div class="text-blue-800 underline">
                                    <Link href="#">Forget password</Link>
                                </div>
                            </div>
                            <div class="text-lg"></div>
                        </div>
                    </div>
                    <div>
                        <button
                            type="submit"
                            class="btn btn-main w-full !block"
                        >
                            Login
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
<script setup>
import BlankLayout from "@/Layout/BlankLayout.vue";
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
