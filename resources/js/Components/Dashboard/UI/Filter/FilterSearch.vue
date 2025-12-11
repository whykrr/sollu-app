<template>
  <div class="form-group has-text">
    <label for="filter_search" class="form-group-text">
      <FontAwesomeIcon :icon="faSearch" />
    </label>

    <input
      id="filter_search"
      type="text"
      class="form sm"
      placeholder="Cari ..."
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSearch } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: { type: [String], default: '' },
})

const emit = defineEmits(['update:modelValue'])
</script>
