<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ retailer: Object })

const creditForm = useForm({ amount: '', description: '' })
</script>

<template>
 <Head :title="`${retailer.name} - Retailer Details`" />
 <div class="space-y-6">
 <div class="flex items-center gap-4">
 <Link href="/admin/retailers" class="text-gray-600 hover:text-gray-900">← Back</Link>
 <div>
 <h1 class="text-2xl font-bold text-gray-900">{{ retailer.name }}</h1>
 <p class="text-gray-600">{{ retailer.email }} · {{ retailer.phone }}</p>
 </div>
 </div>

 <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
 <div class="lg:col-span-2 space-y-6">
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">Retailer Information</h3>
 <div class="grid grid-cols-2 gap-4">
 <div><span class="text-gray-500">Shop Name:</span> <p class="font-medium">{{ retailer.shop_name || '-' }}</p></div>
 <div><span class="text-gray-500">Address:</span> <p class="font-medium">{{ retailer.address || '-' }}</p></div>
 <div><span class="text-gray-500">City:</span> <p class="font-medium">{{ retailer.city || '-' }}</p></div>
 <div><span class="text-gray-500">State:</span> <p class="font-medium">{{ retailer.state || '-' }}</p></div>
 <div><span class="text-gray-500">GST:</span> <p class="font-medium">{{ retailer.gst_number || '-' }}</p></div>
 <div><span class="text-gray-500">PAN:</span> <p class="font-medium">{{ retailer.pan_number || '-' }}</p></div>
 </div>
 </div>

 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
 <div v-if="retailer.transactions?.length" class="divide-y">
 <div v-for="txn in retailer.transactions.slice(0, 10)" :key="txn.id" class="py-3 flex items-center justify-between">
 <div>
 <div class="font-medium">{{ txn.mobile_number }}</div>
 <div class="text-sm text-gray-500">{{ txn.operator?.name || 'Unknown' }} · {{ txn.created_at }}</div>
 </div>
 <div class="text-right">
 <div class="font-medium">Rs. {{ Number(txn.amount).toFixed(2) }}</div>
 <span :class="['px-2 py-0.5 text-xs rounded-full', txn.status === 'success' ? 'bg-green-100 text-green-700' : txn.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
 {{ txn.status }}
 </span>
 </div>
 </div>
 </div>
 <p v-else class="text-gray-500">No transactions yet</p>
 </div>
 </div>

 <div class="space-y-6">
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">Wallet</h3>
 <div class="text-3xl font-bold text-gray-900">Rs. {{ Number(retailer.wallet?.balance || 0).toFixed(2) }}</div>
 <form @submit.prevent="creditForm.post(`/admin/retailers/${retailer.id}/credit`)" class="mt-4 space-y-3">
 <input v-model="creditForm.amount" type="number" step="0.01" placeholder="Amount" class="w-full px-3 py-2 border rounded-lg" required>
 <input v-model="creditForm.description" type="text" placeholder="Description (optional)" class="w-full px-3 py-2 border rounded-lg">
 <button type="submit" :disabled="creditForm.processing" class="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">Credit Wallet</button>
 </form>
 </div>

 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">KYC Status</h3>
 <span :class="['px-3 py-1 rounded-full text-sm', retailer.kyc_status === 'approved' ? 'bg-green-100 text-green-700' : retailer.kyc_status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
 {{ retailer.kyc_status }}
 </span>
 <p v-if="retailer.kyc_rejection_reason" class="text-sm text-red-600 mt-2">{{ retailer.kyc_rejection_reason }}</p>
 <div class="mt-4 space-y-2">
 <form :action="`/admin/retailers/${retailer.id}/kyc`" method="POST">
 <input type="hidden" name="_token" :value="$page.props.csrf_token">
 <input type="hidden" name="action" value="approve">
 <button type="submit" class="w-full py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approve KYC</button>
 </form>
 <form :action="`/admin/retailers/${retailer.id}/kyc`" method="POST">
 <input type="hidden" name="_token" :value="$page.props.csrf_token">
 <input type="hidden" name="action" value="reject">
 <input type="text" name="rejection_reason" placeholder="Rejection reason" class="w-full px-3 py-2 border rounded-lg mb-2 text-sm">
 <button type="submit" class="w-full py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">Reject KYC</button>
 </form>
 </div>
 </div>
 </div>
 </div>
 </div>
 </template>
