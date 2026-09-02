<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
defineOptions({ layout: AppLayout })

defineOptions({ layout: AppLayout })

const props = defineProps({
 stats: Object,
 recentTransactions: Array,
 chartData: Array,
})
</script>

<template>
 <Head title="Dashboard - MK Network" />

 <div class="space-y-6">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
 <p class="text-gray-600 mt-1">Welcome back, {{ $page.props.auth.user.name }}!</p>
 </div>

 <!-- Low Balance Warning -->
 <div v-if="stats.low_balance" class="bg-red-50 border border-red-200 rounded-lg p-4">
 <div class="flex items-center">
 <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
 </svg>
 <div>
 <h3 class="text-sm font-medium text-red-800">Low Wallet Balance</h3>
 <p class="text-sm text-red-700 mt-1">Your available balance (Rs. {{ stats.available_balance.toFixed(2) }}) is below the minimum threshold. Please top up to continue recharging.</p>
 <Link href="/retailer/wallet" class="mt-2 inline-block text-sm font-medium text-red-800 underline">Top Up Now</Link>
 </div>
 </div>
 </div>

 <!-- Stats Grid -->
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Wallet Balance</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">Rs. {{ stats.wallet_balance.toFixed(2) }}</div>
 <div class="text-xs text-gray-400 mt-1">Available: Rs. {{ stats.available_balance.toFixed(2) }}</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Today's Recharges</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.today_transactions }}</div>
 <div class="text-xs text-green-600 mt-1">{{ stats.today_success }} successful</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">Success Rate</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.success_rate }}%</div>
 <div class="text-xs text-gray-400 mt-1">{{ stats.total_transactions }} total</div>
 </div>
 <div class="bg-white rounded-xl p-5 shadow-sm">
 <div class="text-sm text-gray-500">This Month</div>
 <div class="text-2xl font-bold text-gray-900 mt-1">Rs. {{ stats.this_month_volume.toFixed(2) }}</div>
 <div class="text-xs text-gray-400 mt-1">{{ stats.this_month_success }} successful</div>
 </div>
 </div>

 <!-- Chart -->
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold text-gray-900 mb-4">Last 7 Days</h3>
 <div class="grid grid-cols-7 gap-2">
 <div v-for="day in chartData" :key="day.date" class="text-center">
 <div class="text-xs text-gray-500">{{ new Date(day.date).toLocaleDateString('en-US', { weekday: 'short' }) }}</div>
 <div class="h-20 bg-blue-100 rounded-lg flex flex-col items-center justify-center mt-1">
 <div class="text-sm font-semibold text-blue-700">{{ day.count }}</div>
 </div>
 <div class="text-xs text-gray-400 mt-1">{{ day.count }}</div>
 </div>
 </div>
 </div>

 <!-- Recent Transactions -->
 <div class="bg-white rounded-xl shadow-sm">
 <div class="p-6 border-b border-gray-200">
 <div class="flex items-center justify-between">
 <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
 <Link href="/retailer/transactions" class="text-sm text-blue-600 hover:underline">View All</Link>
 </div>
 </div>
 <div class="divide-y divide-gray-200">
 <div v-for="txn in recentTransactions.slice(0, 5)" :key="txn.id"
 class="p-4 flex items-center justify-between hover:bg-gray-50">
 <div class="flex items-center space-x-4">
 <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
 <span class="text-lg">{{ txn.operator?.name?.charAt(0) || '?' }}</span>
 </div>
 <div>
 <div class="font-medium text-gray-900">{{ txn.mobile_number }}</div>
 <div class="text-sm text-gray-500">{{ txn.operator?.name || 'Unknown' }} · {{ txn.created_at?.diffForHumans() }}</div>
 </div>
 </div>
 <div class="text-right">
 <div class="font-semibold text-gray-900">Rs. {{ txn.amount.toFixed(2) }}</div>
 <span :class="['px-2 py-1 text-xs rounded-full', txn.status === 'success' ? 'bg-green-100 text-green-700' : txn.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
 {{ txn.status }}
 </span>
 </div>
 </div>
 <div v-if="recentTransactions.length === 0" class="p-8 text-center text-gray-500">
 No transactions yet. <Link href="/retailer/recharge" class="text-blue-600 hover:underline">Make your first recharge!</Link>
 </div>
 </div>
 </div>

 <!-- Quick Actions -->
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <Link href="/retailer/recharge" class="bg-blue-600 text-white rounded-xl p-6 hover:bg-blue-700 transition text-center">
 <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
 </svg>
 <div class="font-semibold">New Recharge</div>
 </Link>
 <Link href="/retailer/wallet" class="bg-green-600 text-white rounded-xl p-6 hover:bg-green-700 transition text-center">
 <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4v-4m14 4v-4"/>
 </svg>
 <div class="font-semibold">Top Up Wallet</div>
 </Link>
 <Link href="/retailer/transactions" class="bg-purple-600 text-white rounded-xl p-6 hover:bg-purple-700 transition text-center">
 <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
 </svg>
 <div class="font-semibold">View History</div>
 </Link>
 </div>
 </div>
 </template>
