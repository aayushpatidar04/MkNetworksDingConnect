<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Echo } from 'laravel-echo'
import Pusher from 'pusher-js'

const showMobileMenu = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
let echoChannel = null

const user = usePage().props.auth.user

onMounted(() => {
 // Setup Pusher for real-time notifications
 if (user) {
 echoChannel = Echo.private(`retailer.${user.id}`)

 echoChannel.listen('.RechargeSuccess', (e) => {
 notifications.value.unshift({
 type: 'success',
 title: 'Recharge Successful',
 message: e.message,
 time: 'Just now',
 })
 unreadCount.value++
 playNotificationSound()
 })

 echoChannel.listen('.RechargeFailed', (e) => {
 notifications.value.unshift({
 type: 'error',
 title: 'Recharge Failed',
 message: e.message,
 time: 'Just now',
 })
 unreadCount.value++
 playNotificationSound()
 })

 echoChannel.listen('.WalletCredited', (e) => {
 notifications.value.unshift({
 type: 'success',
 title: 'Wallet Credited',
 message: e.message,
 time: 'Just now',
 })
 unreadCount.value++
 playNotificationSound()
 })
 }
})

onUnmounted(() => {
 if (echoChannel) {
 echoChannel.stopListening()
 }
})

function playNotificationSound() {
 const audio = new Audio('/sounds/notification.mp3')
 audio.play().catch(() => {})
}

function toggleNotifications() {
 unreadCount.value = 0
}

function logout() {
 Echo.leave(`retailer.${user.id}`)
 // Use Inertia to logout
 usePage().props.ziggy?.url
 // Handle logout via form
 const form = document.createElement('form')
 form.method = 'POST'
 form.action = '/logout'
 document.body.appendChild(form)
 form.submit()
}
</script>

<template>
 <div class="min-h-screen bg-gray-50">
 <Head>
 <title>{{ $page.component?.props?.pageTitle || 'Dashboard' }} - MK Network</title>
 </Head>

 <!-- Top Navigation -->
 <nav class="bg-white shadow-sm border-b border-gray-200">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
 <div class="flex justify-between h-16">
 <!-- Left: Logo & Mobile Menu -->
 <div class="flex">
 <button @click="showMobileMenu = !showMobileMenu" class="sm:hidden p-2 text-gray-600 hover:text-gray-900">
 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
 </svg>
 </button>
 <Link href="/retailer" class="flex items-center px-4">
 <div class="text-2xl font-bold text-blue-600">MK</div>
 <div class="ml-2 hidden sm:block">
 <div class="text-sm font-semibold text-gray-900">Network</div>
 <div class="text-xs text-gray-500">Retailer Portal</div>
 </div>
 </Link>
 </div>

 <!-- Desktop Navigation -->
 <div class="hidden sm:flex sm:items-center sm:space-x-1">
 <Link href="/retailer" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100" :class="$page.url.startsWith('/retailer') && !$page.url.includes('recharge') && !$page.url.includes('wallet') && !$page.url.includes('transactions') ? 'bg-gray-100 text-gray-900' : 'text-gray-600'">Dashboard</Link>
 <Link href="/retailer/recharge" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100" :class="$page.url.includes('/recharge') ? 'bg-gray-100 text-gray-900' : 'text-gray-600'">Recharge</Link>
 <Link href="/retailer/transactions" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100" :class="$page.url.includes('/transactions') ? 'bg-gray-100 text-gray-900' : 'text-gray-600'">Transactions</Link>
 <Link href="/retailer/wallet" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100" :class="$page.url.includes('/wallet') ? 'bg-gray-100 text-gray-900' : 'text-gray-600'">Wallet</Link>
 </div>

 <!-- Right: Notifications & Profile -->
 <div class="flex items-center space-x-4">
 <!-- Notifications -->
 <div class="relative">
 <button @click="toggleNotifications" class="p-2 text-gray-600 hover:text-gray-900 relative">
 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
 </svg>
 <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
 {{ unreadCount > 9 ? '9+' : unreadCount }}
 </span>
 </button>

 <!-- Notification Dropdown -->
 <div v-if="notifications.length > 0" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
 <div class="p-3 border-b border-gray-200">
 <h3 class="font-semibold text-gray-900">Notifications</h3>
 </div>
 <div class="max-h-96 overflow-y-auto">
 <div v-for="notif in notifications.slice(0, 5)" :key="notif.time"
 :class="['p-3 border-b border-gray-100 last:border-0', notif.type === 'success' ? 'bg-green-50' : 'bg-red-50']">
 <div class="flex items-start">
 <div class="flex-1">
 <p class="text-sm font-medium text-gray-900">{{ notif.title }}</p>
 <p class="text-sm text-gray-600">{{ notif.message }}</p>
 <p class="text-xs text-gray-400 mt-1">{{ notif.time }}</p>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <!-- Profile Dropdown -->
 <div class="relative">
 <button class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded-lg">
 <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
 {{ user.name.charAt(0).toUpperCase() }}
 </div>
 <span class="hidden md:block text-sm font-medium text-gray-700">{{ user.name }}</span>
 </button>
 </div>
 </div>
 </div>
 </div>

 <!-- Mobile Menu -->
 <div v-if="showMobileMenu" class="sm:hidden border-t border-gray-200">
 <div class="pt-2 pb-3 space-y-1">
 <Link href="/retailer" class="block px-3 py-2 text-base font-medium hover:bg-gray-100">Dashboard</Link>
 <Link href="/retailer/recharge" class="block px-3 py-2 text-base font-medium hover:bg-gray-100">Recharge</Link>
 <Link href="/retailer/transactions" class="block px-3 py-2 text-base font-medium hover:bg-gray-100">Transactions</Link>
 <Link href="/retailer/wallet" class="block px-3 py-2 text-base font-medium hover:bg-gray-100">Wallet</Link>
 <Link href="/retailer/profile" class="block px-3 py-2 text-base font-medium hover:bg-gray-100">Profile</Link>
 </div>
 </div>
 </nav>

 <!-- Main Content -->
 <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
 <slot />
 </main>

 <!-- Footer -->
 <footer class="bg-white border-t border-gray-200 mt-auto">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
 <p class="text-center text-sm text-gray-500"> MK Network Communications. All rights reserved.</p>
 </div>
 </footer>
 </div>
</template>
