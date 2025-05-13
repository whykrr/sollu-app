<template>
    <div class="form-group border-neutral">
        <span class="text">
            <fa icon="fa-search" />
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
defineProps({
    modelValue: String,
    name: String,
    label: String,
    id: String,
    placeholder: String,
});
</script>
