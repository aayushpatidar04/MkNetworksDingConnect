<script setup>
import { Head, Link } from '@inertiajs/vue3'
import RetailerLayout from '@/Layouts/RetailerLayout.vue'
defineOptions({ layout: RetailerLayout })

const props = defineProps({ transactions: Object })
</script>

<template>
 <Head title="Transactions - MK Network" />
 <div class="space-y-6">
 <h1 class="text-2xl font-bold text-gray-900">Transaction History</h1>

 <!-- Filters -->
 <form method="GET" class="bg-white rounded-xl p-4 shadow-sm flex flex-wrap gap-3">
 <select name="status" class="border rounded px-3 py-2 text-sm"><option value="">All Status</option><option value="success">Success</option><option value="failed">Failed</option><option value="pending">Pending</option></select>
 <input type="date" name="from" class="border rounded px-3 py-2 text-sm">
 <input type="date" name="to" class="border rounded px-3 py-2 text-sm">
 <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Filter</button>
 <Link href="/retailer/transactions" class="px-4 py-2 border rounded text-sm">Reset</Link>
 </form>

 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <table class="min-w-full divide-y divide-gray-200">
 <thead class="bg-gray-50">
 <tr><th class="px-4 py-3 text-left text-xs text-gray-500">Receipt</th><th class="px-4 py-3 text-left text-xs text-gray-500">Date</th><th class="px-4 py-3 text-left text-xs text-gray-500">Mobile</th><th class="px-4 py-3 text-left text-xs text-gray-500">Operator</th><th class="px-4 py-3 text-left text-xs text-gray-500">Amount</th><th class="px-4 py-3 text-left text-xs text-gray-500">Status</th></tr>
 </thead>
 <tbody class="divide-y">
 <tr v-for="txn in transactions.data" :key="txn.id" class="hover:bg-gray-50">
 <td class="px-4 py-3 text-sm font-mono">{{ txn.receipt_number }}</td>
 <td class="px-4 py-3 text-sm">{{ txn.created_at }}</td>
 <td class="px-4 py-3 text-sm font-mono">{{ txn.mobile_number }}</td>
 <td class="px-4 py-3 text-sm">{{ txn.operator?.name || '-' }}</td>
 <td class="px-4 py-3 text-sm font-medium">Rs. {{ Number(txn.amount).toFixed(2) }}</td>
 <td class="px-4 py-3"><span :class="['px-2 py-0.5 text-xs rounded-full', txn.status === 'success' ? 'bg-green-100 text-green-700' : txn.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">{{ txn.status }}</span></td>
 </tr>
 <tr v-if="!transactions.data?.length"><td colspan="6" class="px-4 py-8 text-center text-gray-500">No transactions found</td></tr>
 </tbody>
 </table>
 </div>
 </div>
</template>
