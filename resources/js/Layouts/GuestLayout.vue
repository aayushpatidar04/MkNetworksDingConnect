<script setup>
import { ref } from "vue";
import MkLogo from "@/Components/MkLogo.vue";
</script>

<template>
    <div
        class="min-h-screen flex items-center justify-center bg-dark-900 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden"
    >
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 -right-40 w-80 h-80 bg-primary/10 rounded-full blur-3xl"
            ></div>
            <div
                class="absolute -bottom-40 -left-40 w-80 h-80 bg-accent/10 rounded-full blur-3xl"
            ></div>
        </div>

        <div class="max-w-md w-full relative z-10">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <Link href="/">
                    <MkLogo size="xl" />
                </Link>
            </div>

            <!-- Card -->
            <div
                class="bg-dark-800 rounded-2xl shadow-2xl border border-dark-600 p-8"
            >
                <slot />
            </div>

            <p class="mt-6 text-center text-sm text-dark-400">
                MK Network Communications. All rights reserved.
            </p>
        </div>
    </div>
</template>
