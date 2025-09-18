<template>
    <Container>
        <div class="col-span-2 bg-white rounded-lg p-2 space-y-1">
            <div>
                <TextField
                    id="name"
                    label="Nama"
                    class="sm"
                    :class="{ 'is-invalid': form.errors.name }"
                    v-model="form.name"
                    :feedback="form.errors.name"
                    :disabled="unit?.merchant_id === null"
                />
            </div>
            <div>
                <TextField
                    id="symbol"
                    label="Simbol"
                    class="sm"
                    :class="{ 'is-invalid': form.errors.symbol }"
                    v-model="form.symbol"
                    :feedback="form.errors.symbol"
                    :disabled="unit?.merchant_id === null"
                />
            </div>
            <div>
                <TextareaField
                    id="description"
                    label="Keterangan"
                    class="sm"
                    :class="{ 'is-invalid': form.errors.description }"
                    v-model="form.description"
                    :feedback="form.errors.description"
                    :disabled="unit?.merchant_id === null"
                />
            </div>
        </div>
        <template #footer>
            <div class="flex justify-between">
                <div class="inline-flex gap-2">
                    <!-- <ButtonBack /> -->

                    <ButtonGroupArchive
                        v-if="unit && unit?.merchant_id !== null"
                        :data="unit"
                        :url-archive="
                            route('dashboard.products.units.destroy', {
                                unit: unit.id,
                            })
                        "
                        :url-restore="
                            route('dashboard.products.units.restore', {
                                unit: unit.id,
                            })
                        "
                        :url-delete="
                            route('dashboard.products.units.purge', {
                                unit: unit.id,
                            })
                        "
                    />
                </div>
                <button
                    v-if="
                        (!user || user?.deleted_at === null) &&
                        unit?.merchant_id !== null
                    "
                    type="button"
                    @click="submitData"
                    class="btn btn-success btn-sm"
                >
                    Simpan
                </button>
            </div>
        </template>
    </Container>
</template>
<script setup>
import Container from "@/Components/Dashboard/UI/Container.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import TextField from "@/Components/Dashboard/Form/TextField.vue";
import ButtonGroupArchive from "@/Components/Dashboard/Button/ButtonGroupArchive.vue";
import TextareaField from "@/Components/Dashboard/Form/TextareaField.vue";

const props = defineProps({
    returnTo: String,
    unit: Object,
});

const form = useForm({
    name: props.unit?.name ?? null,
    symbol: props.unit?.symbol ?? null,
    description: props.unit?.description ?? null,
    return_url: props.returnTo,
});

const submitData = () => {
    if (props.unit) {
        form.put(
            route("dashboard.products.units.update", { unit: props.unit.id })
        );
    } else {
        form.post(route("dashboard.products.units.store"));
    }
};
</script>
