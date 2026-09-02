<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ rules: Array })

const form = useForm({
 name: '', description: '', scope: 'global',
 operator_id: '', country_id: '', retailer_tier: '',
 min_amount: '', max_amount: '',
 commission_type: 'percentage', commission_value: '',
 priority: 0, effective_from: new Date().toISOString().split('T')[0], effective_until: '',
})

function submit() { form.post('/admin/commissions', { onSuccess: () => form.reset() }) }
</script>

<template>
 <Head title="Commission Rules - Admin" />
 <div class="space-y-6">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Commission Rules</h1>
 <p class="text-gray-600">Configure pricing and commission tiers</p>
 </div>

 <!-- Add Rule Form -->
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">Add New Rule</h3>
 <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div><label class="text-xs text-gray-500">Name</label><input v-model="form.name" required class="w-full border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">Scope</label><select v-model="form.scope" class="w-full border rounded px-3 py-2 text-sm"><option value="global">Global</option><option value="operator">Per Operator</option><option value="retailer_tier">Per Tier</option><option value="country">Per Country</option><option value="amount_band">Amount Band</option></select></div>
 <div><label class="text-xs text-gray-500">Type</label><select v-model="form.commission_type" class="w-full border rounded px-3 py-2 text-sm"><option value="percentage">Percentage</option><option value="flat">Flat Fee</option></select></div>
 <div><label class="text-xs text-gray-500">Value</label><input v-model="form.commission_value" type="number" step="0.01" required class="w-full border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">Min Amount</label><input v-model="form.min_amount" type="number" class="w-full border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">Max Amount</label><input v-model="form.max_amount" type="number" class="w-full border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">Retailer Tier</label><select v-model="form.retailer_tier" class="w-full border rounded px-3 py-2 text-sm"><option value="">Any</option><option value="bronze">Bronze</option><option value="silver">Silver</option><option value="gold">Gold</option></select></div>
 <div><label class="text-xs text-gray-500">Priority</label><input v-model="form.priority" type="number" class="w-full border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">Effective From</label><input v-model="form.effective_from" type="date" required class="w-full border rounded px-3 py-2 text-sm"></div>
 <div class="md:col-span-3"><button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">Add Rule</button></div>
 </form>
 </div>

 <!-- Rules List -->
 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <table class="min-w-full divide-y divide-gray-200">
 <thead class="bg-gray-50">
 <tr>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scope</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-200">
 <tr v-for="rule in rules" :key="rule.id">
 <td class="px-4 py-3 text-sm font-medium">{{ rule.name }}</td>
 <td class="px-4 py-3 text-sm">{{ rule.scope }}</td>
 <td class="px-4 py-3 text-sm">{{ rule.commission_type }}</td>
 <td class="px-4 py-3 text-sm">{{ rule.commission_value }}{{ rule.commission_type === 'percentage' ? '%' : ' Rs' }}</td>
 <td class="px-4 py-3 text-sm">{{ rule.priority }}</td>
 <td class="px-4 py-3"><span :class="['px-2 py-1 text-xs rounded-full', rule.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700']">{{ rule.is_active ? 'Active' : 'Inactive' }}</span></td>
 <td class="px-4 py-3 text-sm"><form :action="`/admin/commissions/${rule.id}`" method="POST"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" :value="$page.props.csrf_token"><button type="submit" class="text-red-600 hover:underline">Delete</button></form></td>
 </tr>
 </tbody>
 </table>
 </div>
 </div>
 </template>
