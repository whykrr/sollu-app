<template>
    <div class="grid grid-cols-12 font-bold gap-4">
        <div class="col-span-6">{{ $t("field.field") }}</div>
        <div class="col-span-5">{{ $t("field.validation") }}</div>
    </div>
    <hr class="mb-2" />
    <div
        v-for="(field, idx) in fields"
        :key="idx"
        class="grid grid-cols-12 mb-2 gap-4"
    >
        <div class="col-span-6 flex flex-col gap-2">
            <div>
                <input
                    type="text"
                    class="form w-full"
                    placeholder="Field Name"
                    v-model="fields[idx].name"
                    :class="{
                        'is-invalid': errors[`fields.${idx}.name`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${idx}.name`]
                }}</span>
            </div>
            <div>
                <select
                    class="form w-full"
                    @change="resetValidation(idx)"
                    v-model="fields[idx].field_type"
                >
                    <option value="text">{{ $t("field.text") }}</option>
                    <option value="textarea">{{ $t("field.longText") }}</option>
                    <option value="wysiwyg">
                        {{ $t("field.textEditor") }}
                    </option>
                    <option value="number">{{ $t("field.number") }}</option>
                    <option value="date">{{ $t("field.date") }}</option>
                    <option value="file">{{ $t("field.file") }}</option>
                    <option value="image">{{ $t("field.image") }}</option>
                    <option value="hyperlink">
                        {{ $t("field.hyperlink") }}
                    </option>
                </select>
                <span class="form-feedback"></span>
            </div>
            <div>
                <Switch
                    :labeling="$t('field.required')"
                    v-model="fields[idx].is_required"
                />
            </div>
        </div>
        <FieldValidation
            class="col-span-5"
            :type="fields[idx].field_type"
            :unique-key="idx"
            :validation="fields[idx].validation"
            :errors="errors"
        />
        <div>
            <button
                v-if="!fields[idx].id"
                class="btn btn-danger"
                @click="removeField(idx)"
            >
                <fa icon="fa-trash" />
            </button>
            <Link
                v-else
                class="btn btn-danger"
                @click="removeField(idx)"
                :href="
                    route('admin.content-types.delete-field', {
                        content_type: idContentType,
                        id: fields[idx].id,
                    })
                "
                method="DELETE"
                as="button"
            >
                <fa icon="fa-trash" />
            </Link>
        </div>
        <div class="col-span-2">
            <hr class="mb-2" />
        </div>
    </div>
</template>

<script setup>
import Switch from "@admin/Components/Form/Switch.vue";
import FieldValidation from "@admin/Pages/ContentType/Components/FieldValidation.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    errors: Object,
    fields: Array,
    idContentType: Number,
});

const removeField = (index) => {
    props.fields.splice(index, 1);
};

const resetValidation = (index) => {
    props.fields[index].validation = {};
};
</script>
