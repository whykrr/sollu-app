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
                <label for="name">{{ $t("field.name") }}</label>
                <input
                    type="text"
                    class="form"
                    id="name"
                    placeholder="Samuel"
                    :class="{
                        'is-invalid': form.errors.name,
                    }"
                    v-model="form.name"
                />
                <span class="form-feedback">{{ form.errors.name }}</span>
            </div>
            <div>
                <label for="email">Email</label>
                <input
                    type="text"
                    class="form"
                    id="email"
                    placeholder="samuel@example.com"
                    :class="{
                        'is-invalid': form.errors.email,
                    }"
                    v-model="form.email"
                />
                <span class="form-feedback">{{ form.errors.email }}</span>
            </div>
            <div>
                <label for="role">{{ $t("field.role") }}</label>
                <select
                    id="role"
                    class="form"
                    :class="{
                        'is-invalid': form.errors.role,
                    }"
                    v-model="form.role"
                >
                    <option value="">
                        {{ $t("form.select") }} {{ $t("field.role") }}
                    </option>
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="viewer">Viewer</option>
                </select>
                <span class="form-feedback">{{ form.errors.role }}</span>
            </div>

            <div class="col-span-2 flex flex-1 justify-between">
                <div v-if="props.user">
                    <button
                        v-if="props.user?.deleted_at == null"
                        type="button"
                        class="btn btn-danger"
                        @click="
                            modalStore.openModalSoftDelete(
                                route('admin.users.destroy', {
                                    user: props.user.id,
                                })
                            )
                        "
                    >
                        {{ $t("action.delete") }}
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-danger"
                        @click="
                            modalStore.openModalDelete(
                                route('admin.users.destroy.permanent', {
                                    user: props.user.id,
                                })
                            )
                        "
                    >
                        {{ $t("action.permanentDelete") }}
                    </button>
                </div>
                <div>
                    <button
                        v-if="props.user?.deleted_at == null"
                        type="submit"
                        class="btn btn-main"
                    >
                        {{ $t("action.submit") }}
                    </button>
                    <Link
                        v-else
                        :href="
                            route('admin.users.restore', {
                                user: props.user.id,
                            })
                        "
                        class="btn btn-main mr-2"
                        method="put"
                        as="button"
                    >
                        {{ $t("action.restore") }}
                    </Link>
                </div>
            </div>
        </form>
    </CardTransparent>
</template>
<script setup>
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import { useModalStore } from "@admin/store/modal";
import { Link, router, useForm } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

const modalStore = useModalStore();
const props = defineProps({
    user: Object,
});

const { t } = useI18n();
const pageTitle = !props.user
    ? t("action.create") + " " + t("page.user")
    : t("action.edit") + " " + t("page.user");

const form = useForm({
    name: props.user?.name ?? null,
    email: props.user?.email ?? null,
    role: props.user?.role ?? "",
});

const submitData = () => {
    if (props.user) {
        form.put(route("admin.users.update", { user: props.user.id }));
    } else {
        form.post(route("admin.users.store"));
    }
};
</script>
