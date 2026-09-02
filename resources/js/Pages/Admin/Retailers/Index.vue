<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ retailers: Object })
</script>

<template>
 <Head title="Retailers - Admin" />
 <div class="space-y-6">
 <div class="flex items-center justify-between">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Retailers</h1>
 <p class="text-gray-600">Manage registered retailers</p>
 </div>
 <div class="flex gap-2">
 <Link href="/admin/retailers/export" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Export CSV</Link>
 <Link href="/admin/retailers/create" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Add Retailer</Link>
 </div>
 </div>

 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-200">
 <thead class="bg-gray-50">
 <tr>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Retailer</th>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wallet</th>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KYC</th>
 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
 <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-200">
 <tr v-for="retailer in retailers.data" :key="retailer.id">
 <td class="px-6 py-4">
 <div class="font-medium text-gray-900">{{ retailer.name }}</div>
 <div class="text-sm text-gray-500">{{ retailer.email }}</div>
 <div class="text-sm text-gray-500">{{ retailer.phone }}</div>
 </td>
 <td class="px-6 py-4 text-sm text-gray-900">{{ retailer.shop_name || '-' }}</td>
 <td class="px-6 py-4">
 <span class="px-2 py-1 text-xs rounded-full" :class="{
 'bg-yellow-100 text-yellow-700': retailer.commission_tier === 'bronze',
 'bg-gray-100 text-gray-700': retailer.commission_tier === 'silver',
 'bg-amber-100 text-amber-700': retailer.commission_tier === 'gold',
 }">{{ retailer.commission_tier }}</span>
 </td>
 <td class="px-6 py-4 text-sm font-medium">Rs. {{ Number(retailer.wallet?.balance || 0).toFixed(2) }}</td>
 <td class="px-6 py-4">
 <span class="px-2 py-1 text-xs rounded-full" :class="{
 'bg-yellow-100 text-yellow-700': retailer.kyc_status === 'pending',
 'bg-green-100 text-green-700': retailer.kyc_status === 'approved',
 'bg-red-100 text-red-700': retailer.kyc_status === 'rejected',
 }">{{ retailer.kyc_status }}</span>
 </td>
 <td class="px-6 py-4">
 <span class="px-2 py-1 text-xs rounded-full" :class="retailer.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
 {{ retailer.is_active ? 'Active' : 'Inactive' }}
 </span>
 </td>
 <td class="px-6 py-4 text-right text-sm">
 <Link :href="`/admin/retailers/${retailer.id}`" class="text-blue-600 hover:underline mr-2">View</Link>
 <form :action="`/admin/retailers/${retailer.id}/approve`" method="POST" class="inline" v-if="retailer.kyc_status === 'pending'">
 <input type="hidden" name="_token" :value="$page.props.csrf_token">
 <button type="submit" class="text-green-600 hover:underline mr-2">Approve</button>
 </form>
 </td>
 </tr>
 </tbody>
 </table>
 </div>
 <div v-if="!retailers.data?.length" class="p-8 text-center text-gray-500">No retailers found</div>
 </div>
 <!-- Pagination -->
 <div v-if="retailers.links" class="mt-4 flex justify-center gap-2">
 <template v-for="link in retailers.links" :key="link.label">
 <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 rounded border text-sm" :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white'"/>
 <span v-else v-html="link.label" class="px-3 py-1 rounded border text-sm bg-gray-100 text-gray-400"/>
 </template>
 </div>
 </div>
 </template>
