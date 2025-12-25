<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import Container from '@/Components/Dashboard/UI/Container.vue'
import CategoryForm from './Partials/CategoryForm.vue'

const props = defineProps({
    category: Object,
    availableCategories: Array,
})

const form = useForm({
    name: props.category.name,
    parent_id: props.category.parent_id,
})

const submit = () => {
    form.put(route('dashboard.products.categories.update', props.category.id))
}

</script>

<template>
  <Head title="Edit Kategori" />

  <Container>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Kategori: {{ category.name }}
      </h2>
    </template>

    <div class="bg-white rounded-lg p-6 border border-neutral-200">
        <CategoryForm :form="form" :categories="availableCategories" :is-editing="true" @submit="submit" />
    </div>
  </Container>
</template>
