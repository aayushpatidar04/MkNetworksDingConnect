<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({
 stats: Object,
 topRetailers: Array,
 recentTransactions: Array,
 chartData: Array,
})
</script>

<template>
 <Head title="Dashboard - Admin" />

 <div class="space-y-6">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
 <p class="text-gray-600">Welcome to the MK Network Admin Panel</p>
 </div>

 <!-- Stats Grid -->
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Total Retailers</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_retailers }}</div>
 <div class="text-xs text-green-600 mt-1">{{ stats.active_retailers }} active</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Today's Transactions</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.today_transactions }}</div>
 <div class="text-xs text-green-600 mt-1">{{ stats.today_success }} success</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Today's Revenue</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">Rs. {{ Number(stats.today_commission).toFixed(2) }}</div>
 <div class="text-xs text-gray-400 mt-1">Commission earned</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Success Rate</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.success_rate }}%</div>
 <div class="text-xs text-gray-400 mt-1">All time</div>
 </div>
 </div>

 <!-- DingConnect Balance -->
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="flex items-center justify-between">
 <div>
 <h3 class="text-lg font-semibold text-gray-900">DingConnect Balance</h3>
 <p class="text-gray-500">Your wholesale balance with DingConnect</p>
 </div>
 <div class="text-right">
 <div class="text-2xl font-bold text-blue-600">
 {{ stats.ding_balance?.success ? stats.ding_balance.balance + ' ' + stats.ding_balance.currency : 'N/A' }}
 </div>
 </div>
 </div>
 </div>

 <!-- Top Retailers & Recent Transactions -->
 <div class="grid lg:grid-cols-2 gap-6">
 <div class="bg-white rounded-xl shadow-sm">
 <div class="p-6 border-b border-gray-200">
 <h3 class="text-lg font-semibold text-gray-900">Top Retailers This Month</h3>
 </div>
 <div class="divide-y divide-gray-200">
 <div v-for="retailer in topRetailers" :key="retailer.id" class="p-4 flex items-center justify-between">
 <div>
 <div class="font-medium text-gray-900">{{ retailer.shop_name || retailer.name }}</div>
 <div class="text-sm text-gray-500">{{ retailer.phone }}</div>
 </div>
 <div class="text-right">
 <div class="font-semibold text-gray-900">{{ retailer.month_transactions }}</div>
 <div class="text-xs text-gray-400">transactions</div>
 </div>
 </div>
 <div v-if="topRetailers.length === 0" class="p-6 text-center text-gray-500">No data yet</div>
 </div>
 </div>

 <div class="bg-white rounded-xl shadow-sm">
 <div class="p-6 border-b border-gray-200 flex items-center justify-between">
 <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
 <Link href="/admin/transactions" class="text-sm text-blue-600 hover:underline">View All</Link>
 </div>
 <div class="divide-y divide-gray-200">
 <div v-for="txn in recentTransactions.slice(0, 8)" :key="txn.id" class="p-4 flex items-center justify-between">
 <div class="flex items-center space-x-3">
 <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-semibold">
 {{ txn.operator?.name?.charAt(0) || '?' }}
 </div>
 <div>
 <div class="text-sm font-medium">{{ txn.mobile_number }}</div>
 <div class="text-xs text-gray-500">{{ txn.user?.name || 'Unknown' }}</div>
 </div>
 </div>
 <div class="text-right">
 <div class="text-sm font-medium">Rs. {{ txn.amount.toFixed(2) }}</div>
 <span :class="['px-2 py-0.5 text-xs rounded-full', txn.status === 'success' ? 'bg-green-100 text-green-700' : txn.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
 {{ txn.status }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </template>
