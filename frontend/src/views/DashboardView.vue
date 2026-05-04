<template>
  <div class="dashboard-page py-5">
    <section class="container mb-4">
      <div class="dashboard-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">Sales Management</p>
            <h1 class="display-5 mb-3">Product Dashboard</h1>
            <p class="text-muted mb-0">
              Add new arrivals, refine catalog data, and retire products from the collection without leaving the storefront workflow.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="stats-grid">
              <div class="metric-card">
                <span class="metric-label">Products</span>
                <strong>{{ products.length }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Featured</span>
                <strong>{{ featuredCount }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Categories</span>
                <strong>{{ categoryCount }}</strong>
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
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <p class="section-kicker mb-2">{{ editingProductId ? "Editing Product" : "New Product" }}</p>
                <h2 class="h3 mb-1">{{ editingProductId ? "Update Catalog Item" : "Add Product" }}</h2>
                <p class="text-muted mb-0">Use comma-separated lists for sizes and colors.</p>
              </div>
              <button
                v-if="editingProductId"
                type="button"
                class="btn btn-outline-dark btn-sm"
                @click="resetForm"
              >
                Cancel
              </button>
            </div>

            <form class="dashboard-form" @submit.prevent="saveProduct">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Product Name</label>
                  <input v-model="form.name" type="text" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Category</label>
                  <input v-model="form.category" type="text" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Image URL</label>
                  <input v-model="form.image" type="url" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Price</label>
                  <input v-model.number="form.price" type="number" min="0.01" step="0.01" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Original Price</label>
                  <input v-model="form.originalPrice" type="number" min="0" step="0.01" class="form-control" placeholder="Optional" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Rating</label>
                  <input v-model.number="form.rating" type="number" min="0" max="5" step="0.1" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Reviews</label>
                  <input v-model.number="form.reviews" type="number" min="0" step="1" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Sizes</label>
                  <input v-model="form.sizes" type="text" class="form-control" placeholder="XS, S, M, L" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Colors</label>
                  <input v-model="form.colors" type="text" class="form-control" placeholder="Black, White, Gray" required />
                </div>

                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea v-model="form.description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input id="featured" v-model="form.featured" class="form-check-input" type="checkbox" />
                    <label for="featured" class="form-check-label">Mark as featured</label>
                  </div>
                </div>
              </div>

              <div v-if="feedback.message" :class="['alert mt-4 mb-0', feedback.type === 'error' ? 'alert-danger' : 'alert-success']">
                {{ feedback.message }}
              </div>

              <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-luxury flex-grow-1" :disabled="saving">
                  {{ saving ? (editingProductId ? "Saving..." : "Creating...") : editingProductId ? "Save Changes" : "Create Product" }}
                </button>
                <button type="button" class="btn btn-outline-luxury" @click="resetForm">Reset</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-xl-7">
          <div class="surface p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Catalog Control</p>
                <h2 class="h3 mb-0">Manage Products</h2>
              </div>
              <div class="position-relative">
                <i class="bi bi-search search-icon"></i>
                <input v-model="search" type="search" class="form-control search-input" placeholder="Search by name or category" />
              </div>
            </div>

            <div v-if="loading" class="text-muted">Loading products...</div>

            <div v-else class="dashboard-table-wrap">
              <table class="table align-middle dashboard-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Featured</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="product in filteredProducts" :key="product.id">
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <img :src="product.image" :alt="product.name" class="product-thumb" />
                        <div>
                          <div class="fw-semibold">{{ product.name }}</div>
                          <div class="small text-muted">{{ product.sizes.join(", ") }}</div>
                        </div>
                      </div>
                    </td>
                    <td>{{ product.category }}</td>
                    <td>
                      <div class="fw-semibold">${{ product.price.toLocaleString() }}</div>
                      <div v-if="product.originalPrice" class="small text-muted text-decoration-line-through">
                        ${{ product.originalPrice.toLocaleString() }}
                      </div>
                    </td>
                    <td>
                      <span :class="['badge rounded-pill', product.featured ? 'text-bg-dark' : 'text-bg-light']">
                        {{ product.featured ? "Yes" : "No" }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-dark btn-sm" @click="startEdit(product)">Edit</button>
                        <button class="btn btn-outline-danger btn-sm" @click="removeProduct(product)" :disabled="deletingId === product.id">
                          {{ deletingId === product.id ? "Deleting..." : "Delete" }}
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="!filteredProducts.length" class="empty-state text-center py-5">
                <i class="bi bi-grid display-5 text-muted"></i>
                <h3 class="h5 mt-3">No matching products</h3>
                <p class="text-muted mb-0">Try a different search term or add a new product.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";

const productsStore = useProductsStore();
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const editingProductId = ref(null);
const search = ref("");
const feedback = reactive({
  type: "success",
  message: "",
});

const createInitialForm = () => ({
  name: "",
  category: "",
  image: "",
  price: 0,
  originalPrice: "",
  rating: 4.5,
  reviews: 0,
  sizes: "XS, S, M, L",
  colors: "Black, White",
  description: "",
  featured: false,
});

const form = reactive(createInitialForm());

const products = computed(() => productsStore.products);
const featuredCount = computed(() => products.value.filter((product) => product.featured).length);
const categoryCount = computed(() => new Set(products.value.map((product) => product.category)).size);
const filteredProducts = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  if (!keyword) {
    return products.value;
  }

  return products.value.filter((product) =>
    [product.name, product.category].some((value) => value.toLowerCase().includes(keyword)),
  );
});

const resetForm = ({ clearFeedback = true } = {}) => {
  Object.assign(form, createInitialForm());
  editingProductId.value = null;

  if (clearFeedback) {
    feedback.message = "";
  }
};

const serializeList = (value) =>
  value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);

const hydrateForm = (product) => {
  Object.assign(form, {
    name: product.name,
    category: product.category,
    image: product.image,
    price: product.price,
    originalPrice: product.originalPrice ?? "",
    rating: product.rating,
    reviews: product.reviews,
    sizes: product.sizes.join(", "),
    colors: product.colors.join(", "),
    description: product.description,
    featured: product.featured,
  });
};

const fetchProducts = async () => {
  loading.value = true;

  try {
    await productsStore.fetchProducts();
  } finally {
    loading.value = false;
  }
};

const saveProduct = async () => {
  saving.value = true;
  feedback.message = "";

  try {
    const payload = {
      ...form,
      originalPrice: form.originalPrice === "" ? null : Number(form.originalPrice),
      sizes: serializeList(form.sizes),
      colors: serializeList(form.colors),
      price: Number(form.price),
      rating: Number(form.rating),
      reviews: Number(form.reviews),
    };

    if (editingProductId.value) {
      await api.patch(`/products/${editingProductId.value}`, payload);
      feedback.type = "success";
      feedback.message = "Product updated successfully.";
    } else {
      await api.post("/products", payload);
      feedback.type = "success";
      feedback.message = "Product created successfully.";
    }

    await fetchProducts();
    resetForm({ clearFeedback: false });
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    saving.value = false;
  }
};

const startEdit = (product) => {
  editingProductId.value = product.id;
  feedback.message = "";
  hydrateForm(product);
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const removeProduct = async (product) => {
  const confirmed = window.confirm(`Delete "${product.name}" from the catalog?`);

  if (!confirmed) {
    return;
  }

  deletingId.value = product.id;
  feedback.message = "";

  try {
    await api.delete(`/products/${product.id}`);
    feedback.type = "success";
    feedback.message = "Product deleted successfully.";

    if (editingProductId.value === product.id) {
      resetForm();
    }

    await fetchProducts();
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    deletingId.value = null;
  }
};

onMounted(fetchProducts);
</script>

<style scoped>
.dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(0, 0, 0, 0.12), transparent 32%),
    linear-gradient(145deg, rgba(255, 255, 255, 0.94), rgba(238, 236, 232, 0.88));
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}

.metric-card {
  padding: 1rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.64);
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

.product-thumb {
  width: 72px;
  height: 84px;
  object-fit: cover;
  border-radius: 1rem;
  background: rgba(11, 11, 12, 0.06);
}

.empty-state {
  border: 1px dashed rgba(11, 11, 12, 0.14);
  border-radius: 1.25rem;
  margin-top: 1rem;
}

@media (max-width: 991px) {
  .stats-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .search-input {
    min-width: 100%;
  }
}
</style>
