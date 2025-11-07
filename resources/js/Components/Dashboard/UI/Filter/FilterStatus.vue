<template>
    <div class="form-group has-text">
        <label for="filter_status" class="form-group-text">
            <FontAwesomeIcon
                :icon="
                    modelValue === ''
                        ? faCheck
                        : modelValue === 'archived'
                        ? faArchive
                        : faList
                "
            />
        </label>

        <select
            id="filter_status"
            :value="modelValue"
            class="form sm"
            style="padding-right: 2.5rem !important"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="">Aktif</option>
            <option
                v-for="(option, index) in options"
                :key="index"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>
<script setup>
import { faArchive, faCheck, faList } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

defineProps({
    modelValue: String,
});

const options = [
    { value: "archived", label: "Arsip" },
    { value: "all", label: "Semua" },
];
</script>
