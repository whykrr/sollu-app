<template>
  <div class="border rounded-lg p-2">
    <div class="text-lg font-medium">Tambah Outlet</div>
    <div class="grid grid-cols-12 gap-2">
      <div class="col-span-5">
        <TextField
          id="name"
          v-model="formCreate.name"
          placeholder="Nama Outlet"
          class="sm"
          :class="{ 'is-invalid': formCreate.errors.name }"
          :feedback="formCreate.errors.name"
        />
      </div>
      <div class="col-span-5">
        <TextareaField
          id="address"
          v-model="formCreate.address"
          placeholder="Alamat"
          class="sm"
          :class="{ 'is-invalid': formCreate.errors.address }"
          :feedback="formCreate.errors.address"
        />
      </div>
      <div class="col-span-2">
        <button class="btn btn-main btn-sm w-full">Simpan</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import TextareaField from '@/Components/Form/TextareaField.vue'
import TextField from '@/Components/Form/TextField.vue'
import { useForm } from '@inertiajs/vue3'

const formCreate = useForm({
    name: '',
    address: '',
})
</script>
