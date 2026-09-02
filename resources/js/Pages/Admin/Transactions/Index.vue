<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ transactions: Object, stats: Object, retailers: Array })
</script>

<template>
 <Head title="Transactions - Admin" />
 <div class="space-y-6">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Transactions</h1>
 <p class="text-gray-600">All platform transactions</p>
 </div>

 <!-- Stats -->
 <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
 <div class="bg-white rounded-xl p-4"><div class="text-sm text-gray-500">Total</div><div class="text-xl font-bold">{{ stats.total }}</div></div>
 <div class="bg-white rounded-xl p-4"><div class="text-sm text-gray-500">Success</div><div class="text-xl font-bold text-green-600">{{ stats.success }}</div></div>
 <div class="bg-white rounded-xl p-4"><div class="text-sm text-gray-500">Failed</div><div class="text-xl font-bold text-red-600">{{ stats.failed }}</div></div>
 <div class="bg-white rounded-xl p-4"><div class="text-sm text-gray-500">Pending</div><div class="text-xl font-bold text-yellow-600">{{ stats.pending }}</div></div>
 <div class="bg-white rounded-xl p-4"><div class="text-sm text-gray-500">Revenue</div><div class="text-xl font-bold text-blue-600">Rs. {{ Number(stats.total_commission).toFixed(2) }}</div></div>
 </div>

 <!-- Filters -->
 <form method="GET" class="bg-white rounded-xl p-4 shadow-sm flex flex-wrap gap-3 items-end">
 <div><label class="text-xs text-gray-500">Status</label><select name="status" class="border rounded px-3 py-2 text-sm"><option value="">All</option><option value="success">Success</option><option value="failed">Failed</option><option value="pending">Pending</option></select></div>
 <div><label class="text-xs text-gray-500">Retailer</label><select name="retailer_id" class="border rounded px-3 py-2 text-sm"><option value="">All</option><option v-for="r in retailers" :value="r.id" :key="r.id">{{ r.name }}</option></select></div>
 <div><label class="text-xs text-gray-500">From</label><input type="date" name="from" class="border rounded px-3 py-2 text-sm"></div>
 <div><label class="text-xs text-gray-500">To</label><input type="date" name="to" class="border rounded px-3 py-2 text-sm"></div>
 <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Filter</button>
 <Link href="/admin/transactions/export" class="px-4 py-2 bg-green-600 text-white rounded text-sm">Export</Link>
 </form>

 <!-- Table -->
 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <table class="min-w-full divide-y divide-gray-200">
 <thead class="bg-gray-50">
 <tr>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Retailer</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mobile</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-200">
 <tr v-for="txn in transactions.data" :key="txn.id" class="hover:bg-gray-50">
 <td class="px-4 py-3 text-sm">{{ txn.id }}</td>
 <td class="px-4 py-3 text-sm">{{ txn.created_at }}</td>
 <td class="px-4 py-3 text-sm">{{ txn.user?.name || '-' }}</td>
 <td class="px-4 py-3 text-sm font-mono">{{ txn.mobile_number }}</td>
 <td class="px-4 py-3 text-sm">{{ txn.operator?.name || '-' }}</td>
 <td class="px-4 py-3 text-sm font-medium">Rs. {{ Number(txn.amount).toFixed(2) }}</td>
 <td class="px-4 py-3 text-sm text-green-600">+Rs. {{ Number(txn.commission_amount).toFixed(2) }}</td>
 <td class="px-4 py-3"><span :class="['px-2 py-1 text-xs rounded-full', txn.status === 'success' ? 'bg-green-100 text-green-700' : txn.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">{{ txn.status }}</span></td>
 <td class="px-4 py-3 text-sm">{{ txn.receipt_number || '-' }}</td>
 </tr>
 <tr v-if="!transactions.data?.length"><td colspan="9" class="px-4 py-8 text-center text-gray-500">No transactions found</td></tr>
 </tbody>
 </table>
 </div>
 <!-- Pagination -->
 <div v-if="transactions.links" class="mt-4 flex justify-center gap-2">
 <template v-for="link in transactions.links" :key="link.label">
 <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 rounded border text-sm" :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white'"/>
 <span v-else v-html="link.label" class="px-3 py-1 rounded border text-sm bg-gray-100 text-gray-400"/>
 </template>
 </div>
 </div>
 </template>
