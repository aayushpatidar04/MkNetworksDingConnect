<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Echo } from 'laravel-echo'

const showMobileMenu = ref(false)
const notifications = ref([])
let echoChannel = null

const user = usePage().props.auth.user

onMounted(() => {
 if (user) {
 echoChannel = Echo.private(`admin.${user.id}`)
 echoChannel.listen('.NewTransaction', (e) => {
 notifications.value.unshift(e)
 })
 }
})

onUnmounted(() => {
 if (echoChannel) echoChannel.stopListening()
})
</script>

<template>
 <div class="min-h-screen bg-gray-100">
 <Head>
 <title>{{ $page.component?.props?.pageTitle || 'Admin Panel' }} - MK Network</title>
 </Head>

 <!-- Sidebar + Top Nav -->
 <div class="flex h-screen">
 <!-- Sidebar -->
 <aside :class="['bg-gray-900 text-white transition-all duration-300', showMobileMenu ? 'w-64' : 'w-16']">
 <div class="p-4">
 <Link href="/admin" class="flex items-center">
 <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold">MK</div>
 <span v-if="showMobileMenu" class="ml-3 font-bold text-lg">Admin</span>
 </Link>
 </div>

 <nav class="mt-4 space-y-1 px-2">
 <Link href="/admin" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url === '/admin' || $page.url.startsWith('/admin/dashboard') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m7-7l7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Dashboard</span>
 </Link>

 <Link href="/admin/retailers" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url.includes('/retailers') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Retailers</span>
 </Link>

 <Link href="/admin/transactions" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url.includes('/transactions') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Transactions</span>
 </Link>

 <Link href="/admin/commissions" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url.includes('/commissions') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Commissions</span>
 </Link>

 <Link href="/admin/operators" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url.includes('/operators') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Operators</span>
 </Link>

 <Link href="/admin/settings" class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-800" :class="$page.url.includes('/settings') ? 'bg-gray-800' : ''">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
 </svg>
 <span v-if="showMobileMenu" class="ml-3">Settings</span>
 </Link>
 </nav>
 </aside>

 <!-- Main Content -->
 <div class="flex-1 flex flex-col overflow-hidden">
 <!-- Top bar -->
 <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
 <button @click="showMobileMenu = !showMobileMenu" class="lg:hidden p-2 text-gray-600">
 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
 </svg>
 </button>
 <h1 class="text-xl font-semibold text-gray-900">{{ $page.props.pageTitle || 'Dashboard' }}</h1>
 <div class="flex items-center space-x-4">
 <span class="text-sm text-gray-600">{{ $page.props.auth.user.name }}</span>
 <form method="POST" action="/logout" class="inline">
 <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
 </form>
 </div>
 </header>

 <!-- Page Content -->
 <main class="flex-1 overflow-y-auto p-6">
 <slot />
 </main>
 </div>
 </div>
 </div>
</template>
