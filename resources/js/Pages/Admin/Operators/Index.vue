<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ operators: Object, countries: Array })
</script>

<template>
 <Head title="Operators - Admin" />
 <div class="space-y-6">
 <div class="flex items-center justify-between">
 <div><h1 class="text-2xl font-bold text-gray-900">Operators</h1><p class="text-gray-600">Manage mobile operators</p></div>
 <button @click="$page.props.flash" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Sync from Ding</button>
 </div>

 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <table class="min-w-full divide-y divide-gray-200">
 <thead class="bg-gray-50">
 <tr>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ding ID</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-200">
 <tr v-for="op in operators.data" :key="op.id" class="hover:bg-gray-50">
 <td class="px-4 py-3 text-sm font-medium">{{ op.name }}</td>
 <td class="px-4 py-3 text-sm font-mono">{{ op.ding_operator_id }}</td>
 <td class="px-4 py-3 text-sm">{{ op.country?.name || '-' }}</td>
 <td class="px-4 py-3"><span :class="['px-2 py-1 text-xs rounded-full', op.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700']">{{ op.is_active ? 'Active' : 'Inactive' }}</span></td>
 </tr>
 <tr v-if="!operators.data?.length"><td colspan="4" class="px-4 py-8 text-center text-gray-500">No operators found</td></tr>
 </tbody>
 </table>
 </div>
 </div>
</template>
