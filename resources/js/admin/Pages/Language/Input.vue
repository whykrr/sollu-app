<template>
    <CardTransparent :title="pageTitle">
        <template #buttons>
            <span
                v-if="props.user?.deleted_at != null"
                class="badge badge-danger"
                >Deleted</span
            >
        </template>
        <form
            @submit.prevent="submitData"
            class="grid grid-cols-2 gap-x-4 gap-y-2 mb-0"
        >
            <div>
                <label for="code">{{ $t("field.code") }}</label>
                <input
                    type="text"
                    class="form"
                    id="code"
                    placeholder="id"
                    :class="{
                        'is-invalid': form.errors.code,
                    }"
                    v-model="form.code"
                />
                <span class="form-feedback">{{ form.errors.code }}</span>
            </div>
            <div>
                <label for="name">{{ $t("field.name") }}</label>
                <input
                    type="text"
                    class="form"
                    id="name"
                    placeholder="Bahasa Indonesia"
                    :class="{
                        'is-invalid': form.errors.name,
                    }"
                    v-model="form.name"
                />
                <span class="form-feedback">{{ form.errors.name }}</span>
            </div>

            <div class="col-span-2 flex flex-1 justify-end">
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
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

const props = defineProps({
    language: Object,
});

const { t } = useI18n();
const pageTitle = !props.user
    ? t("action.create") + " " + t("page.language")
    : t("action.edit") + " " + t("page.language");

const form = useForm({
    code: props.language?.code ?? null,
    name: props.language?.name ?? null,
});

const submitData = () => {
    if (props.language) {
        form.put(
            route("admin.languages.update", { language: props.language.id })
        );
    } else {
        form.post(route("admin.languages.store"));
    }
};
</script>
