<template>
  <div class="guideline-manage py-4">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 class="fw-bold mb-1">Guidelines</h4>
          <p class="text-muted mb-0 small">Manage size guides, fit guides, care instructions & product guides</p>
        </div>
      </div>

      <div v-if="feedback.message" class="alert" :class="'alert-' + feedback.type" role="alert">
        {{ feedback.message }}
        <button type="button" class="btn-close float-end" @click="feedback.message = ''"></button>
      </div>

      <ul class="nav nav-tabs guidelines-manage-tabs border-0 mb-4 gap-2" role="tablist">
        <li v-for="tab in tabs" :key="tab.key" class="nav-item" role="presentation">
          <button
            class="nav-link"
            :class="{ active: activeTab === tab.key }"
            @click="activeTab = tab.key"
            type="button"
            role="tab"
          >
            <i :class="tab.icon" class="me-1"></i> {{ tab.label }}
          </button>
        </li>
      </ul>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="text-muted small">{{ filtered.length }} item(s)</span>
        <button class="btn btn-dark btn-sm" @click="startCreate">
          <i class="bi bi-plus-lg me-1"></i> Add {{ activeTabLabel }}
        </button>
      </div>

      <div class="surface p-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="filtered.length === 0" class="text-center py-5">
          <i class="bi bi-journal-text text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No {{ activeTabLabel }} entries yet.</p>
          <button class="btn btn-dark btn-sm mt-2" @click="startCreate">Create the first one</button>
        </div>

        <div v-else class="table-responsive">
          <table class="table guideline-table mb-0">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Title</th>
                <th class="d-none d-md-table-cell">Subtype</th>
                <th class="d-none d-md-table-cell">Preview</th>
                <th style="width: 90px;">Active</th>
                <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filtered" :key="item.id">
                <td class="text-muted small">{{ index + 1 }}</td>
                <td class="fw-medium">{{ item.title }}</td>
                <td class="d-none d-md-table-cell text-muted small">{{ subtypeLabel(item) }}</td>
                <td class="d-none d-md-table-cell text-muted small text-truncate" style="max-width: 220px;">
                  {{ contentPreview(item) }}
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
            <h5 class="fw-bold mb-0">{{ editingId ? 'Edit' : 'Add' }} {{ activeTabLabel }}</h5>
            <button class="btn-close" @click="closeModal"></button>
          </div>

          <form @submit.prevent="saveItem">
            <div class="mb-3">
              <label class="form-label small fw-bold">Title</label>
              <input v-model="form.title" class="form-control" placeholder="e.g. Tops & Shirts" required />
            </div>

            <!-- Size Guide fields -->
            <template v-if="activeTab === 'size_guide'">
              <div class="mb-3">
                <label class="form-label small fw-bold">Category</label>
                <select v-model="form.content.category" class="form-select">
                  <option value="tops">Tops & Shirts</option>
                  <option value="bottoms">Bottoms</option>
                  <option value="dresses">Dresses</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Size Rows (JSON array)</label>
                <textarea v-model="form.content.rowsRaw" class="form-control font-monospace" rows="6"
                  placeholder='[{"size":"XS","us":"0-2","uk":"4-6","eu":"32-34","bust":"31-32","waist":"23-24","hips":""}]'></textarea>
                <div class="form-text">Each row: size, us, uk, eu, bust, waist, hips (leave empty if not applicable).</div>
              </div>
            </template>

            <!-- Fit Guide fields -->
            <template v-if="activeTab === 'fit_guide'">
              <div class="mb-3">
                <label class="form-label small fw-bold">Subtype</label>
                <select v-model="form.content.type" class="form-select" @change="onFitSubtypeChange">
                  <option value="fit_type">Fit Type (card)</option>
                  <option value="fit_note">Fit Note (category note)</option>
                </select>
              </div>

              <template v-if="form.content.type === 'fit_type'">
                <div class="row g-3 mb-3">
                  <div class="col-md-4">
                    <label class="form-label small fw-bold">Fit ID</label>
                    <input v-model="form.content.fitId" class="form-control" placeholder="e.g. slim" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-bold">Name</label>
                    <input v-model="form.content.name" class="form-control" placeholder="e.g. Slim Fit" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-bold">Badge</label>
                    <input v-model="form.content.badge" class="form-control" placeholder="e.g. Tailored Shape" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Icon class</label>
                  <input v-model="form.content.icon" class="form-control" placeholder="e.g. bi bi-person-standing" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Badge Class</label>
                  <input v-model="form.content.badgeClass" class="form-control" placeholder="e.g. bg-dark" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Description</label>
                  <textarea v-model="form.content.description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Summary (short)</label>
                  <input v-model="form.content.summary" class="form-control" placeholder="e.g. Tailored, close to the body." />
                </div>
              </template>

              <template v-if="form.content.type === 'fit_note'">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Category</label>
                  <input v-model="form.content.category" class="form-control" placeholder="e.g. Tops & Shirts" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Note</label>
                  <textarea v-model="form.content.note" class="form-control" rows="3" placeholder="Fit advice for this category..."></textarea>
                </div>
              </template>
            </template>

            <!-- Care Instruction fields -->
            <template v-if="activeTab === 'care_instruction'">
              <div class="mb-3">
                <label class="form-label small fw-bold">Subtype</label>
                <select v-model="form.content.type" class="form-select" @change="onCareSubtypeChange">
                  <option value="care_icon">Care Icon (symbol)</option>
                  <option value="fabric_care">Fabric Care (accordion)</option>
                </select>
              </div>

              <template v-if="form.content.type === 'care_icon'">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Icon class</label>
                  <input v-model="form.content.icon" class="form-control" placeholder="e.g. bi bi-water" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Label</label>
                  <input v-model="form.content.label" class="form-control" placeholder="e.g. Machine Wash" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Detail</label>
                  <input v-model="form.content.detail" class="form-control" placeholder="e.g. Cold water, gentle cycle" />
                </div>
              </template>

              <template v-if="form.content.type === 'fabric_care'">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Fabric Name</label>
                  <input v-model="form.content.name" class="form-control" placeholder="e.g. Cotton & Linen" />
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Care Text</label>
                  <textarea v-model="form.content.care" class="form-control" rows="4" placeholder="Washing, drying, ironing instructions..."></textarea>
                </div>
              </template>
            </template>

            <!-- Product Guide fields -->
            <template v-if="activeTab === 'product_guide'">
              <div class="mb-3">
                <label class="form-label small fw-bold">Subtype</label>
                <select v-model="form.content.type" class="form-select">
                  <option value="universal">Universal (all products)</option>
                  <option value="specific">Specific product</option>
                </select>
              </div>

              <template v-if="form.content.type === 'specific'">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Product ID</label>
                  <input v-model.number="form.content.productId" type="number" class="form-control" placeholder="Product ID" />
                </div>
              </template>

              <div class="mb-3">
                <label class="form-label small fw-bold">Fit Note</label>
                <input v-model="form.content.fitNote" class="form-control" placeholder="e.g. true to size" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Notes</label>
                <textarea v-model="form.content.notes" class="form-control" rows="4"></textarea>
              </div>
            </template>

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
import { computed, reactive, ref, watch } from "vue";
import { api } from "../lib/api";

const tabs = [
  { key: "size_guide", label: "Size Guide", icon: "bi bi-rulers" },
  { key: "fit_guide", label: "Fit Guide", icon: "bi bi-person-standing" },
  { key: "care_instruction", label: "Care Instructions", icon: "bi bi-droplet" },
  { key: "product_guide", label: "Product Guide", icon: "bi bi-box-seam" },
];

const activeTab = ref("size_guide");
const guidelines = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);

const feedback = reactive({ message: "", type: "success" });

const form = reactive({
  title: "",
  type: "size_guide",
  content: {},
  sortOrder: 0,
  isActive: true,
});

function createInitialForm() {
  return { title: "", type: activeTab.value, content: {}, sortOrder: 0, isActive: true };
}

function resetForm() {
  Object.assign(form, createInitialForm());
  editingId.value = null;
}

const activeTabLabel = computed(() => {
  const tab = tabs.find((t) => t.key === activeTab.value);
  return tab ? tab.label : "Guideline";
});

const filtered = computed(() =>
  guidelines.value.filter((g) => g.type === activeTab.value)
);

function subtypeLabel(item) {
  const t = item.content?.type;
  if (t === "fit_type") return "Fit Type";
  if (t === "fit_note") return "Fit Note";
  if (t === "care_icon") return "Care Icon";
  if (t === "fabric_care") return "Fabric Care";
  if (t === "universal") return "Universal";
  if (t === "specific") return "Specific";
  return item.content?.category || item.content?.fitType || "—";
}

function contentPreview(item) {
  const c = item.content || {};
  if (item.type === "size_guide") return `${c.category || "?"} — ${(c.rows || []).length} size(s)`;
  if (item.type === "fit_guide") {
    if (c.type === "fit_note") return c.category || "";
    return c.name || c.fitType || "";
  }
  if (item.type === "care_instruction") {
    if (c.type === "care_icon") return c.label || "";
    return c.name || "";
  }
  if (item.type === "product_guide") return c.type === "universal" ? "Universal" : `Product #${c.productId || "?"}`;
  return "";
}

function showFeedback(message, type = "success") {
  feedback.message = message;
  feedback.type = type;
}

async function fetchGuidelines() {
  loading.value = true;
  try {
    const data = await api.get("/guidelines");
    guidelines.value = data.guidelines || [];
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    loading.value = false;
  }
}

function initContentDefaults() {
  const tab = activeTab.value;
  if (tab === "size_guide") {
    form.content = { category: "tops", rows: [], rowsRaw: "" };
  } else if (tab === "fit_guide") {
    form.content = { type: "fit_type", fitId: "", name: "", icon: "bi bi-person-standing", badge: "", badgeClass: "bg-dark", description: "", summary: "" };
  } else if (tab === "care_instruction") {
    form.content = { type: "care_icon", icon: "bi bi-water", label: "", detail: "" };
  } else if (tab === "product_guide") {
    form.content = { type: "universal", productId: null, fitNote: "", notes: "" };
  }
}

function onFitSubtypeChange() {
  if (form.content.type === "fit_note") {
    form.content = { type: "fit_note", category: "", note: "" };
  } else {
    form.content = { type: "fit_type", fitId: "", name: "", icon: "bi bi-person-standing", badge: "", badgeClass: "bg-dark", description: "", summary: "" };
  }
}

function onCareSubtypeChange() {
  if (form.content.type === "fabric_care") {
    form.content = { type: "fabric_care", name: "", care: "" };
  } else {
    form.content = { type: "care_icon", icon: "bi bi-water", label: "", detail: "" };
  }
}

function startCreate() {
  resetForm();
  form.type = activeTab.value;
  initContentDefaults();
  showModal.value = true;
}

function startEdit(item) {
  editingId.value = item.id;
  form.title = item.title;
  form.type = item.type;
  form.sortOrder = item.sortOrder;
  form.isActive = item.isActive;
  form.content = JSON.parse(JSON.stringify(item.content));

  if (form.type === "size_guide" && form.content.rows) {
    form.content.rowsRaw = JSON.stringify(form.content.rows, null, 2);
  }

  showModal.value = true;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

async function saveItem() {
  if (!form.title.trim()) {
    showFeedback("Title is required.", "danger");
    return;
  }

  saving.value = true;

  try {
    const payload = {
      title: form.title.trim(),
      type: activeTab.value,
      content: { ...form.content },
      sortOrder: form.sortOrder,
      isActive: form.isActive,
    };

    if (activeTab.value === "size_guide" && form.content.rowsRaw) {
      try {
        payload.content.rows = JSON.parse(form.content.rowsRaw);
      } catch {
        showFeedback("Invalid JSON in size rows.", "danger");
        saving.value = false;
        return;
      }
      delete payload.content.rowsRaw;
    }

    if (editingId.value) {
      const data = await api.patch(`/guidelines/${editingId.value}`, payload);
      showFeedback(data.message || "Updated successfully.");
    } else {
      const data = await api.post("/guidelines", payload);
      showFeedback(data.message || "Created successfully.");
    }

    closeModal();
    await fetchGuidelines();
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    saving.value = false;
  }
}

async function removeItem(item) {
  if (!window.confirm(`Delete "${item.title}"?`)) return;

  try {
    const data = await api.delete(`/guidelines/${item.id}`);
    showFeedback(data.message || "Deleted successfully.");
    await fetchGuidelines();
  } catch (err) {
    showFeedback(err.message, "danger");
  }
}

watch(activeTab, () => {
  resetForm();
});

fetchGuidelines();
</script>

<style scoped>
.guidelines-manage-tabs .nav-link {
  background: rgba(255, 248, 228, 0.88);
  border: 1px solid rgba(77, 16, 24, 0.14);
  border-radius: var(--radius-sm) !important;
  padding: 0.6rem 1.1rem;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: var(--primary-black);
  transition: all var(--transition-base);
  white-space: nowrap;
}

.guidelines-manage-tabs .nav-link:hover {
  background: rgba(255, 248, 228, 0.96);
  border-color: rgba(77, 16, 24, 0.28);
}

.guidelines-manage-tabs .nav-link.active {
  background: var(--primary-black) !important;
  border-color: var(--primary-black) !important;
  color: var(--gold) !important;
}

.guideline-table thead th {
  background: rgba(77, 16, 24, 0.04);
  border-bottom: 2px solid rgba(77, 16, 24, 0.1);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 600;
  padding: 0.75rem 0.85rem;
}

.guideline-table tbody td {
  padding: 0.7rem 0.85rem;
  font-size: 0.88rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.05);
  vertical-align: middle;
}

.guideline-table tbody tr:hover {
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
  padding: 2rem 1rem;
  z-index: 1050;
  overflow-y: auto;
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
