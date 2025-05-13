<template>
    <CardTransparent :title="pageTitle">
        <form @submit.prevent="submitData" class="grid grid-cols-2 gap-4 mb-0">
            <div>
                <label for="name">{{ $t("field.name") }}</label>
                <input
                    type="text"
                    class="form"
                    id="name"
                    placeholder="Hero"
                    :class="{
                        'is-invalid': form.errors.name,
                    }"
                    v-model="form.name"
                />
                <span class="form-feedback">{{ form.errors.name }}</span>
            </div>
            <div>
                <label for="description">{{ $t("field.description") }}</label>
                <textarea
                    class="form"
                    id="description"
                    placeholder="Some Description"
                    :class="{
                        'is-invalid': form.errors.description,
                    }"
                    v-model="form.description"
                />
                <span class="form-feedback">{{ form.errors.description }}</span>
            </div>
            <div v-if="parents.length != 0">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="have_parent"
                    v-model="form.have_parent"
                    @change="removeParent"
                />
                <label for="have_parent" class="form-check-label">{{
                    $t("field.haveParent")
                }}</label>

                <select
                    v-if="form.have_parent"
                    class="form mt-2"
                    :class="{
                        'is-invalid': form.errors.parent_id,
                    }"
                    v-model="form.parent_id"
                >
                    <option value="">Choose</option>
                    <option v-for="p in parents" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>
                <span v-if="form.have_parent" class="form-feedback">{{
                    form.errors.parent_id
                }}</span>
            </div>
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="is_listed"
                    v-model="form.is_listed"
                    :class="{ hidden: form.have_parent }"
                    :disabled="form.have_parent"
                />
                <label for="is_listed" class="form-check-label">{{
                    $t("field.multipleRow")
                }}</label>

                <input
                    v-if="form.is_listed"
                    type="text"
                    class="form mt-2"
                    placeholder="Maximum Row"
                    v-model="form.max_row"
                    :class="{
                        'is-invalid': form.errors.max_row,
                    }"
                />
                <span v-if="form.is_listed" class="form-feedback">{{
                    form.errors.max_row
                }}</span>
            </div>
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="with_meta"
                    v-model="form.with_meta"
                />
                <label for="with_meta" class="form-check-label">{{
                    $t("field.withMeta")
                }}</label>
            </div>
            <div class="col-span-2">
                <p class="text-sm">
                    {{ $t("message.defaultFieldTitle") }}
                </p>
                <div class="mb-2">
                    <label for="title_aliases">{{
                        $t("field.titleAlias")
                    }}</label>
                    <input
                        type="text"
                        class="form"
                        id="title_aliases"
                        :placeholder="$t('field.titleAlias')"
                        :class="{
                            'is-invalid': form.errors.title_aliases,
                        }"
                        v-model="form.title_aliases"
                    />
                    <span class="form-feedback">{{
                        form.errors.title_aliases
                    }}</span>
                </div>
                <FieldForm
                    :fields="form.fields"
                    :errors="form.errors"
                    :id-content-type="type?.id"
                />
                <div class="mt-2">
                    <button
                        @click="addField"
                        type="button"
                        class="btn btn-sm btn-main-light place-content-center w-full"
                    >
                        <fa icon="fa-plus"></fa>
                        {{ $t("action.addField") }}
                    </button>
                </div>
            </div>
            <div class="col-span-2 flex flex-1 justify-between">
                <div>
                    <button
                        v-if="props.type"
                        type="button"
                        class="btn btn-danger"
                        @click="
                            modalStore.openModalDelete(
                                route('admin.content-types.destroy', {
                                    content_type: props.type.id,
                                })
                            )
                        "
                    >
                        {{ $t("action.delete") }}
                    </button>
                </div>
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
import { Link, useForm, usePage } from "@inertiajs/vue3";
import FieldForm from "@admin/Pages/ContentType/Components/FieldForm.vue";
import { watch } from "vue";
import { useModalStore } from "@admin/store/modal";
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";

const page = usePage();
const modalStore = useModalStore();
const props = defineProps({
    type: Object,
    parents: Array,
});

const pageTitle = !props.type ? "Add Type" : "Edit Type";

const form = useForm({
    name: props.type?.name ?? null,
    description: props.type?.description ?? null,
    fields: props.type?.content_fields ?? [],
    is_listed: props.type?.is_listed ?? false,
    max_row: props.type?.max_row ?? null,
    have_parent: props.type?.parent_id ? true : false,
    parent_id: props.type?.parent_id ?? "",
    title_aliases: props.type?.title_aliases ?? null,
    with_meta: props.type?.with_meta ?? false,
});

watch(
    () => form.have_parent,
    (newValue) => {
        form.is_listed = newValue ? true : form.is_listed;
    }
);

const addField = () => {
    form.fields.push({
        name: "",
        field_type: "text",
        is_required: false,
        validation: {},
    });
};

const removeParent = () => (form.parent_id = "");

const submitData = () => {
    if (props.type) {
        form.put(
            route("admin.content-types.update", {
                content_type: props.type.id,
            })
        );
    } else {
        form.post(route("admin.content-types.store"));
    }
};
</script>
