<template>
  <Container>
    <div class="grid grid-cols-3 gap-2 min-h-full">
      <div class="col-span-2 bg-white rounded-lg p-2 space-y-1">
        <div class="font-semibold">Detail</div>
        <div>
          <TextField
            id="name"
            v-model="form.name"
            label="Nama Lengkap"
            :class="{ 'is-invalid': form.errors.name }"
            :feedback="form.errors.name"
          />
        </div>
        <div>
          <EmailField
            id="email"
            v-model="form.email"
            label="Email"
            :class="{ 'is-invalid': form.errors.email }"
            :feedback="form.errors.email"
            :disabled="user"
          />
        </div>
        <div>
          <NumberField
            id="phone"
            v-model="form.phone"
            label="Telepon"
            :class="{ 'is-invalid': form.errors.phone }"
            :feedback="form.errors.phone"
          />
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <div class="bg-white rounded-lg p-2 space-y-1">
          <div class="font-semibold">Peran</div>
          <div class="flex flex-wrap gap-1">
            <RadioButtonField
              v-model="form.role"
              name="role"
              :options="roles"
              class="sm"
              :feedback="form.errors.role"
            />
          </div>
          <div class="text-danger text-xs select-none">
            {{ form.errors.role }}
          </div>
        </div>

        <div
          v-if="!selectedOutlet"
          class="bg-white rounded-lg p-2 space-y-1"
        >
          <div class="font-semibold">Outlet</div>
          <div class="flex flex-wrap gap-1">
            <CheckboxButtonField
              v-model="form.outlets"
              :options="outlets"
              name="outlets"
              class="sm btn-sm"
            />
          </div>
          <div class="text-danger text-xs select-none">
            {{ form.errors.outlets }}
          </div>
        </div>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-between">
        <div class="inline-flex gap-2">
          <!-- <ButtonBack /> -->

          <ButtonGroupArchive
            v-if="user"
            :data="user"
            :url-archive="
              route('dashboard.employees.destroy', {
                user: user.id,
              })
            "
            :url-restore="
              route('dashboard.employees.restore', {
                user: user.id,
              })
            "
            :url-delete="
              route('dashboard.employees.purge', {
                user: user.id,
              })
            "
          />
        </div>
        <button
          v-if="!user || user?.deleted_at === null"
          type="button"
          class="btn btn-success"
          @click="submitData"
        >
          Simpan
        </button>
      </div>
    </template>
  </Container>
</template>
<script setup>
import Container from '@/Components/Dashboard/UI/Container.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import TextField from '@/Components/Dashboard/Form/TextField.vue'
import EmailField from '@/Components/Dashboard/Form/EmailField.vue'
import NumberField from '@/Components/Dashboard/Form/NumberField.vue'
import RadioButtonField from '@/Components/Dashboard/Form/RadioButtonField.vue'
import CheckboxButtonField from '@/Components/Dashboard/Form/CheckboxButtonField.vue'
import ButtonGroupArchive from '@/Components/Dashboard/Button/ButtonGroupArchive.vue'
import { computed } from 'vue'
import TextareaField from '@/Components/Dashboard/Form/TextareaField.vue'

const props = defineProps({
    returnTo: String,
    user: Object,
    roles: Array,
})

const selectedOutlet = computed(() => usePage().props.selectedOutlet)

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}))

const form = useForm({
    name: props.user?.name ?? null,
    email: props.user?.email ?? null,
    phone: props.user?.phone ?? null,
    role: props.user?.roles[0].name ?? '',
    outlets: props.user?.outlets
        ? props.user?.outlets?.map((outlet) => outlet.id)
        : selectedOutlet.value
            ? [selectedOutlet.value?.id]
            : [],
    return_url: props.returnTo,
})

const submitData = () => {
    if (props.user) {
        form.put(route('dashboard.employees.update', { user: props.user.id }))
    } else {
        form.post(route('dashboard.employees.store'))
    }
}
</script>
