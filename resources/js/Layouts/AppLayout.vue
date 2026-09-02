<script setup>
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <Head>
            <title>
                {{ $page.component?.props?.pageTitle || "MK Network" }}
            </title>
        </Head>

        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center">
                        <div class="text-2xl font-bold text-blue-600">
                            MK Network
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link
                            href="/about"
                            class="text-gray-600 hover:text-gray-900 font-medium"
                            >About</Link
                        >
                        <Link
                            href="/pricing"
                            class="text-gray-600 hover:text-gray-900 font-medium"
                            >Pricing</Link
                        >
                        <Link
                            href="/contact"
                            class="text-gray-600 hover:text-gray-900 font-medium"
                            >Contact</Link
                        >
                        <Link
                            href="/login"
                            class="text-gray-600 hover:text-gray-900 font-medium"
                            >Login</Link
                        >
                        <Link
                            href="/register"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition"
                            >Register</Link
                        >
                    </nav>

                    <!-- Mobile menu button -->
                    <button class="md:hidden p-2 text-gray-600">
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="text-2xl font-bold">MK Network</div>
                        <p class="mt-2 text-gray-400 text-sm">
                            Complete B2B mobile top-up distribution platform for
                            retailers.
                        </p>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-3">Quick Links</h3>
                        <div class="space-y-2">
                            <Link
                                href="/about"
                                class="block text-gray-400 hover:text-white text-sm"
                                >About Us</Link
                            >
                            <Link
                                href="/pricing"
                                class="block text-gray-400 hover:text-white text-sm"
                                >Pricing</Link
                            >
                            <Link
                                href="/contact"
                                class="block text-gray-400 hover:text-white text-sm"
                                >Contact</Link
                            >
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-3">Contact</h3>
                        <p class="text-gray-400 text-sm">
                            MK Network Communications
                        </p>
                        <p class="text-gray-400 text-sm">
                            support@mknetwork.com
                        </p>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-6 text-center">
                    <p class="text-gray-400 text-sm">
                        MK Network Communications. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
