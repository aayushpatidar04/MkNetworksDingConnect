<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ settings: Object, groups: Array })

const flatSettings = Object.values(props.settings).flat()
const form = useForm({ settings: flatSettings.map(s => ({ id: s.id, value: s.value })) })

function submit() { form.put('/admin/settings', { onSuccess: () => alert('Settings saved!') }) }
</script>

<template>
 <Head title="Settings - Admin" />
 <div class="space-y-6">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
 <p class="text-gray-600">Platform configuration</p>
 </div>

 <form @submit.prevent="submit" class="space-y-6">
 <template v-for="group in groups" :key="group">
 <div class="bg-white rounded-xl p-6 shadow-sm">
 <h3 class="text-lg font-semibold mb-4 capitalize">{{ group }}</h3>
 <div class="space-y-4">
 <div v-for="setting in settings[group]" :key="setting.id" class="flex items-center gap-4">
 <label class="w-1/3 text-sm text-gray-700">{{ setting.description || setting.key }}</label>
 <input :value="setting.value" @input="(e) => { const s = form.settings.find(x => x.id === setting.id); if (s) s.value = e.target.value }" type="text" class="flex-1 border rounded px-3 py-2 text-sm">
 </div>
 </div>
 </div>
 </template>
 <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">Save Settings</button>
 </form>
 </div>
</template>
