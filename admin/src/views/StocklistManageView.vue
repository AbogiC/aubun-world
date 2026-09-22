<template>
  <div class="stocklist-manage py-4">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 class="fw-bold mb-1">Product Stock Management</h4>
          <p class="text-muted mb-0 small">View and manage inventory levels for all products</p>
        </div>
      </div>

      <div v-if="feedback.message" class="alert" :class="'alert-' + feedback.type" role="alert">
        {{ feedback.message }}
        <button type="button" class="btn-close float-end" @click="feedback.message = ''"></button>
      </div>

      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2">
          <input
            v-model="search"
            type="search"
            class="form-control form-control-sm"
            placeholder="Search products..."
            style="min-width: 240px;"
          />
          <select v-model="stockFilter" class="form-select form-select-sm" style="width: auto;">
            <option value="">All Stock Levels</option>
            <option value="out">Out of Stock (0)</option>
            <option value="low">Low Stock (1-10)</option>
            <option value="ok">In Stock (11+)</option>
          </select>
        </div>
        <span class="text-muted small">{{ filteredProducts.length }} product(s)</span>
      </div>

      <div class="surface p-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="filteredProducts.length === 0" class="text-center py-5">
          <i class="bi bi-box-seam text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No products found.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table stockist-table mb-0">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Product</th>
                <th class="d-none d-md-table-cell">Category</th>
                <th style="width: 120px;">Price</th>
                <th style="width: 100px;">Stock</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(product, index) in filteredProducts" :key="product.id">
                <td class="text-muted small">{{ index + 1 }}</td>
                <td class="fw-medium">
                  <div class="d-flex align-items-center gap-3">
                    <img :src="product.image" :alt="product.name" class="product-thumb-sm" />
                    {{ product.name }}
                  </div>
                </td>
                <td class="d-none d-md-table-cell text-muted small">{{ product.category }}</td>
                <td class="fw-semibold">${{ (product.basePrice ?? product.price).toLocaleString() }}</td>
                <td>
                  <input
                    v-model.number="product.stock"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control form-control-sm stock-input"
                    @change="updateStock(product)"
                    :disabled="updatingStockId === product.id"
                  />
                </td>
                <td>
                  <span :class="stockBadgeClass(product.stock)">
                    {{ stockStatusText(product.stock) }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button
                      class="btn btn-sm btn-outline-dark"
                      @click="quickEditStock(product)"
                      title="Edit Stock"
                      :disabled="updatingStockId === product.id"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <router-link
                      :to="`/products`"
                      class="btn btn-sm btn-outline-primary"
                      title="Edit Product"
                    >
                      <i class="bi bi-box-seam"></i>
                    </router-link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Stock Edit Modal -->
      <div v-if="showQuickEditModal" class="modal-overlay" @click.self="closeQuickEditModal">
        <div class="modal-dialog-custom">
          <div class="surface p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h5 class="fw-bold mb-0">Adjust Stock: {{ quickEditProduct?.name }}</h5>
              <button class="btn-close" @click="closeQuickEditModal"></button>
            </div>

            <form @submit.prevent="saveQuickEditStock">
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Current Stock</label>
                  <input
                    v-model.number="quickEditForm.currentStock"
                    type="number"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">New Stock Quantity</label>
                  <input
                    v-model.number="quickEditForm.newStock"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control"
                    required
                    autofocus
                  />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Adjustment Reason</label>
                <select v-model="quickEditForm.reason" class="form-select">
                  <option value="restock">Restock / New Inventory</option>
                  <option value="adjustment">Inventory Adjustment</option>
                  <option value="damage">Damaged / Returned</option>
                  <option value="transfer">Store Transfer</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div v-if="quickEditForm.reason === 'other'" class="mb-3">
                <label class="form-label small fw-bold">Notes</label>
                <textarea v-model="quickEditForm.notes" class="form-control" rows="2" placeholder="Additional details..."></textarea>
              </div>

              <div class="d-flex gap-2 justify-content-end border-top pt-3">
                <button type="button" class="btn btn-outline-dark btn-sm" @click="closeQuickEditModal">Cancel</button>
                <button type="submit" class="btn btn-dark btn-sm" :disabled="savingQuickEdit">
                  <span v-if="savingQuickEdit" class="spinner-border spinner-border-sm me-1"></span>
                  Update Stock
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";

const productsStore = useProductsStore();
const loading = ref(false);
const search = ref("");
const stockFilter = ref("");
const updatingStockId = ref(null);
const savingQuickEdit = ref(false);

const feedback = ref({ message: "", type: "success" });

const showQuickEditModal = ref(false);
const quickEditProduct = ref(null);
const quickEditForm = ref({
  currentStock: 0,
  newStock: 0,
  reason: "restock",
  notes: "",
});

const products = computed(() => productsStore.products);

const filteredProducts = computed(() => {
  let result = products.value;

  const keyword = search.value.trim().toLowerCase();
  if (keyword) {
    result = result.filter((product) =>
      [product.name, product.category].some((value) => value.toLowerCase().includes(keyword))
    );
  }

  if (stockFilter.value) {
    switch (stockFilter.value) {
      case "out":
        result = result.filter((p) => p.stock === 0);
        break;
      case "low":
        result = result.filter((p) => p.stock > 0 && p.stock <= 10);
        break;
      case "ok":
        result = result.filter((p) => p.stock > 10);
        break;
    }
  }

  return result;
});

function stockBadgeClass(stock) {
  if (stock === 0) return "badge bg-danger";
  if (stock <= 10) return "badge bg-warning text-dark";
  return "badge bg-success";
}

function stockStatusText(stock) {
  if (stock === 0) return "Out of Stock";
  if (stock <= 10) return "Low Stock";
  return "In Stock";
}

function showFeedback(message, type = "success") {
  feedback.value = { message, type };
}

async function fetchProducts() {
  loading.value = true;
  try {
    await productsStore.fetchProducts();
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    loading.value = false;
  }
}

async function updateStock(product) {
  updatingStockId.value = product.id;
  try {
    const payload = {
      stock: product.stock,
    };
    await api.patch(`/products/${product.id}`, payload);
    showFeedback(`Stock updated for "${product.name}"`);
  } catch (err) {
    showFeedback(err.message, "danger");
    await fetchProducts(); // Revert to server value
  } finally {
    updatingStockId.value = null;
  }
}

function quickEditStock(product) {
  quickEditProduct.value = product;
  quickEditForm.value = {
    currentStock: product.stock,
    newStock: product.stock,
    reason: "restock",
    notes: "",
  };
  showQuickEditModal.value = true;
}

function closeQuickEditModal() {
  showQuickEditModal.value = false;
  quickEditProduct.value = null;
  quickEditForm.value = {
    currentStock: 0,
    newStock: 0,
    reason: "restock",
    notes: "",
  };
}

async function saveQuickEditStock() {
  if (!quickEditProduct.value) return;

  savingQuickEdit.value = true;
  try {
    const payload = {
      stock: quickEditForm.value.newStock,
    };
    await api.patch(`/products/${quickEditProduct.value.id}`, payload);
    showFeedback(`Stock updated for "${quickEditProduct.value.name}" (${quickEditForm.value.currentStock} → ${quickEditForm.value.newStock})`);
    closeQuickEditModal();
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    savingQuickEdit.value = false;
  }
}

onMounted(fetchProducts);
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

.product-thumb-sm {
  width: 40px;
  height: 48px;
  object-fit: cover;
  border-radius: 0.5rem;
  background: rgba(77, 16, 24, 0.08);
}

.stock-input {
  width: 80px;
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
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
  max-width: 480px;
  animation: modalIn 0.2s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(-20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>