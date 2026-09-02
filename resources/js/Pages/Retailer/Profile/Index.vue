<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import RetailerLayout from '@/Layouts/RetailerLayout.vue'

const props = defineProps({ user: Object })

const form = useForm({
 name: props.user.name,
 email: props.user.email,
 phone: props.user.phone,
 shop_name: props.user.shop_name || '',
 address: props.user.address || '',
 city: props.user.city || '',
 state: props.user.state || '',
 pincode: props.user.pincode || '',
})

function submit() { form.put('/retailer/profile') }
</script>

<template>
 <Head title="Profile - MK Network" />
 <div class="max-w-2xl space-y-6">
 <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>

 <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ $page.props.flash.success }}</div>
 <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ $page.props.flash.error }}</div>

 <form @submit.prevent="submit" class="bg-white rounded-xl p-6 shadow-sm space-y-4">
 <div class="grid grid-cols-2 gap-4">
 <div><label class="text-sm font-medium">Full Name</label><input v-model="form.name" class="w-full border rounded px-3 py-2" required></div>
 <div><label class="text-sm font-medium">Email</label><input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" required></div>
 </div>
 <div><label class="text-sm font-medium">Phone</label><input v-model="form.phone" class="w-full border rounded px-3 py-2" required></div>
 <div><label class="text-sm font-medium">Shop Name</label><input v-model="form.shop_name" class="w-full border rounded px-3 py-2"></div>
 <div><label class="text-sm font-medium">Address</label><textarea v-model="form.address" class="w-full border rounded px-3 py-2" rows="2"></textarea></div>
 <div class="grid grid-cols-3 gap-4">
 <div><label class="text-sm font-medium">City</label><input v-model="form.city" class="w-full border rounded px-3 py-2"></div>
 <div><label class="text-sm font-medium">State</label><input v-model="form.state" class="w-full border rounded px-3 py-2"></div>
 <div><label class="text-sm font-medium">Pincode</label><input v-model="form.pincode" class="w-full border rounded px-3 py-2"></div>
 </div>
 <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">Update Profile</button>
 </form>

 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4">Change Password</h3>
 <form @submit.prevent="form.put('/retailer/profile')" class="space-y-4">
 <div><label class="text-sm font-medium">Current Password</label><input type="password" class="w-full border rounded px-3 py-2"></div>
 <div><label class="text-sm font-medium">New Password</label><input type="password" class="w-full border rounded px-3 py-2"></div>
 <div><label class="text-sm font-medium">Confirm Password</label><input type="password" class="w-full border rounded px-3 py-2"></div>
 <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Change Password</button>
 </form>
 </div>
 </div>
</template>
