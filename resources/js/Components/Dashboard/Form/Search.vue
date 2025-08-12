<template>
    <div class="form-group border-neutral">
        <span class="text">
            <FontAwesomeIcon :icon="faSearch" />
        </span>
        <input
            :value="modelValue"
            type="text"
            :name
            :id
            class="form text-dark dark:text-light"
            :placeholder
        />
    </div>
</template>

<script setup>
import { faSearch } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

defineProps({
    modelValue: String,
    name: String,
    label: String,
    id: String,
    placeholder: String,
});
</script>
