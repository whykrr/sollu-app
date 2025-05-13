<template>
    <div class="table-responsive">
        <table class="table table-hovered">
            <thead>
                <tr>
                    <td width="1%">#</td>
                    <td width="15%">{{ $t("field.name") }}</td>
                    <td width="65%">{{ $t("table.subject") }}</td>
                    <td width="1%"></td>
                </tr>
            </thead>
            <tbody class="small">
                <tr class="h-2"></tr>
                <tr
                    v-for="message in messages"
                    class="text-nowrap"
                    :key="message.id"
                    :class="{
                        'font-semibold': message.status === 'unread',
                    }"
                >
                    <td>
                        <input
                            type="checkbox"
                            class="form"
                            :value="message.id"
                        />
                    </td>
                    <td
                        @click="
                            router.get(
                                route('admin.message.show', {
                                    message: message.id,
                                })
                            )
                        "
                    >
                        {{ message.name }}
                    </td>
                    <td
                        @click="
                            router.get(
                                route('admin.message.show', {
                                    message: message.id,
                                })
                            )
                        "
                    >
                        {{ message.subject }}
                        <div class="text-gray-400 text-sm font-thin inline">
                            {{
                                message.message.substring(
                                    0,
                                    80 - message.subject.length
                                )
                            }}
                            <span
                                v-if="
                                    message.message.length >
                                    80 - message.subject.length
                                "
                                >...</span
                            >
                        </div>
                    </td>
                    <td>
                        {{ message.created_at }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
    messages: Array,
});
</script>
