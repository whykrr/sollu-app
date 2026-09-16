<template>
    <div
        class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-indigo-100/80 relative z-10"
    >
        <div>
            <div class="flex justify-center items-center gap-2.5">
                <img src="/img/logo-colored.png" alt="Sollu" class="h-10 w-auto" />
                <span
                    class="text-xs uppercase font-extrabold px-2 py-0.5 bg-indigo-600 text-white rounded-md tracking-wider shadow-xs"
                >
                    Cockpit
                </span>
            </div>
            <h2 class="mt-6 text-center text-2xl font-extrabold text-neutral-900">
                Cockpit Control Center
            </h2>
            <p class="mt-1.5 text-center text-sm text-neutral-600">
                Sign in to access the platform management console
            </p>
        </div>
        <form class="mt-8 space-y-6" @submit.prevent="submit">
            <div class="rounded-md shadow-xs space-y-4">
                <EmailField
                    v-model="form.email"
                    label="Email address"
                    :error="form.errors.email"
                    placeholder="admin@sollu.id"
                    required
                />
                <PasswordField
                    v-model="form.password"
                    label="Password"
                    :error="form.errors.password"
                    placeholder="••••••••"
                    required
                />
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember-me"
                        v-model="form.remember"
                        name="remember-me"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-neutral-300 rounded"
                    />
                    <label for="remember-me" class="ml-2 block text-sm text-neutral-900">
                        Remember me
                    </label>
                </div>
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group relative w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                >
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <!-- Icon -->
                        <svg
                            class="h-5 w-5 text-indigo-200 group-hover:text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                    Sign in
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import EmailField from '@/Components/Form/EmailField.vue'
import PasswordField from '@/Components/Form/PasswordField.vue'
import AuthCockpitLayout from '@/Layout/AuthCockpitLayout.vue'

defineOptions({
    layout: AuthCockpitLayout,
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('cockpit.login.attempt'), {
        onFinish: () => form.reset('password'),
    })
}
</script>
