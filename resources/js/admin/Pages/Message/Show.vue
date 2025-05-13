<template>
    <Card :title="message.subject">
        <div class="text-xl">{{ message.name }}</div>
        <div class="text-sm text-gray-400 flex flex-row justify-between">
            <div><{{ message.email }}></div>
            <div>{{ message.created_at_full }}</div>
        </div>
        <p class="mt-2">{{ message.message }}</p>
        <hr class="my-2" />
        <div class="ml-4">
            <div class="font-semibold text-lg">Response</div>
            <form
                v-if="!message.response"
                @submit.prevent="submit"
                class="flex flex-col gap-2"
            >
                <textarea v-model="formResponse.message" class="form" />
                <div>
                    <button type="submit" class="btn btn-main">
                        <fa icon="fa-paper-plane" />
                        Sent
                    </button>
                </div>
            </form>
            <div v-else>
                <p>{{ message.response.message }}</p>
                <div class="text-sm text-gray-400">
                    {{ message.response.created_at }} by
                    {{ message.response.responder.name }}
                </div>
            </div>
        </div>
    </Card>
</template>
<script setup>
import Card from "@admin/Components/UI/Card.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    message: Object,
});

const formResponse = useForm({
    message: null,
});

const submit = () =>
    formResponse.post(
        route("admin.message.response", { message: props.message.id })
    );
</script>
