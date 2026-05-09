<template>
  <div class="voucher-page py-5">
    <section class="container mb-4">
      <div class="voucher-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">Campaign Management</p>
            <h1 class="display-5 mb-3">Voucher Discounts</h1>
            <p class="text-muted mb-0">
              Create, update, and retire discount vouchers for the full catalog, one category, or a selected product list.
            </p>
            <div class="d-flex flex-wrap gap-3 mt-4">
              <router-link to="/dashboard/products" class="btn btn-outline-dark">
                Back to Products
              </router-link>
              <router-link to="/dashboard/shipping" class="btn btn-outline-dark">
                Shipping Settings
              </router-link>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="voucher-metrics">
              <div class="metric-card">
                <span class="metric-label">Vouchers</span>
                <strong>{{ vouchers.length }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Active</span>
                <strong>{{ activeVoucherCount }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Expiring Soon</span>
                <strong>{{ expiringSoonCount }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container">
      <div class="row g-4">
        <div class="col-xl-5">
          <div class="surface p-4 p-lg-5 h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">{{ editingVoucherId ? "Editing Voucher" : "New Voucher" }}</p>
                <h2 class="h3 mb-1">{{ editingVoucherId ? "Update Voucher" : "Create Voucher" }}</h2>
                <p class="text-muted mb-0">Use uppercase codes like <strong>DISCVEST20</strong>.</p>
              </div>
              <button
                v-if="editingVoucherId"
                type="button"
                class="btn btn-outline-dark btn-sm"
                @click="resetForm"
              >
                Cancel
              </button>
            </div>

            <form class="dashboard-form" @submit.prevent="saveVoucher">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Voucher Name / Code</label>
                  <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    placeholder="DISCVEST20"
                    maxlength="120"
                    required
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Discount Value (%)</label>
                  <div class="input-group">
                    <input
                      v-model.number="form.discountPercent"
                      type="number"
                      min="0.01"
                      max="100"
                      step="0.01"
                      class="form-control"
                      required
                    />
                    <span class="input-group-text">% off</span>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Expiration</label>
                  <input
                    v-model="form.expiresAt"
                    type="datetime-local"
                    class="form-control"
                    required
                  />
                </div>

                <div class="col-12">
                  <label class="form-label">Voucher Scope</label>
                  <select v-model="form.scopeType" class="form-select">
                    <option value="all">All Products</option>
                    <option value="category">Specific Category</option>
                    <option value="products">Specific Products</option>
                  </select>
                </div>

                <div v-if="form.scopeType === 'category'" class="col-12">
                  <label class="form-label">Limited Category</label>
                  <select v-model="form.categoryName" class="form-select" required>
                    <option value="">Select category</option>
                    <option v-for="category in limitedCategories" :key="category" :value="category">
                      {{ category }}
                    </option>
                  </select>
                  <div class="form-text">Example: choose <strong>Vest</strong> if this voucher should only work for vest products.</div>
                </div>

                <div v-if="form.scopeType === 'products'" class="col-12">
                  <label class="form-label">Limited Products</label>
                  <div class="product-picker">
                    <label
                      v-for="product in products"
                      :key="`voucher-product-${product.id}`"
                      class="product-option"
                      :class="{ 'product-option--active': form.productIds.includes(product.id) }"
                    >
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :checked="form.productIds.includes(product.id)"
                        @change="toggleProduct(product.id)"
                      />
                      <div class="product-option__body">
                        <span class="product-option__name">{{ product.name }}</span>
                        <span class="product-option__meta">{{ product.category }}</span>
                      </div>
                    </label>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input id="voucher-active" v-model="form.isActive" class="form-check-input" type="checkbox" />
                    <label for="voucher-active" class="form-check-label">Voucher is active</label>
                  </div>
                </div>
              </div>

              <div v-if="feedback.message" :class="['alert mt-4 mb-0', feedback.type === 'error' ? 'alert-danger' : 'alert-success']">
                {{ feedback.message }}
              </div>

              <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-luxury flex-grow-1" :disabled="saving">
                  {{ saving ? (editingVoucherId ? "Saving..." : "Creating...") : editingVoucherId ? "Save Changes" : "Create Voucher" }}
                </button>
                <button type="button" class="btn btn-outline-luxury" @click="resetForm">
                  Reset
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-xl-7">
          <div class="surface p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Voucher Library</p>
                <h2 class="h3 mb-0">Manage Existing Codes</h2>
              </div>
              <div class="position-relative">
                <i class="bi bi-search search-icon"></i>
                <input
                  v-model="search"
                  type="search"
                  class="form-control search-input"
                  placeholder="Search code or category"
                />
              </div>
            </div>

            <div v-if="loading" class="text-muted">Loading vouchers...</div>

            <div v-else class="dashboard-table-wrap">
              <table class="table align-middle dashboard-table">
                <thead>
                  <tr>
                    <th>Voucher</th>
                    <th>Discount</th>
                    <th>Scope</th>
                    <th>Expiration</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="voucher in filteredVouchers" :key="voucher.id">
                    <td>
                      <div class="fw-semibold">{{ voucher.code }}</div>
                      <div class="small text-muted">
                        {{ voucher.isActive ? "Active" : "Inactive" }}
                      </div>
                    </td>
                    <td>{{ voucher.discountPercent }}% off</td>
                    <td>{{ scopeLabel(voucher) }}</td>
                    <td>
                      <div>{{ formatExpiration(voucher.expiresAt) }}</div>
                      <div v-if="isExpired(voucher.expiresAt)" class="small text-danger">Expired</div>
                    </td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-dark btn-sm" @click="startEdit(voucher)">Edit</button>
                        <button
                          class="btn btn-outline-danger btn-sm"
                          :disabled="deletingId === voucher.id"
                          @click="removeVoucher(voucher)"
                        >
                          {{ deletingId === voucher.id ? "Deleting..." : "Delete" }}
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="!filteredVouchers.length" class="empty-state text-center py-5">
                <i class="bi bi-ticket-perforated display-5 text-muted"></i>
                <h3 class="h5 mt-3">No matching vouchers</h3>
                <p class="text-muted mb-0">Create a new code or try a different search term.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";

const productsStore = useProductsStore();
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const editingVoucherId = ref(null);
const search = ref("");
const vouchers = ref([]);
const feedback = reactive({
  type: "success",
  message: "",
});

const createInitialForm = () => ({
  code: "",
  discountPercent: 20,
  scopeType: "all",
  categoryName: "",
  productIds: [],
  expiresAt: "",
  isActive: true,
});

const form = reactive(createInitialForm());

const products = computed(() => productsStore.products);
const limitedCategories = computed(() => productsStore.categories.filter((category) => category !== "All"));
const activeVoucherCount = computed(() => vouchers.value.filter((voucher) => voucher.isActive && !isExpired(voucher.expiresAt)).length);
const expiringSoonCount = computed(() => vouchers.value.filter((voucher) => isExpiringSoon(voucher.expiresAt)).length);
const filteredVouchers = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  if (!keyword) {
    return vouchers.value;
  }

  return vouchers.value.filter((voucher) =>
    [voucher.code, voucher.categoryName || "", scopeLabel(voucher)]
      .some((value) => value.toLowerCase().includes(keyword)),
  );
});

watch(
  () => form.scopeType,
  (scopeType) => {
    if (scopeType !== "category") {
      form.categoryName = "";
    }

    if (scopeType !== "products") {
      form.productIds = [];
    }
  },
);

const normalizeCode = () => {
  form.code = form.code.toUpperCase().replace(/\s+/g, "");
};

const setDefaultExpiration = () => {
  if (form.expiresAt) {
    return;
  }

  const date = new Date();
  date.setDate(date.getDate() + 30);
  form.expiresAt = toDateTimeLocalValue(date);
};

const resetForm = ({ clearFeedback = true } = {}) => {
  Object.assign(form, createInitialForm());
  editingVoucherId.value = null;
  setDefaultExpiration();

  if (clearFeedback) {
    feedback.message = "";
  }
};

const fetchVouchers = async () => {
  loading.value = true;

  try {
    const [{ vouchers: voucherRows }] = await Promise.all([
      api.get("/vouchers"),
      productsStore.loaded ? Promise.resolve() : productsStore.fetchProducts(),
    ]);

    vouchers.value = (voucherRows || []).map((voucher) => ({
      ...voucher,
      discountPercent: Number(voucher.discountPercent),
      productIds: voucher.productIds || [],
    }));
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    loading.value = false;
  }
};

const saveVoucher = async () => {
  saving.value = true;
  feedback.message = "";
  normalizeCode();

  try {
    const payload = {
      code: form.code,
      discountPercent: Number(form.discountPercent),
      scopeType: form.scopeType,
      categoryName: form.categoryName,
      productIds: form.productIds,
      expiresAt: form.expiresAt,
      isActive: form.isActive,
    };

    if (editingVoucherId.value) {
      await api.patch(`/vouchers/${editingVoucherId.value}`, payload);
      feedback.type = "success";
      feedback.message = "Voucher updated successfully.";
    } else {
      await api.post("/vouchers", payload);
      feedback.type = "success";
      feedback.message = "Voucher created successfully.";
    }

    await fetchVouchers();
    resetForm({ clearFeedback: false });
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    saving.value = false;
  }
};

const startEdit = (voucher) => {
  editingVoucherId.value = voucher.id;
  feedback.message = "";
  Object.assign(form, {
    code: voucher.code,
    discountPercent: Number(voucher.discountPercent),
    scopeType: voucher.scopeType,
    categoryName: voucher.categoryName || "",
    productIds: [...(voucher.productIds || [])],
    expiresAt: toDateTimeLocalValue(voucher.expiresAt),
    isActive: voucher.isActive,
  });
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const removeVoucher = async (voucher) => {
  const confirmed = window.confirm(`Delete voucher "${voucher.code}"?`);

  if (!confirmed) {
    return;
  }

  deletingId.value = voucher.id;
  feedback.message = "";

  try {
    await api.delete(`/vouchers/${voucher.id}`);
    feedback.type = "success";
    feedback.message = "Voucher deleted successfully.";

    if (editingVoucherId.value === voucher.id) {
      resetForm();
    }

    await fetchVouchers();
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    deletingId.value = null;
  }
};

const toggleProduct = (productId) => {
  if (form.productIds.includes(productId)) {
    form.productIds = form.productIds.filter((id) => id !== productId);
    return;
  }

  form.productIds = [...form.productIds, productId];
};

const scopeLabel = (voucher) => {
  if (voucher.scopeType === "category") {
    return `Category: ${voucher.categoryName}`;
  }

  if (voucher.scopeType === "products") {
    return `${voucher.productIds.length} selected products`;
  }

  return "All products";
};

const formatExpiration = (value) =>
  voucherDate(value).toLocaleString([], {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

const isExpired = (value) => voucherDate(value).getTime() < Date.now();

const isExpiringSoon = (value) => {
  const time = voucherDate(value).getTime();
  const diff = time - Date.now();

  return diff > 0 && diff <= 1000 * 60 * 60 * 24 * 7;
};

const toDateTimeLocalValue = (value) => {
  const date = value instanceof Date ? value : voucherDate(value);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  const offset = date.getTimezoneOffset();
  const localDate = new Date(date.getTime() - (offset * 60 * 1000));
  return localDate.toISOString().slice(0, 16);
};

const voucherDate = (value) => new Date(String(value).replace(" ", "T"));

onMounted(async () => {
  resetForm();
  await fetchVouchers();
});
</script>

<style scoped>
.voucher-hero {
  background:
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.16), transparent 32%),
    linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.42));
}

.voucher-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}

.metric-card {
  padding: 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.64);
}

.metric-card strong {
  display: block;
  font-size: 1.7rem;
  margin-top: 0.3rem;
}

.metric-label {
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.product-picker {
  display: grid;
  gap: 0.75rem;
  max-height: 280px;
  overflow: auto;
  padding-right: 0.25rem;
}

.product-option {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 0.95rem;
  background: rgba(255, 241, 184, 0.78);
  cursor: pointer;
}

.product-option--active {
  border-color: rgba(77, 16, 24, 0.32);
  box-shadow: 0 10px 22px rgba(77, 16, 24, 0.12);
}

.product-option__body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.product-option__name {
  font-weight: 600;
  line-height: 1.3;
}

.product-option__meta {
  font-size: 0.82rem;
  color: var(--ink-muted);
}

.search-input {
  min-width: 260px;
  padding-left: 2.5rem;
}

.search-icon {
  position: absolute;
  top: 50%;
  left: 0.9rem;
  transform: translateY(-50%);
  color: var(--ink-muted);
}

.dashboard-table-wrap {
  overflow-x: auto;
}

.dashboard-table {
  margin-bottom: 0;
}

.dashboard-table th {
  font-size: 0.76rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-muted);
  border-bottom-width: 1px;
}

.dashboard-table td,
.dashboard-table th {
  background: transparent;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.empty-state {
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1.25rem;
  margin-top: 1rem;
}

@media (max-width: 991px) {
  .voucher-metrics {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .voucher-metrics {
    grid-template-columns: 1fr;
  }

  .search-input {
    min-width: 100%;
  }
}
</style>
