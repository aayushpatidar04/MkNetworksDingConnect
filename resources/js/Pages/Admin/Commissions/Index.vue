<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
defineOptions({ layout: AdminLayout });

const props = defineProps({ rules: Array });

const form = useForm({
    name: "",
    description: "",
    scope: "global",
    operator_id: "",
    country_id: "",
    retailer_tier: "",
    min_amount: "",
    max_amount: "",
    commission_type: "percentage",
    commission_value: "",
    priority: 0,
    effective_from: new Date().toISOString().split("T")[0],
    effective_until: "",
});

function submit() {
    form.post("/admin/commissions", { onSuccess: () => form.reset() });
}
</script>

<template>
    <Head title="Commission Rules - Admin" />
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-white mb-1">Commission Rules</h1>
            <p class="text-dark-300">Configure pricing and commission tiers</p>
        </div>

        <!-- Add Rule Form -->
        <div class="bg-dark-800 rounded-2xl p-6 border border-dark-600">
            <h3 class="text-lg font-semibold text-white mb-4">Add New Rule</h3>
            <form
                @submit.prevent="submit"
                class="grid grid-cols-1 md:grid-cols-3 gap-4"
            >
                <div>
                    <label class="text-xs text-dark-300 block mb-1">Name</label
                    ><input
                        v-model="form.name"
                        required
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1">Scope</label
                    ><select
                        v-model="form.scope"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    >
                        <option value="global">Global</option>
                        <option value="operator">Per Operator</option>
                        <option value="retailer_tier">Per Tier</option>
                        <option value="country">Per Country</option>
                        <option value="amount_band">Amount Band</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1">Type</label
                    ><select
                        v-model="form.commission_type"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    >
                        <option value="percentage">Percentage</option>
                        <option value="flat">Flat Fee</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1">Value</label
                    ><input
                        v-model="form.commission_value"
                        type="number"
                        step="0.01"
                        required
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1"
                        >Min Amount</label
                    ><input
                        v-model="form.min_amount"
                        type="number"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1"
                        >Max Amount</label
                    ><input
                        v-model="form.max_amount"
                        type="number"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1"
                        >Retailer Tier</label
                    ><select
                        v-model="form.retailer_tier"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    >
                        <option value="">Any</option>
                        <option value="bronze">Bronze</option>
                        <option value="silver">Silver</option>
                        <option value="gold">Gold</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1"
                        >Priority</label
                    ><input
                        v-model="form.priority"
                        type="number"
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div>
                    <label class="text-xs text-dark-300 block mb-1"
                        >Effective From</label
                    ><input
                        v-model="form.effective_from"
                        type="date"
                        required
                        class="w-full border border-dark-600 rounded-lg px-3 py-2 text-sm bg-dark-700 text-white input-dark"
                    />
                </div>
                <div class="md:col-span-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark disabled:opacity-50 transition"
                    >
                        Add Rule
                    </button>
                </div>
            </form>
        </div>

        <!-- Rules List -->
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
                                Name
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Scope
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Type
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Value
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Priority
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-dark-200 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-600">
                        <tr
                            v-for="rule in rules"
                            :key="rule.id"
                            class="hover:bg-dark-700 transition"
                        >
                            <td
                                class="px-4 py-3 text-sm font-medium text-white"
                            >
                                {{ rule.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-dark-200">
                                {{ rule.scope }}
                            </td>
                            <td class="px-4 py-3 text-sm text-dark-200">
                                {{ rule.commission_type }}
                            </td>
                            <td class="px-4 py-3 text-sm text-dark-200">
                                {{ rule.commission_value
                                }}{{
                                    rule.commission_type === "percentage"
                                        ? "%"
                                        : " Rs"
                                }}
                            </td>
                            <td class="px-4 py-3 text-sm text-dark-200">
                                {{ rule.priority }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        rule.is_active
                                            ? 'bg-accent/20 text-accent-light'
                                            : 'bg-dark-600 text-dark-400',
                                    ]"
                                    >{{
                                        rule.is_active ? "Active" : "Inactive"
                                    }}</span
                                >
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <form
                                    :action="`/admin/commissions/${rule.id}`"
                                    method="POST"
                                >
                                    <input
                                        type="hidden"
                                        name="_method"
                                        value="DELETE"
                                    /><input
                                        type="hidden"
                                        name="_token"
                                        :value="$page.props.csrf_token"
                                    /><button
                                        type="submit"
                                        class="text-red-400 hover:text-red-300 transition"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
