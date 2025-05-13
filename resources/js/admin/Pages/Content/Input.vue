<template>
    <div class="flex flex-col gap-4">
        <CardTransparent :title="pageTitle">
            <form @submit.prevent="submitForm" class="mb-0">
                <div class="flex flex-col gap-2">
                    <div>
                        <label for="title">
                            {{ contentType.title_aliases ?? $t("field.title") }}
                        </label>
                        <input
                            type="text"
                            class="form"
                            id="title"
                            :placeholder="
                                contentType.title_aliases ?? $t('field.title')
                            "
                            :class="{
                                'is-invalid': form.errors.title,
                            }"
                            v-model="form.title"
                        />
                        <span class="form-feedback">
                            {{ form.errors.title }}
                        </span>
                    </div>
                    <div v-for="(cf, idx) in contentType.content_fields">
                        <label :for="'field' + idx">{{ cf.name }}</label>
                        <input
                            v-if="
                                cf.field_type === 'text' ||
                                cf.field_type === 'number' ||
                                cf.field_type === 'date'
                            "
                            :type="cf.field_type"
                            class="form"
                            :id="'field' + idx"
                            :placeholder="cf.name"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                            v-model="form.content_fields[idx].field_value.value"
                        />
                        <input
                            v-if="cf.field_type === 'hyperlink'"
                            type="text"
                            class="form"
                            :id="'field' + idx"
                            :placeholder="cf.name"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                            v-model="form.content_fields[idx].field_value.value"
                        />
                        <input
                            v-if="cf.field_type === 'image'"
                            type="file"
                            class="form"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                            :id="'field' + idx"
                            accept="image/*"
                            @input="addFiles($event, idx)"
                        />
                        <input
                            v-if="cf.field_type === 'file'"
                            type="file"
                            class="form"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                            :id="'field' + idx"
                            accept=".pdf"
                            @input="addFiles($event, idx)"
                        />
                        <textarea
                            v-if="cf.field_type === 'textarea'"
                            class="form"
                            :id="'field' + idx"
                            :placeholder="cf.name"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                            v-model="form.content_fields[idx].field_value.value"
                        />
                        <QuillEditor
                            v-if="cf.field_type === 'wysiwyg'"
                            v-model="form.content_fields[idx].field_value.value"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `content_fields.${idx}.field_value.value`
                                    ],
                            }"
                        ></QuillEditor>
                        <span class="form-feedback">{{
                            form.errors[
                                `content_fields.${idx}.field_value.value`
                            ]
                        }}</span>
                        <span
                            v-if="cf.validation.dimension"
                            class="text-xs block"
                        >
                            {{
                                $t("message.dimension", {
                                    value:
                                        cf.validation.dimension.width +
                                        "x" +
                                        cf.validation.dimension.height,
                                })
                            }}
                        </span>
                        <span v-if="cf.validation.ratio" class="text-xs block">
                            {{
                                $t("message.ratioFormat", {
                                    value: cf.validation.ratio,
                                })
                            }}
                        </span>
                        <span
                            v-if="
                                cf.field_type === 'image' ||
                                cf.field_type === 'file'
                            "
                            class="text-xs"
                        >
                            {{ $t("message.imageReplace") }}
                        </span>
                        <img
                            v-if="
                                cf.field_type === 'image' &&
                                cf.field_value?.src != null
                            "
                            :src="cf.field_value?.src"
                            class="h-32 mt-2"
                            :alt="cf.name"
                        />
                    </div>
                    <div v-if="contentType.with_meta">
                        <div class="text-lg font-bold">Meta Data</div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="meta_title">
                                    {{ $t("field.title") }}
                                </label>
                                <input
                                    type="text"
                                    class="form"
                                    id="meta_title"
                                    placeholder="Meta Title"
                                    :class="{
                                        'is-invalid': form.errors.meta_title,
                                    }"
                                    v-model="form.meta_title"
                                />
                                <span class="form-feedback">{{
                                    form.errors.meta_title
                                }}</span>
                            </div>
                            <div>
                                <label for="meta_keyword">
                                    {{ $t("field.keyword") }}
                                </label>
                                <input
                                    type="text"
                                    class="form"
                                    id="meta_keyword"
                                    placeholder="Meta Keyword"
                                    :class="{
                                        'is-invalid': form.errors.meta_keyword,
                                    }"
                                    v-model="form.meta_keyword"
                                />
                                <span class="form-feedback">{{
                                    form.errors.meta_keyword
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <label for="meta_description">{{
                                    $t("field.description")
                                }}</label>
                                <textarea
                                    type="text"
                                    class="form form-sm"
                                    id="meta_description"
                                    placeholder="Meta Description"
                                    :class="{
                                        'is-invalid':
                                            form.errors.meta_description,
                                    }"
                                    v-model="form.meta_description"
                                ></textarea>
                                <span class="form-feedback">{{
                                    form.errors.meta_description
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row justify-between">
                        <div>
                            <button
                                v-if="contentType.is_listed"
                                type="button"
                                class="btn btn-danger"
                                @click="
                                    modalStore.openModalDelete(
                                        route('admin.contents.delete', {
                                            content_type: contentType.id,
                                            content: contentType.content.id,
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
                </div>
            </form>
        </CardTransparent>
        <hr />
        <Listed v-for="c in children" :content-type="c" />
    </div>
</template>
<script setup>
import { useForm } from "@inertiajs/vue3";
import Listed from "./Listed.vue";
import QuillEditor from "@admin/Components/Form/QuillEditor.vue";
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import { useModalStore } from "@admin/store/modal";

const props = defineProps({
    contentType: Object,
    children: Array,
});

const modalStore = useModalStore();

const pageTitle = "Section " + props.contentType.name;

const form = useForm({
    id: props.contentType.content?.id ?? null,
    title: props.contentType.content?.title ?? null,
    content_fields: props.contentType.content_fields ?? null,
    meta_title: props.contentType.content?.meta_title ?? null,
    meta_keyword: props.contentType.content?.meta_keyword ?? null,
    meta_description: props.contentType.content?.meta_description ?? null,
});

form.content_fields.forEach((field, i) => {
    form.content_fields[i].field_value = field.field_value ?? {
        value: null,
    };

    if (field.field_type == "image" || field.field_type == "file") {
        form.content_fields[i].field_value.oldValue = field.field_value.value;
        form.content_fields[i].field_value.value = null;
    }
});

const addFiles = (event, idx) => {
    form.content_fields[idx].field_value.value = event.target.files[0];
};

const submitForm = () =>
    !props.contentType.content?.id
        ? form.post(
              route("admin.contents.store", {
                  content_type: props.contentType.id,
              }),
              {
                  onSuccess: resetInputFile,
              }
          )
        : form.post(
              route("admin.contents.update", {
                  content_type: props.contentType.id,
                  content: props.contentType.content.id,
              }),
              {
                  onSuccess: resetInputFile,
              }
          );

const resetInputFile = () => {
    form.content_fields.forEach((field, i) => {
        if (field.field_type == "image" || field.field_type == "file") {
            console.log(field.field_type);
            form.content_fields[i].field_value.value = null;
        }
    });
};
</script>
