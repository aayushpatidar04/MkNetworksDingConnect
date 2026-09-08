<script setup>
import { Head, router } from "@inertiajs/vue3";
import RetailerLayout from "@/Layouts/RetailerLayout.vue";
import { ref, onMounted } from "vue";

defineOptions({ layout: RetailerLayout });

const props = defineProps({
    wallet: Object,
    availableBalance: Number,
});

const selectedAmount = ref(null);
const customAmount = ref("");
const processing = ref(false);
const errorMessage = ref("");
const rzpInstance = ref(null);

const presetAmounts = [50, 100, 200, 500, 1000, 2000];

function selectAmount(amount) {
    selectedAmount.value = amount;
    customAmount.value = "";
    errorMessage.value = "";
}

async function initiateTopUp() {
    const amount = customAmount.value || selectedAmount.value;
    if (!amount || amount < 10) {
        errorMessage.value = "Please select or enter a minimum amount of £10";
        return;
    }

    processing.value = true;
    errorMessage.value = "";

    try {
        const res = await fetch("/retailer/wallet/topup", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({ amount }),
        });

        const data = await res.json();

        if (!data.success) {
            errorMessage.value = data.error || "Failed to initiate top-up";
            processing.value = false;
            return;
        }

        openRazorpay(data);
    } catch (e) {
        errorMessage.value = "Network error. Please try again.";
        processing.value = false;
    }
}

function openRazorpay(data) {
    const options = {
        key: data.razorpay_key,
        amount: Math.round(data.amount * 100),
        currency: data.currency || "GBP",
        name: "MK Network",
        description: "Wallet Top-Up",
        order_id: data.order_id,
        handler: async function (response) {
            processing.value = true;
            errorMessage.value = "";
            
            try {
                const verifyRes = await fetch("/retailer/wallet/verify", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                    body: JSON.stringify({
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature,
                    }),
                });

                const verifyData = await verifyRes.json();
                console.log(verifyData);
                if (verifyData.success) {
                    router.visit("/retailer/wallet");
                } else {
                    errorMessage.value =
                        verifyData.error ||
                        "Payment verification failed. Please contact support.";
                    processing.value = false;
                }
            } catch (e) {
                errorMessage.value =
                    "Verification failed. Please contact support.";
                processing.value = false;
            }
        },
        theme: {
            color: "#4F46E5",
        },
    };

    if (rzpInstance.value) {
        rzpInstance.value.destroy();
    }

    rzpInstance.value = new window.Razorpay(options);
    rzpInstance.value.on("payment.failed", function (response) {
        errorMessage.value =
            response.error.description || "Payment failed. Please try again.";
        processing.value = false;
    });
    rzpInstance.value.open();
}

onMounted(() => {
    if (!window.Razorpay) {
        const script = document.createElement("script");
        script.src = "https://checkout.razorpay.com/v1/checkout.js";
        document.head.appendChild(script);
    }
});
</script>

<template>
    <Head title="Top Up Wallet - MK Network" />
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-white mb-1">Top Up Wallet</h1>
            <p class="text-dark-300">Add funds to your wallet balance</p>
        </div>

        <!-- Current Balance -->
        <div class="bg-dark-800 rounded-2xl border border-dark-600 p-6">
            <div class="text-sm text-dark-300 mb-1">Current Wallet Balance</div>
            <div class="text-4xl font-bold text-white">
                £ {{ Number(wallet.balance).toFixed(2) }}
            </div>
            <div class="text-sm text-dark-400 mt-1">
                Available: £ {{ Number(availableBalance).toFixed(2) }}
            </div>
        </div>

        <!-- Error Message -->
        <div
            v-if="errorMessage"
            class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-red-300 text-sm"
        >
            {{ errorMessage }}
        </div>

        <!-- Amount Selection -->
        <div class="bg-dark-800 rounded-2xl border border-dark-600 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Select Amount</h3>
            <div class="grid grid-cols-3 gap-3 mb-4">
                <button
                    v-for="amount in presetAmounts"
                    :key="amount"
                    type="button"
                    @click="selectAmount(amount)"
                    :class="[
                        'py-4 rounded-xl font-semibold text-lg transition',
                        selectedAmount === amount
                            ? 'bg-primary text-white border-2 border-primary'
                            : 'bg-dark-700 text-white border-2 border-dark-600 hover:border-primary',
                    ]"
                >
                    £ {{ amount }}
                </button>
            </div>

            <!-- Custom Amount -->
            <div>
                <label class="block text-sm font-medium text-dark-200 mb-2"
                    >Or enter custom amount</label
                >
                <div class="relative">
                    <span
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 font-medium"
                        >£</span
                    >
                    <input
                        v-model="customAmount"
                        type="number"
                        min="10"
                        max="50000"
                        step="1"
                        placeholder="Enter amount (min £10)"
                        class="w-full border border-dark-600 rounded-lg pl-8 pr-4 py-3 bg-dark-700 text-white outline-none focus:border-primary"
                    />
                </div>
            </div>
        </div>

        <!-- Pay Button -->
        <button
            type="button"
            @click="initiateTopUp"
            :disabled="processing"
            class="w-full py-4 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark disabled:opacity-50 transition text-base"
        >
            {{ processing ? "Processing..." : "Pay with Razorpay" }}
        </button>
    </div>
</template>
