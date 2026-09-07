<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { ref, computed } from "vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({ operators: Object, countries: Array });

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingOperator = ref(null);
const deletingOperator = ref(null);
const syncing = ref(false);
const search = ref(props.operators.query?.search || "");
const countryFilter = ref(props.operators.query?.country_id || "");

const form = useForm({
    name: "",
    ding_operator_id: "",
    country_id: "",
    logo_url: "",
    display_order: "0",
    is_active: true,
});

function openAddModal() {
    form.reset();
    form.country_id = "";
    form.is_active = true;
    showAddModal.value = true;
}

function openEditModal(op) {
    editingOperator.value = op;
    form.name = op.name;
    form.ding_operator_id = op.ding_operator_id;
    form.country_id = op.country_id;
    form.logo_url = op.logo_url || "";
    form.display_order = op.display_order || 0;
    form.is_active = op.is_active;
    showEditModal.value = true;
}

function submitAdd() {
    form.post("/admin/operators", {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
}

function submitEdit() {
    form.put(`/admin/operators/${editingOperator.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingOperator.value = null;
        },
    });
}

function confirmDelete(op) {
    deletingOperator.value = op;
    showDeleteModal.value = true;
}

function deleteOperator() {
    router.delete(`/admin/operators/${deletingOperator.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingOperator.value = null;
        },
    });
}

function syncFromDing() {
    syncing.value = true;
    router.post(
        "/admin/operators/sync",
        {},
        {
            onFinish: () => {
                syncing.value = false;
            },
        },
    );
}

function goToPage(url) {
    router.get(url, {}, { preserveState: true });
}

function applyFilters() {
    router.get(
        "/admin/operators",
        { search: search.value, country_id: countryFilter.value },
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Operators - Admin" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Operators</h1>
                <p class="text-dark-300">
                    {{ operators.total }} operator{{
                        operators.total !== 1 ? "s" : ""
                    }}
                    across {{ countries.length }} countries
                </p>
            </div>
            <div class="flex gap-3">
                <button
                    @click="syncFromDing"
                    :disabled="syncing"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 transition flex items-center gap-2"
                >
                    <svg
                        v-if="syncing"
                        class="animate-spin h-4 w-4"
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
                    <svg
                        v-else
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        ></path>
                    </svg>
                    {{ syncing ? "Syncing..." : "Sync from Ding" }}
                </button>
                <button
                    @click="openAddModal"
                    class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition"
                >
                    + Add Operator
                </button>
            </div>
        </div>

        <div
            v-if="$page.props.flash?.success"
            class="p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-300"
        >
            {{ $page.props.flash.success }}
        </div>
        <div
            v-if="$page.props.flash?.error"
            class="p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300"
        >
            {{ $page.props.flash.error }}
        </div>

        <div
            class="bg-dark-800 rounded-2xl border border-dark-600 overflow-hidden"
        >
            <div class="p-4 border-b border-dark-600 flex flex-wrap gap-4">
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search operators..."
                    class="border border-dark-600 rounded-lg px-4 py-2 bg-dark-700 text-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                />
                <select
                    v-model="countryFilter"
                    @change="applyFilters"
                    class="border border-dark-600 rounded-lg px-4 py-2 bg-dark-700 text-white text-sm outline-none"
                >
                    <option value="">All Countries</option>
                    <option v-for="c in countries" :key="c.id" :value="c.id">
                        {{ c.flag_emoji }} {{ c.name }}
                    </option>
                </select>
            </div>

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
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-dark-200 uppercase"
                            >
                                Actions
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
                                {{ op.country?.flag_emoji }}
                                {{ op.country?.name || "-" }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        op.is_active
                                            ? 'bg-green-500/20 text-green-300'
                                            : 'bg-dark-600 text-dark-400',
                                    ]"
                                >
                                    {{ op.is_active ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button
                                    @click="openEditModal(op)"
                                    class="text-primary-light hover:text-white text-sm transition"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="confirmDelete(op)"
                                    class="text-red-400 hover:text-red-300 text-sm transition"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!operators.data?.length">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-dark-400"
                            >
                                No operators found. Click "Sync from Ding" to
                                import.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="operators.last_page > 1"
                class="p-4 border-t border-dark-600 flex items-center justify-between"
            >
                <p class="text-sm text-dark-400">
                    Showing {{ operators.from }} to {{ operators.to }} of
                    {{ operators.total }}
                </p>
                <div class="flex gap-2">
                    <button
                        v-for="page in operators.links"
                        :key="page.label"
                        @click="goToPage(page.url)"
                        :disabled="!page.url"
                        :class="[
                            'px-3 py-1 rounded text-sm transition',
                            page.active
                                ? 'bg-primary text-white'
                                : 'bg-dark-700 text-dark-300 hover:bg-dark-600',
                            !page.url ? 'opacity-50 cursor-not-allowed' : '',
                        ]"
                        v-html="page.label"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Add Operator Modal -->
    <Modal :show="showAddModal" @close="showAddModal = false">
        <div class="p-6">
            <h3 class="text-xl font-bold text-white mb-4">Add Operator</h3>
            <form @submit.prevent="submitAdd" class="space-y-4">
                <div>
                    <InputLabel value="Operator Name *" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="w-full mt-1"
                        required
                    />
                    <p
                        v-if="form.errors.name"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Ding Operator ID *" />
                    <TextInput
                        v-model="form.ding_operator_id"
                        type="text"
                        class="w-full mt-1"
                        required
                        placeholder="e.g. 1"
                    />
                    <p
                        v-if="form.errors.ding_operator_id"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.ding_operator_id }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Country *" />
                    <select
                        v-model="form.country_id"
                        class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white text-sm mt-1 outline-none"
                        required
                    >
                        <option value="">Select country</option>
                        <option
                            v-for="c in countries"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.flag_emoji }} {{ c.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.country_id"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.country_id }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Logo URL" />
                    <TextInput
                        v-model="form.logo_url"
                        type="url"
                        class="w-full mt-1"
                        placeholder="https://..."
                    />
                </div>
                <div>
                    <InputLabel value="Display Order" />
                    <TextInput
                        v-model.number="form.display_order"
                        type="number"
                        class="w-full mt-1"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="form.is_active"
                        id="active"
                        class="rounded"
                    />
                    <label for="active" class="text-sm text-dark-200"
                        >Active</label
                    >
                </div>
                <div class="flex gap-3 pt-4">
                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1"
                    >
                        {{ form.processing ? "Adding..." : "Add Operator" }}
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="showAddModal = false"
                        >Cancel</SecondaryButton
                    >
                </div>
            </form>
        </div>
    </Modal>

    <!-- Edit Operator Modal -->
    <Modal :show="showEditModal" @close="showEditModal = false">
        <div class="p-6">
            <h3 class="text-xl font-bold text-white mb-4">Edit Operator</h3>
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel value="Operator Name *" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="w-full mt-1"
                        required
                    />
                    <p
                        v-if="form.errors.name"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Ding Operator ID *" />
                    <TextInput
                        v-model="form.ding_operator_id"
                        type="text"
                        class="w-full mt-1"
                        required
                    />
                    <p
                        v-if="form.errors.ding_operator_id"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.ding_operator_id }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Country *" />
                    <select
                        v-model="form.country_id"
                        class="w-full border border-dark-600 rounded-lg px-4 py-3 bg-dark-700 text-white text-sm mt-1 outline-none"
                        required
                    >
                        <option value="">Select country</option>
                        <option
                            v-for="c in countries"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.flag_emoji }} {{ c.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.country_id"
                        class="text-red-400 text-xs mt-1"
                    >
                        {{ form.errors.country_id }}
                    </p>
                </div>
                <div>
                    <InputLabel value="Logo URL" />
                    <TextInput
                        v-model="form.logo_url"
                        type="url"
                        class="w-full mt-1"
                    />
                </div>
                <div>
                    <InputLabel value="Display Order" />
                    <TextInput
                        v-model.number="form.display_order"
                        type="number"
                        class="w-full mt-1"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="form.is_active"
                        id="edit-active"
                        class="rounded"
                    />
                    <label for="edit-active" class="text-sm text-dark-200"
                        >Active</label
                    >
                </div>
                <div class="flex gap-3 pt-4">
                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1"
                    >
                        {{ form.processing ? "Saving..." : "Save Changes" }}
                    </PrimaryButton>
                    <SecondaryButton
                        type="button"
                        @click="showEditModal = false"
                        >Cancel</SecondaryButton
                    >
                </div>
            </form>
        </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <div class="p-6">
            <h3 class="text-xl font-bold text-white mb-2">Delete Operator</h3>
            <p class="text-dark-300 mb-6">
                Are you sure you want to delete
                <strong class="text-white">{{ deletingOperator?.name }}</strong
                >? This action cannot be undone.
            </p>
            <div class="flex gap-3">
                <DangerButton
                    @click="deleteOperator"
                    :disabled="form.processing"
                    class="flex-1"
                >
                    Delete
                </DangerButton>
                <SecondaryButton @click="showDeleteModal = false"
                    >Cancel</SecondaryButton
                >
            </div>
        </div>
    </Modal>
</template>
