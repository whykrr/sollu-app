<template>
    <Container>
        <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2 bg-white rounded-lg p-2 space-y-1">
                <div class="font-semibold">Detail</div>
                <div>
                    <TextField
                        id="name"
                        label="Nama Lengkap"
                        class="sm"
                        :class="{ 'is-invalid': form.errors.name }"
                        v-model="form.name"
                        :feedback="form.errors.name"
                    />
                </div>
                <div>
                    <EmailField
                        id="email"
                        label="Email"
                        class="sm"
                        :class="{ 'is-invalid': form.errors.email }"
                        v-model="form.email"
                        :feedback="form.errors.email"
                        :disabled="user"
                    />
                </div>
                <div>
                    <NumberField
                        id="phone"
                        label="Telepon"
                        class="sm"
                        :class="{ 'is-invalid': form.errors.phone }"
                        v-model="form.phone"
                        :feedback="form.errors.phone"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div class="bg-white rounded-lg p-2 space-y-1">
                    <div class="font-semibold">Peran</div>
                    <div class="flex flex-wrap gap-1">
                        <RadioButtonField
                            name="role"
                            :options="roles"
                            v-model="form.role"
                            class="sm btn-sm"
                            :feedback="form.errors.role"
                        />
                    </div>
                    <div class="text-danger text-xs select-none">
                        {{ form.errors.role }}
                    </div>
                </div>

                <div class="bg-white rounded-lg p-2 space-y-1">
                    <div class="font-semibold">Outlet</div>
                    <div class="flex flex-wrap gap-1">
                        <CheckboxButtonField
                            v-model="form.outlets"
                            :options="outlets"
                            name="outlets"
                            class="sm btn-sm"
                        />
                    </div>
                    <div class="text-danger text-xs select-none">
                        {{ form.errors.outlets }}
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-between">
                <div class="inline-flex gap-2">
                    <!-- <ButtonBack /> -->
                    <button
                        v-if="
                            user?.roles[0].name != 'owner' &&
                            user?.deleted_at === null
                        "
                        class="btn btn-warning btn-sm"
                        @click="
                            modal.openModalArchive(
                                route('dashboard.employees.destroy', {
                                    user: user.id,
                                })
                            )
                        "
                        aria-label="Hapus"
                    >
                        <FontAwesomeIcon :icon="faArchive" /> Arsipkan
                    </button>

                    <Link
                        as="button"
                        method="PUT"
                        v-if="user && user?.deleted_at !== null"
                        class="btn btn-success btn-sm"
                        :href="
                            route('dashboard.employees.restore', {
                                user: user.id,
                            })
                        "
                    >
                        <FontAwesomeIcon :icon="faArrowsRotate" />
                    </Link>

                    <button
                        method="delete"
                        v-if="user && user?.deleted_at != null"
                        class="btn btn-danger btn-sm"
                        @click="
                            modal.openModalDelete(
                                route('dashboard.employees.purge', {
                                    user: user.id,
                                })
                            )
                        "
                    >
                        <FontAwesomeIcon :icon="faTrash" /> Hapus
                    </button>
                </div>
                <button
                    v-if="!user || user?.deleted_at === null"
                    type="button"
                    @click="submitData"
                    class="btn btn-success btn-sm"
                >
                    Simpan
                </button>
            </div>
        </template>
    </Container>
</template>
<script setup>
import Container from "@/Components/Dashboard/UI/Container.vue";
import { useModalStore } from "@/store/Dashboard/modal";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faArchive,
    faArrowLeft,
    faArrowsRotate,
    faRecycle,
    faTrash,
    faXmark,
} from "@fortawesome/free-solid-svg-icons";
import TextField from "@/Components/Dashboard/Form/TextField.vue";
import EmailField from "@/Components/Dashboard/Form/EmailField.vue";
import NumberField from "@/Components/Dashboard/Form/NumberField.vue";
import RadioField from "@/Components/Dashboard/Form/RadioField.vue";
import RadioButtonField from "@/Components/Dashboard/Form/RadioButtonField.vue";
import CheckboxField from "@/Components/Dashboard/Form/CheckboxField.vue";
import CheckboxButtonField from "@/Components/Dashboard/Form/CheckboxButtonField.vue";
import ButtonBack from "@/Components/Dashboard/Button/ButtonBack.vue";

const modal = useModalStore();
const props = defineProps({
    returnTo: String,
    user: Object,
    roles: Array,
});

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const form = useForm({
    name: props.user?.name ?? null,
    email: props.user?.email ?? null,
    phone: props.user?.phone ?? null,
    role: props.user?.roles[0].name ?? "",
    outlets: props.user?.outlets?.map((outlet) => outlet.id) ?? [],
    return_url: props.returnTo,
});

const submitData = () => {
    if (props.user) {
        form.put(route("dashboard.employees.update", { user: props.user.id }));
    } else {
        form.post(route("dashboard.employees.store"));
    }
};
</script>
