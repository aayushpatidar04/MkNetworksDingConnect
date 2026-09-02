<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import RetailerLayout from '@/Layouts/RetailerLayout.vue'
import { ref, onMounted } from 'vue'
defineOptions({ layout: RetailerLayout })

const props = defineProps({ availableBalance: Number, countries: Array })

const selectedCountry = ref(null)
const operators = ref([])
const selectedOperator = ref(null)
const estimatedPrice = ref(null)

const form = useForm({
 mobile_number: '',
 operator_id: '',
 country_id: '',
 amount: 10,
})

function loadOperators() {
 if (!form.country_id) { operators.value = []; return }
 fetch(`/retailer/recharge/operators?country_id=${form.country_id}`)
 .then(r => r.json())
 .then(data => { operators.value = data.operators; selectedOperator.value = null })
}

function calculatePricing() {
 if (!form.operator_id || !form.amount) { estimatedPrice.value = null; return }
 fetch(`/retailer/recharge/pricing?operator_id=${form.operator_id}&amount=${form.amount}`)
 .then(r => r.json())
 .then(data => { estimatedPrice.value = data })
}

function submit() {
 if (estimatedPrice.value && props.availableBalance < estimatedPrice.value.retailer_charged) {
 alert('Insufficient wallet balance!')
 return
 }
 form.post('/retailer/recharge', {
 onSuccess: () => { form.reset(); estimatedPrice.value = null; operators.value = [] }
 })
}

onMounted(() => { loadOperators() })
</script>

<template>
 <Head title="New Recharge - MK Network" />
 <div class="max-w-2xl">
 <h1 class="text-2xl font-bold text-gray-900 mb-6">New Recharge</h1>

 <div v-if="availableBalance < 10" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
 <p class="text-red-700">Your wallet balance is low. <Link href="/retailer/wallet" class="underline">Top up now</Link></p>
 </div>

 <form @submit.prevent="submit" class="bg-white rounded-xl p-6 shadow-sm space-y-5">
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
 <select v-model="form.country_id" @change="loadOperators" class="w-full border rounded-lg px-4 py-3">
 <option value="">Select Country</option>
 <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }} ({{ c.calling_code }})</option>
 </select>
 </div>

 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
 <select v-model="form.operator_id" @change="calculatePricing" :disabled="!operators.length" class="w-full border rounded-lg px-4 py-3" required>
 <option value="">Select Operator</option>
 <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
 </select>
 </div>

 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
 <input v-model="form.mobile_number" type="text" placeholder="Enter mobile number" class="w-full border rounded-lg px-4 py-3" required>
 </div>

 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Rs.)</label>
 <input v-model="form.amount" type="number" min="10" max="10000" step="1" @input="calculatePricing" class="w-full border rounded-lg px-4 py-3" required>
 </div>

 <!-- Pricing Preview -->
 <div v-if="estimatedPrice" class="bg-blue-50 rounded-lg p-4 space-y-1">
 <div class="flex justify-between text-sm"><span class="text-gray-600">DingConnect Cost:</span><span>Rs. {{ Number(estimatedPrice.ding_cost).toFixed(2) }}</span></div>
 <div class="flex justify-between text-sm"><span class="text-gray-600">Commission ({{ (estimatedPrice.commission_rate * 100).toFixed(2) }}%):</span><span>+Rs. {{ Number(estimatedPrice.commission_amount).toFixed(2) }}</span></div>
 <div class="flex justify-between text-base font-bold border-t pt-2"><span class="text-gray-700">You Pay:</span><span class="text-blue-600">Rs. {{ Number(estimatedPrice.retailer_charged).toFixed(2) }}</span></div>
 </div>

 <button type="submit" :disabled="form.processing || !estimatedPrice" class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50">
 {{ form.processing ? 'Processing...' : 'Recharge Now' }}
 </button>
 </form>
 </div>
</template>
