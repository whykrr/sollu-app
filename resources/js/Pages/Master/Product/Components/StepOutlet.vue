<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Distribusi Outlet</div>
        <table class="w-full text-left border rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-slate-50">
                    <th class="p-2 border-b text-sm font-semibold text-slate-700">Outlet</th>
                    <th class="p-2 border-b text-sm font-semibold text-slate-700 text-center">Tersedia</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="outlet in outlets" :key="outlet.id" class="hover:bg-slate-50/50">
                    <td class="p-2 border-b text-sm">{{ outlet.name }}</td>
                    <td class="p-2 border-b text-center">
                        <input type="checkbox" v-model="outletStatusMap[outlet.id]" class="rounded h-5 w-5 text-primary cursor-pointer">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const outlets = inject('outlets')
const outletStatusMap = inject('outletStatusMap')
</script>
