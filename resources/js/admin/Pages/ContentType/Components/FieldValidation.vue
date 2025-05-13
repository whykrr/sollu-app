<template>
    <div class="flex flex-col gap-2">
        <div v-if="type == 'text'" class="flex flex-row items-center">
            <div>
                <input
                    name="checkbox_default"
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_char' + uniqueKey"
                    :checked="validation.max !== undefined"
                    @change="validationToggle('max')"
                />
                <label
                    :for="'checkbox_char' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.maxCharacters") }}</label
                >
            </div>
            <div v-if="validation.max !== undefined">
                <input
                    type="text"
                    class="form-sm"
                    :placeholder="$t('field.value')"
                    v-model.number="validation.max"
                    :class="{
                        'is-invalid':
                            errors[`fields.${uniqueKey}.validation.max`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.max`]
                }}</span>
            </div>
        </div>

        <div v-if="type == 'number'" class="flex flex-row items-center">
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_min' + uniqueKey"
                    :checked="validation.min !== undefined"
                    @change="validationToggle('min')"
                />
                <label
                    :for="'checkbox_min' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.min") }}</label
                >
            </div>
            <div v-if="validation.min !== undefined">
                <input
                    type="text"
                    class="form-sm"
                    :placeholder="$t('field.value')"
                    v-model.number="validation.min"
                    :class="{
                        'is-invalid':
                            errors[`fields.${uniqueKey}.validation.min`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.min`]
                }}</span>
            </div>
        </div>

        <div v-if="type == 'number'" class="flex flex-row items-center">
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_max' + uniqueKey"
                    :checked="validation.max !== undefined"
                    @change="validationToggle('max')"
                />
                <label
                    :for="'checkbox_max' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.max") }}</label
                >
            </div>
            <div v-if="validation.max !== undefined">
                <input
                    type="text"
                    class="form-sm"
                    :placeholder="$t('field.value')"
                    v-model.number="validation.max"
                    :class="{
                        'is-invalid':
                            errors[`fields.${uniqueKey}.validation.max`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.max`]
                }}</span>
            </div>
        </div>

        <div v-if="type == 'file'" class="flex flex-row items-center">
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_size' + uniqueKey"
                    :checked="validation.size !== undefined"
                    @change="validationToggle('size')"
                />
                <label
                    :for="'checkbox_size' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.maxFileSize") }} (kb)</label
                >
            </div>
            <div v-if="validation.size !== undefined">
                <input
                    type="text"
                    class="form-sm"
                    placeholder="Value"
                    v-model.number="validation.size"
                    :class="{
                        'is-invalid':
                            errors[`fields.${uniqueKey}.validation.size`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.size`]
                }}</span>
            </div>
        </div>

        <div v-if="type == 'image'" class="flex flex-row items-center gap-2">
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_dimension' + uniqueKey"
                    :checked="validation.dimension !== undefined"
                    @change="validationToggle('dimension')"
                />
                <label
                    :for="'checkbox_dimension' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.imageDimension") }}</label
                >
            </div>
            <div v-if="validation.dimension !== undefined">
                <input
                    type="text"
                    class="form-sm w-28"
                    :placeholder="$t('field.width')"
                    v-model.number="validation.dimension.width"
                    :class="{
                        'is-invalid':
                            errors[
                                `fields.${uniqueKey}.validation.dimension.width`
                            ],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.dimension.maxWidth`]
                }}</span>
            </div>
            <div v-if="validation.dimension !== undefined">
                <input
                    type="text"
                    class="form-sm w-28"
                    :placeholder="$t('field.height')"
                    v-model.number="validation.dimension.height"
                    :class="{
                        'is-invalid':
                            errors[
                                `fields.${uniqueKey}.validation.dimension.height`
                            ],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.dimension.maxHeight`]
                }}</span>
            </div>
        </div>

        <div v-if="type == 'image'" class="flex flex-row items-center gap-2">
            <div>
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'checkbox_ratio' + uniqueKey"
                    :checked="validation.ratio !== undefined"
                    @change="validationToggle('ratio')"
                />
                <label
                    :for="'checkbox_ratio' + uniqueKey"
                    class="form-check-label"
                    >{{ $t("field.ratio") }}</label
                >
            </div>
            <div v-if="validation.ratio !== undefined">
                <input
                    type="text"
                    class="form-sm w-20"
                    placeholder="2/3"
                    v-model="validation.ratio"
                    :class="{
                        'is-invalid':
                            errors[`fields.${uniqueKey}.validation.ratio`],
                    }"
                />
                <span class="form-feedback">{{
                    errors[`fields.${uniqueKey}.validation.ratio`]
                }}</span>
            </div>
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    uniqueKey: Number,
    type: String,
    validation: Object,
    errors: Object,
});

const validationToggle = (validation) => {
    if (props.validation[validation] !== undefined) {
        delete props.validation[validation];
    } else {
        if (validation === "dimension") {
            props.validation[validation] = {
                width: null,
                height: null,
            };
        } else {
            props.validation[validation] = null;
        }
    }
};
</script>
