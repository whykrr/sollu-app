<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    form: Object,
    categories: Array,
    isEditing: { 
        type: Boolean, 
        default: false, 
    },
})

const emit = defineEmits(['submit'])

</script>

<template>
  <form class="space-y-6" @submit.prevent="emit('submit')">
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
      <div class="mt-1">
        <input id="name" v-model="form.name" type="text" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
      </div>
      <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</div>
    </div>

    <div>
      <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Kategori</label>
      <div class="mt-1">
        <select id="parent_id" v-model="form.parent_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
          <option :value="null">-- Tidak Ada Parent --</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
      <p class="mt-2 text-sm text-gray-500">Pilih parent jika ini adalah sub-kategori. Maksimal 3 level.</p>
      <div v-if="form.errors.parent_id" class="mt-2 text-sm text-red-600">{{ form.errors.parent_id }}</div>
    </div>

    <div class="flex items-center justify-end space-x-4">
      <Link :href="route('products.categories.index')" class="text-sm text-gray-600 hover:text-gray-900">Batal</Link>
      <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
        {{ isEditing ? 'Update' : 'Simpan' }}
      </button>
    </div>
  </form>
</template>
