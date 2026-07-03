<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Pilih Modifier</div>
        <div class="grid grid-cols-2 gap-3">
            <label v-for="mod in modifierGroups" :key="mod.id" class="flex items-center gap-2 border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition">
                <input type="checkbox" :value="mod.id" v-model="selectedModifiers" class="rounded text-primary cursor-pointer">
                <span class="text-sm cursor-pointer">{{ mod.name }} ({{ mod.selection_type }})</span>
            </label>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const modifierGroups = inject('modifierGroups')
const selectedModifiers = inject('selectedModifiers')
</script>
