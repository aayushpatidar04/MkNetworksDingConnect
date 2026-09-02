<script setup>
import { Head, Link } from '@inertiajs/vue3'
import RetailerLayout from '@/Layouts/RetailerLayout.vue'
defineOptions({ layout: RetailerLayout })

const props = defineProps({ wallet: Object, availableBalance: Number, topups: Object })
</script>

<template>
 <Head title="Wallet - MK Network" />
 <div class="space-y-6">
 <h1 class="text-2xl font-bold text-gray-900">My Wallet</h1>

 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <div class="text-sm text-gray-500">Wallet Balance</div>
 <div class="text-3xl font-bold text-gray-900 mt-1">Rs. {{ Number(wallet.balance).toFixed(2) }}</div>
 </div>
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <div class="text-sm text-gray-500">Available Balance</div>
 <div class="text-3xl font-bold text-green-600 mt-1">Rs. {{ Number(availableBalance).toFixed(2) }}</div>
 <div class="text-xs text-gray-400 mt-1">{{ Number(wallet.balance - availableBalance).toFixed(2) }} on hold</div>
 </div>
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <div class="text-sm text-gray-500">Currency</div>
 <div class="text-3xl font-bold text-gray-900 mt-1">{{ wallet.currency }}</div>
 </div>
 </div>

 <div class="flex gap-3">
 <Link href="/retailer/wallet/topup" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Top Up Wallet</Link>
 <Link href="/retailer/wallet/ledger" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">View Ledger</Link>
 </div>

 <div class="bg-white rounded-xl shadow-sm">
 <div class="p-6 border-b"><h3 class="text-lg font-semibold">Recent Top-ups</h3></div>
 <table class="min-w-full divide-y">
 <thead class="bg-gray-50">
 <tr><th class="px-4 py-3 text-left text-xs text-gray-500">Date</th><th class="px-4 py-3 text-left text-xs text-gray-500">Amount</th><th class="px-4 py-3 text-left text-xs text-gray-500">Method</th><th class="px-4 py-3 text-left text-xs text-gray-500">Status</th></tr>
 </thead>
 <tbody class="divide-y">
 <tr v-for="topup in topups.data" :key="topup.id">
 <td class="px-4 py-3 text-sm">{{ topup.created_at }}</td>
 <td class="px-4 py-3 text-sm font-medium">Rs. {{ Number(topup.amount).toFixed(2) }}</td>
 <td class="px-4 py-3 text-sm capitalize">{{ topup.payment_method }}</td>
 <td class="px-4 py-3"><span :class="['px-2 py-0.5 text-xs rounded-full', topup.status === 'completed' ? 'bg-green-100 text-green-700' : topup.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">{{ topup.status }}</span></td>
 </tr>
 <tr v-if="!topups.data?.length"><td colspan="4" class="px-4 py-8 text-center text-gray-500">No top-ups yet</td></tr>
 </tbody>
 </table>
 </div>
 </div>
</template>
