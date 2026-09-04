<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";

defineOptions({ layout: AdminLayout });
</script>

<template>
    <Head title="Dashboard - Admin" />
    <div class="text-center py-20">
        <h2 class="text-2xl font-bold text-white mb-2">Dashboard</h2>
        <p class="text-dark-300">Welcome to MK Network Admin Panel</p>
        <p class="text-dark-400 text-sm mt-4">
            Use the sidebar to navigate between sections
        </p>
    </div>
</template>
