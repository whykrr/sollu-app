<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    category: Object,
    level: Number,
})

const confirmDelete = (type, category) => {
    const message = type === 'soft' 
        ? 'Anda yakin ingin memindahkan kategori ini ke sampah?' 
        : 'PERHATIAN: Kategori dan semua sub-kategorinya akan dihapus permanen. Lanjutkan?'

    if (confirm(message)) {
        const routeName = type === 'soft' ? 'products.categories.destroy' : 'products.categories.force-delete'
        router.delete(route(routeName, category.id), { preserveScroll: true })
    }
}

</script>

<template>
  <tr :class="{ 'bg-gray-50': level > 0 }">
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
      <span :style="{ paddingLeft: level * 24 + 'px' }">{{ category.name }}</span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
      <span :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
        {{ category.is_active ? 'Aktif' : 'Tidak Aktif' }}
      </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
      <span :class="category.merchant_id ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
        {{ category.merchant_id ? 'Merchant' : 'Master' }}
      </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
      <div v-if="category.merchant_id" class="flex items-center justify-end space-x-2">
        <Link :href="route('products.categories.edit', category.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
        <button class="text-red-600 hover:text-red-900" @click="confirmDelete('soft', category)">Sampah</button>
        <button class="text-red-600 hover:text-red-900 font-bold" @click="confirmDelete('hard', category)">Hapus</button>
      </div>
      <div v-else>
        <span class="text-xs text-gray-400">-</span>
      </div>
    </td>
  </tr>
  <!-- Recursive render for children -->
  <template v-if="category.children && category.children.length">
    <CategoryRow 
      v-for="child in category.children" 
      :key="child.id" 
      :category="child" 
      :level="level + 1" 
    />
  </template>
</template>
