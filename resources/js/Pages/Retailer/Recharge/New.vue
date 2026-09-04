<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import RetailerLayout from "@/Layouts/RetailerLayout.vue";
import { ref, onMounted } from "vue";

defineOptions({ layout: RetailerLayout });

const props = defineProps({ availableBalance: Number, countries: Array });

const selectedCountry = ref(null);
const operators = ref([]);
const selectedOperator = ref(null);
const estimatedPrice = ref(null);

const form = useForm({
    mobile_number: "",
    operator_id: "",
    country_id: "",
    amount: 10,
});

function loadOperators() {
    if (!form.country_id) {
        operators.value = [];
        return;
    }
    fetch(`/retailer/recharge/operators?country_id=${form.country_id}`)
        .then((r) => r.json())
        .then((data) => {
            operators.value = data.operators;
            selectedOperator.value = null;
        });
}

function calculatePricing() {
    if (!form.operator_id || !form.amount) {
        estimatedPrice.value = null;
        return;
    }
    fetch(
        `/retailer/recharge/pricing?operator_id=${form.operator_id}&amount=${form.amount}`,
    )
        .then((r) => r.json())
        .then((data) => {
            estimatedPrice.value = data;
        });
}

function submit() {
    if (
        estimatedPrice.value &&
        props.availableBalance < estimatedPrice.value.retailer_charged
    ) {
        alert("Insufficient wallet balance!");
        return;
    }
    form.post("/retailer/recharge", {
        onSuccess: () => {
            form.reset();
            estimatedPrice.value = null;
            operators.value = [];
        },
    });
}

onMounted(() => {
    loadOperators();
});
</script>

<template>
    <Head title="New Recharge - MK Network" />
    <div class="max-w-2xl">
        <h1 class="text-3xl font-bold text-white mb-1">New Recharge</h1>
        <p class="text-dark-300 mb-6">Instant mobile top-up for any operator</p>

        <div
            v-if="availableBalance < 10"
            class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 mb-6"
        >
            <p class="text-red-400">
                Your wallet balance is low.
                <Link
                    href="/retailer/wallet"
                    class="underline hover:text-red-300"
                    >Top up now</Link
                >
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="bg-dark-800 rounded-2xl p-6 border border-dark-600 space-y-5"
        >
            <div>
                <label class="block text-sm font-medium text-dark-200 mb-2"
                    >Country</label
                >
                <select
                    v-model="form.country_id"
                    @change="loadOperators"
                    class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white input-dark"
                >
                    <option value="">Select Country</option>
                    <option v-for="c in countries" :key="c.id" :value="c.id">
                        {{ c.name }} ({{ c.calling_code }})
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-dark-200 mb-2"
                    >Operator</label
                >
                <select
                    v-model="form.operator_id"
                    @change="calculatePricing"
                    :disabled="!operators.length"
                    class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white input-dark"
                    required
                >
                    <option value="">Select Operator</option>
                    <option v-for="op in operators" :key="op.id" :value="op.id">
                        {{ op.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-dark-200 mb-2"
                    >Mobile Number</label
                >
                <input
                    v-model="form.mobile_number"
                    type="text"
                    placeholder="Enter mobile number"
                    class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white input-dark"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-dark-200 mb-2"
                    >Amount (Rs.)</label
                >
                <input
                    v-model="form.amount"
                    type="number"
                    min="10"
                    max="10000"
                    step="1"
                    @input="calculatePricing"
                    class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white input-dark"
                    required
                />
            </div>

            <!-- Pricing Preview -->
            <div
                v-if="estimatedPrice"
                class="bg-primary/10 rounded-xl p-4 space-y-1 border border-primary/20"
            >
                <div class="flex justify-between text-sm">
                    <span class="text-dark-300">DingConnect Cost:</span
                    ><span class="text-dark-200"
                        >Rs.
                        {{ Number(estimatedPrice.ding_cost).toFixed(2) }}</span
                    >
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-300"
                        >Commission ({{
                            (estimatedPrice.commission_rate * 100).toFixed(2)
                        }}%):</span
                    ><span class="text-accent-light"
                        >+Rs.
                        {{
                            Number(estimatedPrice.commission_amount).toFixed(2)
                        }}</span
                    >
                </div>
                <div
                    class="flex justify-between text-base font-bold border-t border-primary/20 pt-2"
                >
                    <span class="text-white">You Pay:</span
                    ><span class="text-primary-light"
                        >Rs.
                        {{
                            Number(estimatedPrice.retailer_charged).toFixed(2)
                        }}</span
                    >
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing || !estimatedPrice"
                class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary-dark disabled:opacity-50 transition"
            >
                {{ form.processing ? "Processing..." : "Recharge Now" }}
            </button>
        </form>
    </div>
</template>
