<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import RetailerLayout from "@/Layouts/RetailerLayout.vue";
import { ref, onMounted, computed } from "vue";

defineOptions({ layout: RetailerLayout });

const props = defineProps({
    availableBalance: Number,
    countries: Array,
});

const currentStep = ref(1);
const selectedCountry = ref(null);
const operators = ref([]);
const selectedOperator = ref(null);
const products = ref([]);
const selectedProduct = ref(null);
const loadingProducts = ref(false);
const mobileNumber = ref("");
const submitting = ref(false);
const errorMessage = ref("");

const form = useForm({
    mobile_number: "",
    operator_id: "",
    country_id: "",
    amount: 0,
});

const canProceedToProvider = computed(() => !!selectedCountry.value);
const canProceedToPlan = computed(() => !!selectedOperator.value);
const canProceedToConfirm = computed(() => {
    return (
        !!selectedProduct.value &&
        mobileNumber.value.replace(/\D/g, "").length >= 10
    );
});

const totalSteps = 4;
const stepNames = ["Country", "Operator", "Plan", "Confirm"];

function parseValidityPeriod(iso) {
    if (!iso || iso.trim() === "") return null;

    const match = iso.match(
        /P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?/,
    );
    if (!match) return null;

    const years = parseInt(match[1] || "0");
    const months = parseInt(match[2] || "0");
    const weeks = parseInt(match[3] || "0");
    const days = parseInt(match[4] || "0");
    const hours = parseInt(match[5] || "0");

    const parts = [];
    if (years > 0) parts.push(years + (years === 1 ? " year" : " years"));
    if (months > 0) parts.push(months + (months === 1 ? " month" : " months"));
    if (weeks > 0) parts.push(weeks + (weeks === 1 ? " week" : " weeks"));
    if (days > 0) parts.push(days + (days === 1 ? " day" : " days"));
    if (hours > 0) parts.push(hours + (hours === 1 ? " hour" : " hours"));

    if (parts.length === 0) return null;

    const totalDays = years * 365 + months * 30 + weeks * 7 + days;
    if (totalDays >= 7 && hours === 0) {
        return "Expires in " + totalDays + " days";
    }

    return "Expires in " + parts.join(", ");
}

function formatValidity(iso) {
    const result = parseValidityPeriod(iso);
    if (result) return result;
    if (iso && iso.trim() !== "") return iso;
    return null;
}

function selectCountry(country) {
    selectedCountry.value = country;
    currentStep.value = 2;
    loadOperators();
}

function selectOperator(operator) {
    selectedOperator.value = operator;
    currentStep.value = 3;
    loadProducts();
}

function selectProduct(product) {
    selectedProduct.value = product;
    form.amount = product.receive_value;
    currentStep.value = 4;
}

function backToStep(step) {
    currentStep.value = step;
    errorMessage.value = "";
}

async function loadOperators() {
    const res = await fetch(
        `/retailer/recharge/operators?country_id=${selectedCountry.value.id}`,
    );
    const data = await res.json();
    operators.value = data.operators || [];
}

async function loadProducts() {
    loadingProducts.value = true;
    products.value = [];
    try {
        const res = await fetch(
            `/retailer/recharge/products?provider_code=${selectedOperator.value.provider_code}&country_iso=${selectedCountry.value.iso_code}`,
        );
        const data = await res.json();
        if (data.success) {
            products.value = data.products;
        } else {
            errorMessage.value = data.error || "Failed to load plans";
        }
    } catch (e) {
        errorMessage.value = "Network error loading plans";
    } finally {
        loadingProducts.value = false;
    }
}

function submitRecharge() {
    errorMessage.value = "";
    if (!canProceedToConfirm.value) {
        errorMessage.value = "Please enter a valid mobile number (10+ digits)";
        return;
    }

    if (props.availableBalance < form.send_value) {
        errorMessage.value =
            "Insufficient wallet balance. Please top up first.";
        return;
    }

    submitting.value = true;
    form.mobile_number = mobileNumber.value.replace(/\D/g, "");
    form.operator_id = selectedOperator.value.id;
    form.country_id = selectedCountry.value.id;

    form.post("/retailer/recharge", {
        onSuccess: () => {},
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || "Recharge failed";
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<template>
    <Head title="New Recharge - MK Network" />
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white mb-1">New Recharge</h1>
            <p class="text-dark-300">
                Instant mobile top-up powered by DingConnect
            </p>
        </div>

        <!-- Wallet Balance Alert -->
        <div
            v-if="availableBalance < 10"
            class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 mb-6 flex items-start"
        >
            <svg
                class="w-5 h-5 text-red-400 mr-3 mt-0.5 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
            </svg>
            <div>
                <p class="text-sm text-red-300">
                    Low wallet balance.
                    <a
                        href="/retailer/wallet"
                        class="underline font-medium hover:text-red-200"
                        >Top up now →</a
                    >
                </p>
            </div>
        </div>

        <!-- Stepper -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <template v-for="(step, idx) in totalSteps" :key="idx">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition',
                                currentStep > idx + 1
                                    ? 'bg-green-500 text-white'
                                    : currentStep === idx + 1
                                      ? 'bg-primary text-white'
                                      : 'bg-dark-700 text-dark-400 border border-dark-600',
                            ]"
                        >
                            <svg
                                v-if="currentStep > idx + 1"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span v-else>{{ idx + 1 }}</span>
                        </div>
                        <div
                            :class="[
                                'text-xs mt-2 font-medium',
                                currentStep >= idx + 1
                                    ? 'text-white'
                                    : 'text-dark-400',
                            ]"
                        >
                            {{ stepNames[idx] }}
                        </div>
                    </div>
                    <div
                        v-if="idx < totalSteps - 1"
                        :class="[
                            'flex-1 h-0.5 mx-2 -mt-6 transition',
                            currentStep > idx + 1
                                ? 'bg-green-500'
                                : 'bg-dark-700',
                        ]"
                    ></div>
                </template>
            </div>
        </div>

        <!-- Error Message -->
        <div
            v-if="errorMessage"
            class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-300 text-sm"
        >
            {{ errorMessage }}
        </div>

        <!-- STEP 1: Select Country -->
        <div v-if="currentStep === 1">
            <h2 class="text-xl font-semibold text-white mb-4">
                Select Country
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <button
                    v-for="country in countries"
                    :key="country.id"
                    @click="selectCountry(country)"
                    class="bg-dark-800 hover:bg-dark-700 border border-dark-600 hover:border-primary rounded-2xl p-5 text-left transition group"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-3xl text-primary-light">{{
                            country.flag_emoji || "🌍"
                        }}</span>
                        <div>
                            <div
                                class="font-semibold text-white group-hover:text-primary-light transition"
                            >
                                {{ country.name }}
                            </div>
                            <div class="text-xs text-dark-400">
                                {{ country.iso_code }} ·
                                {{ country.calling_code }}
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- STEP 2: Select Operator -->
        <div v-if="currentStep === 2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white">
                    Select Operator
                </h2>
                <button
                    @click="backToStep(1)"
                    class="text-sm text-dark-300 hover:text-white transition flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Change country
                </button>
            </div>

            <div
                v-if="!operators.length"
                class="text-center py-12 text-dark-400"
            >
                No operators available for {{ selectedCountry?.name }}
            </div>

            <div
                v-else
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
            >
                <button
                    v-for="op in operators"
                    :key="op.id"
                    @click="selectOperator(op)"
                    class="bg-dark-800 hover:bg-dark-700 border border-dark-600 hover:border-primary rounded-2xl p-4 text-center transition group"
                >
                    <div
                        class="w-16 h-16 mx-auto mb-3 bg-white rounded-xl flex items-center justify-center overflow-hidden"
                    >
                        <img
                            v-if="op.logo_url"
                            :src="op.logo_url"
                            :alt="op.name"
                            class="w-full h-full object-contain p-1"
                        />
                        <span v-else class="text-2xl">📱</span>
                    </div>
                    <div class="font-semibold text-white text-sm">
                        {{ op.name }}
                    </div>
                </button>
            </div>
        </div>

        <!-- STEP 3: Select Plan -->
        <div v-if="currentStep === 3">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white">Select Plan</h2>
                <button
                    @click="backToStep(2)"
                    class="text-sm text-dark-300 hover:text-white transition flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Change operator
                </button>
            </div>

            <div v-if="loadingProducts" class="text-center py-16">
                <svg
                    class="animate-spin h-10 w-10 text-primary mx-auto"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <p class="mt-3 text-dark-300">
                    Loading plans from {{ selectedOperator?.name }}...
                </p>
            </div>

            <div
                v-else-if="!products.length"
                class="text-center py-12 text-dark-400"
            >
                No plans available for this operator
            </div>

            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <button
                    v-for="product in products"
                    :key="product.sku_code"
                    @click="selectProduct(product)"
                    class="bg-dark-800 hover:bg-dark-700 border border-dark-600 hover:border-primary rounded-2xl p-5 text-left transition group"
                >
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="text-2xl font-bold text-white">
                                {{ product.receive_currency }}
                                {{ product.receive_value }}
                            </div>
                            <div
                                v-if="product.display_text"
                                class="text-xs text-dark-400 mt-1"
                            >
                                {{ product.display_text }}
                            </div>
                        </div>
                        <span
                            v-if="product.validity_period"
                            class="text-xs bg-green-500/20 text-green-300 px-2 py-1 rounded"
                        >
                            {{ formatValidity(product.validity_period) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span
                            v-for="benefit in product.benefits"
                            :key="benefit"
                            class="text-xs bg-dark-700 text-dark-300 px-2 py-1 rounded"
                        >
                            {{ benefit }}
                        </span>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-dark-600 flex justify-between items-center"
                    >
                        <span class="text-xs text-dark-400"
                            >You will be charged</span
                        >
                        <span class="font-semibold text-primary-light"
                            >{{ product.send_currency }}
                            {{ product.send_value.toFixed(2) }}</span
                        >
                    </div>
                </button>
            </div>
        </div>

        <!-- STEP 4: Confirm -->
        <div v-if="currentStep === 4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white">
                    Confirm Recharge
                </h2>
                <button
                    @click="backToStep(3)"
                    class="text-sm text-dark-300 hover:text-white transition flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Change plan
                </button>
            </div>

            <div
                class="bg-dark-800 rounded-2xl border border-dark-600 p-6 space-y-6"
            >
                <!-- Selected Plan Summary -->
                <div class="bg-dark-700 rounded-xl p-4">
                    <div class="text-xs text-dark-400 mb-2">Selected Plan</div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 bg-white rounded-lg flex items-center justify-center overflow-hidden"
                        >
                            <img
                                v-if="selectedOperator?.logo_url"
                                :src="selectedOperator.logo_url"
                                class="w-full h-full object-contain p-1"
                            />
                            <span v-else>📱</span>
                        </div>
                        <div>
                            <div class="font-semibold text-white">
                                {{ selectedOperator?.name }}
                            </div>
                            <div class="text-sm text-dark-300">
                                {{ selectedProduct?.receive_currency }}
                                {{ selectedProduct?.receive_value }} ·
                                {{ selectedProduct?.display_text }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Number Input -->
                <div>
                    <label class="block text-sm font-medium text-dark-200 mb-2"
                        >Mobile Number</label
                    >
                    <div class="flex">
                        <span
                            class="bg-dark-700 border border-r-0 border-dark-600 rounded-l-lg px-4 py-3 text-white font-medium"
                        >
                            {{ selectedCountry?.calling_code }}
                        </span>
                        <input
                            v-model="mobileNumber"
                            type="tel"
                            placeholder="Enter mobile number"
                            class="flex-1 border border-dark-600 rounded-r-lg px-4 py-3 bg-dark-700 text-white outline-none focus:border-primary"
                            @input="
                                mobileNumber = mobileNumber.replace(
                                    /[^0-9]/g,
                                    '',
                                )
                            "
                        />
                    </div>
                    <p class="text-xs text-dark-400 mt-2">
                        Enter number without country code
                    </p>
                </div>

                <!-- Price Summary -->
                <div
                    class="bg-primary/10 border border-primary/20 rounded-xl p-4 space-y-2"
                >
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-300">Recharge value:</span>
                        <span class="text-white font-medium"
                            >{{ selectedProduct?.receive_currency }}
                            {{ selectedProduct?.receive_value }}</span
                        >
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-300">Processing fee:</span>
                        <span class="text-white">Free</span>
                    </div>
                    <div
                        class="flex justify-between text-base font-bold border-t border-primary/20 pt-2"
                    >
                        <span class="text-white">Total charge:</span>
                        <span class="text-primary-light"
                            >{{ selectedProduct?.send_currency }}
                            {{ selectedProduct?.send_value.toFixed(2) }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between text-xs text-dark-400 pt-1"
                    >
                        <span>Wallet balance:</span>
                        <span
                            >Available: £
                            {{ Number(availableBalance || 0).toFixed(2) }}</span
                        >
                    </div>
                </div>

                <button
                    @click="submitRecharge"
                    :disabled="submitting || !canProceedToConfirm"
                    class="w-full py-4 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark disabled:opacity-50 transition text-base"
                >
                    {{
                        submitting
                            ? "Processing Recharge..."
                            : "Confirm & Recharge"
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
