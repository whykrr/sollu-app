<template>
  <div>
    <form
      class="space-y-2"
      @submit.prevent="submit"
    >

      <TextField
        id="name"
        v-model="form.name"
        label="Nama Bahan Baku"
        :feedback="form.errors.name"
        required
      />

      <TextField
        id="sku"
        v-model="form.sku"
        label="SKU"
        :feedback="form.errors.sku"
      />

      <TextField
        id="barcode"
        v-model="form.barcode"
        label="Barcode"
        :feedback="form.errors.barcode"
      />

      <DropdownField
        id="uom_id"
        v-model="form.uom_id"
        label="Satuan (UOM)"
        :options="uomOptions"
        :feedback="form.errors.uom_id"
        required
      />

      <label
        class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
      >
        <div>
          <div class="font-bold text-sm text-slate-800">
            Lacak Inventori (Stok)
          </div>
          <div class="text-xs text-slate-500">
            Lacak stok masuk dan keluar untuk bahan baku ini.
          </div>
        </div>
        <input
          v-model="form.track_inventory"
          type="checkbox"
          class="rounded h-5 w-5 text-primary cursor-pointer"
        />
      </label>

      <TextField
        v-if="form.track_inventory"
        id="minimum_stock"
        v-model="form.minimum_stock"
        type="number"
        label="Minimum Stok"
        :feedback="form.errors.minimum_stock"
      />
    </form>

    <!-- Footer Actions Di-teleportkan ke PopUpPage Modal Footer -->
    <Teleport
      v-if="isMounted"
      to="#popUpFooter"
    >
      <button
        type="button"
        class="btn btn-slate-400"
        :disabled="form.processing"
        @click="close"
      >
        Batal
      </button>
      <button
        type="button"
        class="btn btn-main"
        :disabled="form.processing"
        @click="submit"
      >
        <span v-if="form.processing">Menyimpan...</span>
        <span v-else>Simpan</span>
      </button>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, watch, ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import { usePopUpStore } from '@/store/popup'

const props = defineProps({
    rawMaterial: {
        type: Object,
        default: null,
    },
    uoms: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['close'])
const popUpStore = usePopUpStore()
const isMounted = ref(false)

onMounted(() => {
    isMounted.value = true
})

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    uom_id: '',
    track_inventory: true,
    minimum_stock: 0,
})

const uomOptions = computed(() => {
    return props.uoms.map((uom) => ({
        label: uom.name,
        value: uom.id,
    }))
})

watch(
    () => props.rawMaterial,
    (data) => {
        form.reset()
        if (data) {
            form.name = data.name || ''
            form.sku = data.sku || ''
            form.barcode = data.barcode || ''
            form.uom_id = data.uom_id || ''
            form.track_inventory = data.track_inventory ?? true;
            form.minimum_stock = data.minimum_stock || 0
        }
    },
    { immediate: true }
)

const close = () => {
    form.clearErrors()
    emit('close')
    popUpStore.close()
}

const submit = () => {
    if (props.rawMaterial?.id) {
        form.put(
            route('inventory.raw-materials.update', props.rawMaterial.id),
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => close(),
            }
        )
    } else {
        form.post(route('inventory.raw-materials.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    }
}
</script>
