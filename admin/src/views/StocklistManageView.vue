<template>
  <div class="stocklist-manage py-4">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 class="fw-bold mb-1">Stockists</h4>
          <p class="text-muted mb-0 small">Manage the boutiques and partners shown on the stocklist page</p>
        </div>
      </div>

      <div v-if="feedback.message" class="alert" :class="'alert-' + feedback.type" role="alert">
        {{ feedback.message }}
        <button type="button" class="btn-close float-end" @click="feedback.message = ''"></button>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="text-muted small">{{ stockists.length }} item(s)</span>
        <button class="btn btn-dark btn-sm" @click="startCreate">
          <i class="bi bi-plus-lg me-1"></i> Add Stockist
        </button>
      </div>

      <div class="surface p-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="stockists.length === 0" class="text-center py-5">
          <i class="bi bi-shop text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No stockists yet.</p>
          <button class="btn btn-dark btn-sm mt-2" @click="startCreate">Create the first one</button>
        </div>

        <div v-else class="table-responsive">
          <table class="table stockist-table mb-0">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Name</th>
                <th class="d-none d-md-table-cell">Region</th>
                <th class="d-none d-lg-table-cell">Address</th>
                <th style="width: 90px;">Active</th>
                <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in stockists" :key="item.id">
                <td class="text-muted small">{{ index + 1 }}</td>
                <td class="fw-medium">{{ item.name }}</td>
                <td class="d-none d-md-table-cell text-muted small">{{ item.region }}</td>
                <td class="d-none d-lg-table-cell text-muted small text-truncate" style="max-width: 260px;">
                  {{ item.address }}, {{ item.city }}
                </td>
                <td>
                  <span :class="['badge', item.isActive ? 'bg-success' : 'bg-secondary']">
                    {{ item.isActive ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-dark" @click="startEdit(item)" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="removeItem(item)" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-dialog-custom">
        <div class="surface p-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold mb-0">{{ editingId ? 'Edit' : 'Add' }} Stockist</h5>
            <button class="btn-close" @click="closeModal"></button>
          </div>

          <form @submit.prevent="saveItem">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Name</label>
                <input v-model="form.name" class="form-control" placeholder="e.g. Maison Aubun" required />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Region</label>
                <input v-model="form.region" class="form-control" placeholder="e.g. Jakarta" required />
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Type</label>
                <select v-model="form.type" class="form-select">
                  <option value="Flagship">Flagship</option>
                  <option value="Boutique">Boutique</option>
                  <option value="Concept Store">Concept Store</option>
                  <option value="Department Store">Department Store</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Icon class</label>
                <input v-model="form.icon" class="form-control" placeholder="e.g. bi bi-shop" />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Address</label>
              <input v-model="form.address" class="form-control" placeholder="e.g. Jl. Sudirman Kav. 1" required />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">City</label>
              <input v-model="form.city" class="form-control" placeholder="e.g. Jakarta, Indonesia" required />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">URL <span class="text-muted fw-normal">(optional)</span></label>
              <input v-model="form.url" class="form-control" placeholder="https://..." />
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Sort Order</label>
                <input v-model.number="form.sortOrder" type="number" class="form-control" min="0" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Active</label>
                <div class="form-check form-switch mt-2">
                  <input v-model="form.isActive" type="checkbox" class="form-check-input" id="isActive" />
                  <label class="form-check-label" for="isActive">{{ form.isActive ? 'Visible' : 'Hidden' }}</label>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 justify-content-end border-top pt-3">
              <button type="button" class="btn btn-outline-dark btn-sm" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-dark btn-sm" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingId ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { api } from "../lib/api";

const stockists = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);

const feedback = reactive({ message: "", type: "success" });

const form = reactive({
  name: "",
  region: "",
  type: "Boutique",
  icon: "bi bi-shop",
  address: "",
  city: "",
  url: "",
  sortOrder: 0,
  isActive: true,
});

function createInitialForm() {
  return {
    name: "",
    region: "",
    type: "Boutique",
    icon: "bi bi-shop",
    address: "",
    city: "",
    url: "",
    sortOrder: 0,
    isActive: true,
  };
}

function resetForm() {
  Object.assign(form, createInitialForm());
  editingId.value = null;
}

function showFeedback(message, type = "success") {
  feedback.message = message;
  feedback.type = type;
}

async function fetchStockists() {
  loading.value = true;
  try {
    const data = await api.get("/stockists/manage");
    stockists.value = data.stockists || [];
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    loading.value = false;
  }
}

function startCreate() {
  resetForm();
  showModal.value = true;
}

function startEdit(item) {
  editingId.value = item.id;
  form.name = item.name;
  form.region = item.region;
  form.type = item.type;
  form.icon = item.icon || "bi bi-shop";
  form.address = item.address;
  form.city = item.city;
  form.url = item.url || "";
  form.sortOrder = item.sortOrder;
  form.isActive = item.isActive;
  showModal.value = true;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

async function saveItem() {
  if (!form.name.trim()) {
    showFeedback("Name is required.", "danger");
    return;
  }
  if (!form.region.trim()) {
    showFeedback("Region is required.", "danger");
    return;
  }
  if (!form.address.trim()) {
    showFeedback("Address is required.", "danger");
    return;
  }
  if (!form.city.trim()) {
    showFeedback("City is required.", "danger");
    return;
  }

  saving.value = true;

  try {
    const payload = {
      name: form.name.trim(),
      region: form.region.trim(),
      type: form.type,
      icon: form.icon.trim(),
      address: form.address.trim(),
      city: form.city.trim(),
      url: form.url.trim(),
      sortOrder: form.sortOrder,
      isActive: form.isActive,
    };

    if (editingId.value) {
      const data = await api.patch(`/stockists/${editingId.value}`, payload);
      showFeedback(data.message || "Updated successfully.");
    } else {
      const data = await api.post("/stockists", payload);
      showFeedback(data.message || "Created successfully.");
    }

    closeModal();
    await fetchStockists();
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    saving.value = false;
  }
}

async function removeItem(item) {
  if (!window.confirm(`Delete "${item.name}"?`)) return;

  try {
    const data = await api.delete(`/stockists/${item.id}`);
    showFeedback(data.message || "Deleted successfully.");
    await fetchStockists();
  } catch (err) {
    showFeedback(err.message, "danger");
  }
}

fetchStockists();
</script>

<style scoped>
.stockist-table thead th {
  background: rgba(77, 16, 24, 0.04);
  border-bottom: 2px solid rgba(77, 16, 24, 0.1);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 600;
  padding: 0.75rem 0.85rem;
}

.stockist-table tbody td {
  padding: 0.7rem 0.85rem;
  font-size: 0.88rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.05);
  vertical-align: middle;
}

.stockist-table tbody tr:hover {
  background: rgba(255, 241, 184, 0.3);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(20, 10, 12, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: calc(5.5rem + 2rem) 1rem 2rem;
  z-index: 1050;
  overflow-y: auto;
}

@media (max-width: 991.98px) {
  .modal-overlay {
    padding-top: calc(3.5rem + 1.5rem);
  }
}

.modal-dialog-custom {
  width: 100%;
  max-width: 640px;
  animation: modalIn 0.2s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(-20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>