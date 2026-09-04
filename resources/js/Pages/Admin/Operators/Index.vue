<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
defineOptions({ layout: AdminLayout });

const props = defineProps({ operators: Object, countries: Array });
</script>

<template>
    <Head title="Operators - Admin" />
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Operators</h1>
                <p class="text-dark-300">Manage mobile operators</p>
            </div>
            <button
                @click="$page.props.flash"
                class="px-4 py-2 btn-primary text-white rounded-lg text-sm font-medium"
            >
                Sync from Ding
            </button>
        </div>

        <div
            class="bg-dark-800 rounded-2xl border border-dark-600 overflow-hidden"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-dark-600">
                    <thead class="bg-dark-700">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Operator
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Ding ID
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Country
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-600">
                        <tr
                            v-for="op in operators.data"
                            :key="op.id"
                            class="hover:bg-dark-700 transition"
                        >
                            <td
                                class="px-4 py-3 text-sm font-medium text-white"
                            >
                                {{ op.name }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm font-mono text-dark-300"
                            >
                                {{ op.ding_operator_id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-dark-200">
                                {{ op.country?.name || "-" }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        op.is_active
                                            ? 'bg-accent/20 text-accent-light'
                                            : 'bg-dark-600 text-dark-400',
                                    ]"
                                    >{{
                                        op.is_active ? "Active" : "Inactive"
                                    }}</span
                                >
                            </td>
                        </tr>
                        <tr v-if="!operators.data?.length">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-dark-400"
                            >
                                No operators found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
