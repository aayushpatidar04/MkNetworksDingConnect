<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
defineOptions({ layout: AppLayout })

const features = [
 {
 title: 'Instant Recharges',
 description: 'Process mobile top-ups in seconds across all major operators and countries.',
 icon: 'M13 10V3L4 14h7v7l9-11h-7z',
 },
 {
 title: 'Secure & Reliable',
 description: 'Bank-grade security with real-time transaction tracking and instant notifications.',
 icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
 },
 {
 title: 'Wallet Management',
 description: 'Prepaid wallet system with multiple top-up options. No minimum balance required.',
 icon: 'M3 10h18M7 15h1m4 0h1m-7 4v-4m14 4v-4M5 10h.01M12 10h.01M19 10h.01',
 },
 {
 title: 'Business Insights',
 description: 'Track your recharges, earnings, and performance with detailed analytics.',
 icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
 },
]

const stats = [
 { label: 'Active Retailers', value: '500+' },
 { label: 'Transactions', value: '1L+' },
 { label: 'Operators', value: '200+' },
 { label: 'Countries', value: '50+' },
]
</script>

<template>
 <div>
 <Head title="MK Network - Mobile Top-Up Platform" />

 <!-- Hero Section -->
 <section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
 <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjEpIi8+PC9zdmc+')] opacity-30"></div>
 <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
 <div class="text-center">
 <h1 class="text-4xl sm:text-6xl font-bold tracking-tight mb-6">
 Mobile Top-Up<br>
 <span class="text-blue-200">Distribution Platform</span>
 </h1>
 <p class="text-xl text-blue-100 max-w-3xl mx-auto mb-10">
 The complete B2B solution for retailers. Fund your wallet, process recharges instantly, and grow your business with MK Network.
 </p>
 <div class="flex flex-col sm:flex-row gap-4 justify-center">
 <Link href="/login" class="px-8 py-4 bg-white text-blue-700 rounded-lg font-semibold text-lg hover:bg-gray-100 transition shadow-lg">
 Retailer Login
 </Link>
 <Link href="/contact" class="px-8 py-4 border-2 border-white text-white rounded-lg font-semibold text-lg hover:bg-white/10 transition">
 Become a Partner
 </Link>
 </div>
 </div>
 </div>
 </section>

 <!-- Stats Section -->
 <section class="py-16 bg-white">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
 <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
 <div v-for="stat in stats" :key="stat.label" class="text-center">
 <div class="text-4xl font-bold text-blue-600">{{ stat.value }}</div>
 <div class="text-gray-600 mt-2">{{ stat.label }}</div>
 </div>
 </div>
 </div>
 </section>

 <!-- Features Section -->
 <section class="py-20 bg-gray-50">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
 <div class="text-center mb-16">
 <h2 class="text-3xl font-bold text-gray-900">Why Choose MK Network?</h2>
 <p class="mt-4 text-lg text-gray-600">Everything you need to run a successful mobile recharge business</p>
 </div>

 <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
 <div v-for="feature in features" :key="feature.title" class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
 <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
 <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon"/>
 </svg>
 </div>
 <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
 <p class="text-gray-600">{{ feature.description }}</p>
 </div>
 </div>
 </div>
 </section>

 <!-- How It Works -->
 <section class="py-20 bg-white">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
 <div class="text-center mb-16">
 <h2 class="text-3xl font-bold text-gray-900">How It Works</h2>
 </div>

 <div class="grid md:grid-cols-3 gap-8">
 <div class="text-center">
 <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
 <h3 class="text-xl font-semibold mb-2">Register & Verify</h3>
 <p class="text-gray-600">Sign up as a retailer and get your KYC verified in 24 hours.</p>
 </div>
 <div class="text-center">
 <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
 <h3 class="text-xl font-semibold mb-2">Fund Your Wallet</h3>
 <p class="text-gray-600">Load your prepaid wallet via UPI, cards, or bank transfer.</p>
 </div>
 <div class="text-center">
 <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
 <h3 class="text-xl font-semibold mb-2">Start Recharging</h3>
 <p class="text-gray-600">Process instant recharges for any operator and earn on every transaction.</p>
 </div>
 </div>
 </div>
 </section>

 <!-- CTA Section -->
 <section class="py-20 bg-blue-600 text-white">
 <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
 <h2 class="text-3xl font-bold mb-4">Ready to Start Your Recharge Business?</h2>
 <p class="text-xl text-blue-100 mb-8">Join 500+ retailers already using MK Network for their top-up needs.</p>
 <div class="flex flex-col sm:flex-row gap-4 justify-center">
 <Link href="/register" class="px-8 py-4 bg-white text-blue-700 rounded-lg font-semibold text-lg hover:bg-gray-100 transition">
 Register Now
 </Link>
 <Link href="/contact" class="px-8 py-4 border-2 border-white text-white rounded-lg font-semibold text-lg hover:bg-white/10 transition">
 Contact Sales
 </Link>
 </div>
 </div>
 </section>
 </div>
 </template>
