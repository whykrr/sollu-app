<template>
    <Container>
        <div class="bg-white rounded-lg p-2">
            <Form />
        </div>
    </Container>
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
                <label for="name">Name</label>
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
                <label for="role">Role</label>
                <select
                    id="role"
                    class="form"
                    :class="{
                        'is-invalid': form.errors.role,
                    }"
                    v-model="form.role"
                >
                    <option value="">--Pilih--</option>
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
                                route('dashboard.admin.users.destroy', {
                                    user: props.user.id,
                                })
                            )
                        "
                    >
                        Hapus
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-danger"
                        @click="
                            modalStore.openModalDelete(
                                route(
                                    'dashboard.admin.users.destroy.permanent',
                                    {
                                        user: props.user.id,
                                    }
                                )
                            )
                        "
                    >
                        Hapus Permanen
                    </button>
                </div>
                <div>
                    <button
                        v-if="props.user?.deleted_at == null"
                        type="submit"
                        class="btn btn-main"
                    >
                        Simpan
                    </button>
                    <Link
                        v-else
                        :href="
                            route('dashboard.admin.users.restore', {
                                user: props.user.id,
                            })
                        "
                        class="btn btn-main mr-2"
                        method="put"
                        as="button"
                    >
                        Kembalikan
                    </Link>
                </div>
            </div>
        </form>
    </CardTransparent>
</template>
<script setup>
import CardTransparent from "@/Components/Dashboard/Cards/CardTransparent.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { useModalStore } from "@/store/Dashboard/modal";
import { Link, router, useForm } from "@inertiajs/vue3";
import Form from "./Components/Form.vue";

const modalStore = useModalStore();
const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user?.name ?? null,
    email: props.user?.email ?? null,
    role: props.user?.role ?? "",
});

const submitData = () => {
    if (props.user) {
        form.put(
            route("dashboard.admin.users.update", { user: props.user.id })
        );
    } else {
        form.post(route("dashboard.admin.users.store"));
    }
};
</script>
