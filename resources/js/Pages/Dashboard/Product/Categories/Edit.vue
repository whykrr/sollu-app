<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layout/Dashboard/AppLayout.vue'
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
    form.put(route('products.categories.update', props.category.id))
}

</script>

<template>
  <Head title="Edit Kategori" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Kategori: {{ category.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <CategoryForm :form="form" :categories="availableCategories" :is-editing="true" @submit="submit" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
