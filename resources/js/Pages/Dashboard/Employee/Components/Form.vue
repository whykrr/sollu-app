<template>
    <div class="flex flex-col gap-2">
        <div>
            <TextField id="name" label="Nama Lengkap" class="sm" />
        </div>
        <div>
            <EmailField id="email" label="Email" class="sm" />
        </div>
        <div>
            <NumberField id="phone" label="Telepon" class="sm" />
        </div>
        <div>
            <RadioField
                id="all_outlet"
                name="outlet"
                label="Semua Outlet"
                class="sm"
            />
            <RadioButtonField
                id="all_outlet2"
                name="outlet"
                label="Semua Outlet"
                class="sm"
            />
        </div>
    </div>
</template>
<script setup>
import EmailField from "@/Components/Dashboard/Form/EmailField.vue";
import NumberField from "@/Components/Dashboard/Form/NumberField.vue";
import RadioButtonField from "@/Components/Dashboard/Form/RadioButtonField.vue";
import RadioField from "@/Components/Dashboard/Form/RadioField.vue";
import TextField from "@/Components/Dashboard/Form/TextField.vue";
</script>
