<template>
  <div class="dashboard-page py-4">
    <section class="container mb-4">
      <div class="dashboard-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4 mb-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">Sales Management</p>
            <h1 class="display-5 mb-3">Product Management</h1>
            <p class="text-muted mb-0">
              Add new arrivals, refine catalog data, and manage inventory without leaving the storefront workflow.
            </p>
            <div class="mt-4">
              <div class="d-flex flex-wrap gap-3">
                <router-link to="/shipping" class="btn btn-outline-dark">
                  Open Shipping Settings
                </router-link>
                <router-link to="/vouchers" class="btn btn-dark">
                  Manage Vouchers
                </router-link>
              </div>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
              <button class="btn btn-outline-primary btn-sm" @click="exportProducts" :disabled="loading">
                <i class="bi bi-download me-1"></i> Export
              </button>
              <button class="btn btn-outline-secondary btn-sm" @click="importProducts" :disabled="loading">
                <i class="bi bi-upload me-1"></i> Import
              </button>
            </div>
          </div>
        </div>

        <!-- Stats Grid - Full Width -->
        <div class="stats-section">
          <div class="stats-grid">
            <div class="metric-card primary">
              <div class="metric-icon">
                <i class="bi bi-box-seam"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Total Products</span>
                <strong class="metric-value">{{ products.length }}</strong>
              </div>
            </div>
            <div class="metric-card featured">
              <div class="metric-icon">
                <i class="bi bi-star-fill"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Featured</span>
                <strong class="metric-value">{{ featuredCount }}</strong>
              </div>
            </div>
            <div class="metric-card categories">
              <div class="metric-icon">
                <i class="bi bi-tags"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Categories</span>
                <strong class="metric-value">{{ categoryCount }}</strong>
              </div>
            </div>
            <div class="metric-card stock-total">
              <div class="metric-icon">
                <i class="bi bi-boxes"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Total Stock</span>
                <strong class="metric-value">{{ totalStock }}</strong>
              </div>
            </div>
            <div class="metric-card stock-low">
              <div class="metric-icon">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Low Stock</span>
                <strong class="metric-value">{{ lowStockCount }}</strong>
                <span class="metric-sublabel">(1-10 units)</span>
              </div>
            </div>
            <div class="metric-card stock-out">
              <div class="metric-icon">
                <i class="bi bi-x-circle"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Out of Stock</span>
                <strong class="metric-value">{{ outOfStockCount }}</strong>
                <span class="metric-sublabel">(0 units)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container">
      <div class="row g-4">
        <div class="col-12">
          <div class="surface p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Catalog Control</p>
                <h2 class="h3 mb-0">Manage Products</h2>
              </div>
              <div class="d-flex gap-3 align-items-center flex-wrap">
                <div class="position-relative flex-grow-1" style="max-width: 380px;">
                  <i class="bi bi-search search-icon"></i>
                  <input v-model="search" type="search" class="form-control search-input" placeholder="Search by name or category" />
                </div>
                <div class="d-flex gap-2">
                  <select v-model="categoryFilter" class="form-select form-select-sm stock-filter-select" @change="onFilterChange">
                    <option value="">All Categories</option>
                    <option value="Men">Men</option>
                    <option value="Women">Women</option>
                    <option value="Unisex">Unisex</option>
                    <option value="Kids">Kids</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Footwear">Footwear</option>
                  </select>
                  <select v-model="stockFilter" class="form-select form-select-sm stock-filter-select" @change="onFilterChange">
                    <option value="">All Stock Levels</option>
                    <option value="out">Out of Stock (0)</option>
                    <option value="low">Low Stock (1-10)</option>
                    <option value="ok">In Stock (11+)</option>
                  </select>
                  <button class="btn btn-luxury" @click="openAddProductModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Product
                  </button>
                </div>
              </div>
            </div>

            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading products...</span>
              </div>
              <p class="text-muted mt-2 mb-0">Loading products...</p>
            </div>

            <div v-else class="dashboard-table-wrap">
              <!-- Desktop Table View -->
              <div class="d-none d-lg-block">
                <div class="table-responsive">
                  <table class="table align-middle dashboard-table">
                  <thead>
                    <tr>
                      <th style="width: 100px;">Product</th>
                      <th class="d-none d-md-table-cell" style="width: 80px;">Visibility</th>
                      <th class="d-none d-lg-table-cell" style="width: 140px;">Category</th>
                      <th class="d-none d-xl-table-cell" style="width: 140px;">Subcategory</th>
                      <th style="width: 140px;">Price</th>
                      <th style="width: 140px;">Stock</th>
                      <th class="d-none d-xl-table-cell" style="width: 100px;">Featured</th>
                      <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="product in filteredProducts" :key="product.id" :class="{ 'editing-stock': editingStockId === product.id }">
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img :src="product.image" :alt="product.name" class="product-thumb" />
                          <div class="product-info">
                            <div class="fw-semibold">{{ product.name }}</div>
                            <div class="small text-muted">{{ product.sizes.join(", ") }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="d-none d-md-table-cell">
                        <span :class="['badge rounded-pill', product.isShowed ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                          <i class="bi" :class="product.isShowed ? 'bi-eye-fill me-1' : 'bi-eye-slash-fill me-1'"></i>
                          {{ product.isShowed ? "Visible" : "Hidden" }}
                        </span>
                      </td>
                      <td class="d-none d-lg-table-cell">
                        <span class="badge bg-light text-dark">{{ product.category }}</span>
                      </td>
                      <td class="d-none d-xl-table-cell">
                        <span v-if="product.subcategory" class="badge bg-info-subtle text-info">{{ product.subcategory }}</span>
                        <span v-else class="text-muted">—</span>
                      </td>
                      <td>
                        <div class="price-cell">
                          <div class="fw-semibold">${{ (product.basePrice ?? product.price).toLocaleString() }}</div>
                          <div v-if="product.originalPrice" class="small text-muted text-decoration-line-through">
                            ${{ product.originalPrice.toLocaleString() }}
                          </div>
                          <div v-if="product.countryPrices?.length" class="small text-muted mt-1">
                            <i class="bi bi-globe me-1"></i>{{ summarizeCountryPriceUsage(product.countryPrices) }}
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="stock-cell">
                          <StockBadge :stock="product.stock" :editing="editingStockId === product.id" @click="startInlineStockEdit(product)" />
                          <div v-if="editingStockId === product.id" class="stock-edit-inline mt-2">
                            <div class="input-group input-group-sm">
                              <input
                                v-model.number="editingStockValue"
                                type="number"
                                min="0"
                                step="1"
                                class="form-control"
                                @keydown.enter="saveInlineStock(product)"
                                @keydown.esc="cancelInlineStock"
                                @blur="saveInlineStock(product)"
                                ref="stockInput"
                                aria-label="Edit stock quantity"
                              />
                              <button class="btn btn-success btn-sm" @click="saveInlineStock(product)" :disabled="savingStockId === product.id" title="Save (Enter)">
                                <i class="bi bi-check"></i>
                              </button>
                              <button class="btn btn-outline-secondary btn-sm" @click="cancelInlineStock" title="Cancel (Esc)">
                                <i class="bi bi-x"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="d-none d-xl-table-cell">
                        <span :class="['badge rounded-pill', product.featured ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                          <i class="bi" :class="product.featured ? 'bi-star-fill me-1' : 'bi-star me-1'"></i>
                          {{ product.featured ? "Featured" : "Regular" }}
                        </span>
                      </td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                          <button class="btn btn-outline-dark btn-sm" @click="startEdit(product)" :disabled="savingStockId === product.id || deletingId === product.id" title="Edit Product">
                            <i class="bi bi-pencil"></i>
                            <span class="d-none d-sm-inline">Edit</span>
                          </button>
                          <button class="btn btn-outline-danger btn-sm" @click="removeProduct(product)" :disabled="deletingId === product.id || savingStockId === product.id" title="Delete Product">
                            <i class="bi bi-trash"></i>
                            <span class="d-none d-sm-inline">{{ deletingId === product.id ? "Deleting..." : "Delete" }}</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
                </div>
              </div>

              <!-- Mobile Card View -->
              <div class="d-lg-none product-cards mt-3">
                <div v-for="product in filteredProducts" :key="product.id" class="product-card surface p-3 mb-3">
                  <div class="d-flex gap-3 mb-3">
                    <img :src="product.image" :alt="product.name" class="product-card-thumb" />
                    <div class="flex-grow-1">
                      <div class="fw-semibold">{{ product.name }}</div>
                      <div class="small text-muted">{{ product.sizes.join(", ") }}</div>
                    </div>
                  </div>
                  <div class="row g-2 text-center">
                    <div class="col-4">
                      <div class="small text-muted">Price</div>
                      <div class="fw-semibold">${{ (product.basePrice ?? product.price).toLocaleString() }}</div>
                    </div>
                    <div class="col-4">
                      <div class="small text-muted">Stock</div>
                      <StockBadge :stock="product.stock" />
                    </div>
                    <div class="col-4">
                      <div class="small text-muted">Status</div>
                      <span :class="['badge rounded-pill', product.isShowed ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                        {{ product.isShowed ? "Visible" : "Hidden" }}
                      </span>
                    </div>
                  </div>
                  <div class="d-flex gap-2 mt-3 pt-2 border-top">
                    <button class="btn btn-outline-primary flex-grow-1 btn-sm" @click="startInlineStockEdit(product)" :disabled="editingStockId === product.id || savingStockId === product.id">
                      <i class="bi bi-box-seam me-1"></i> Stock
                    </button>
                    <button class="btn btn-outline-dark flex-grow-1 btn-sm" @click="startEdit(product)" :disabled="editingStockId === product.id || savingStockId === product.id">
                      <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <button class="btn btn-outline-danger flex-grow-1 btn-sm" @click="removeProduct(product)" :disabled="deletingId === product.id || editingStockId === product.id">
                      <i class="bi bi-trash me-1"></i> Delete
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="!filteredProducts.length" class="empty-state text-center py-5">
                <i class="bi bi-grid display-5 text-muted"></i>
                <h3 class="h5 mt-3">{{ search.value || stockFilter.value ? "No matching products" : "No products yet" }}</h3>
                <p class="text-muted mb-3">{{ search.value || stockFilter.value ? "Try a different search term or filter." : "Get started by adding your first product." }}</p>
                <button v-if="!search.value && !stockFilter.value" class="btn btn-luxury" @click="openAddProductModal">
                  <i class="bi bi-plus-lg me-1"></i> Add Product
                </button>
              </div>
            </div>

            <!-- Results Summary -->
            <div v-if="!loading && filteredProducts.length" class="mt-3 d-flex justify-content-between align-items-center small text-muted">
              <span>Showing {{ filteredProducts.length }} of {{ products.length }} products</span>
              <span v-if="stockFilter.value" class="badge bg-primary-subtle text-primary">
                Filtered: {{ stockFilterLabel }}
                <button class="btn-close btn-close-white ms-2" @click="clearStockFilter" aria-label="Clear filter"></button>
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <ProductFormModal
      v-model="showProductModal"
      :form="form"
      :editing-product-id="editingProductId"
      :saving="saving"
      :uploading-image="uploadingImage"
      :feedback="feedback"
      :grouped-country-prices="groupedCountryPrices"
      @update:model-value="closeProductModal"
      @save="saveProduct"
      @image-change="handleImageChange"
      @clear-image="clearImage"
      @open-country-pricing="openCountryPricingModal"
      @filter-country-price-by="filterModalByPrice"
      @remove-country-price-group="removeCountryPriceGroup"
    />

    <CountryPricingModal
      ref="countryPricingRef"
      v-model="showCountryPricingModal"
      :country-pricing-catalog="countryPricingCatalog"
      :country-price-map="countryPriceMap"
      :price-filter-options="priceFilterOptions"
      @apply="handleApplyCountryPrice"
      @remove-selected="handleRemoveSelectedCountryPrices"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import { api } from "../lib/api";
import { COUNTRY_PRICING_CATALOG } from "../lib/countryPricingCatalog";
import { useProductsStore } from "../stores/products";
import ProductFormModal from "../components/ProductFormModal.vue";
import CountryPricingModal from "../components/CountryPricingModal.vue";
import StockBadge from "../components/StockBadge.vue";

const productsStore = useProductsStore();
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const savingStockId = ref(null);
const editingProductId = ref(null);
const editingStockId = ref(null);
const editingStockValue = ref(0);
const search = ref("");
const categoryFilter = ref("");
const stockFilter = ref("");
const uploadingImage = ref(false);
const showProductModal = ref(false);
const showCountryPricingModal = ref(false);
const countryPricingRef = ref(null);
const stockInputRef = ref(null);
const feedback = reactive({
  type: "success",
  message: "",
});
const countryPricingCatalog = COUNTRY_PRICING_CATALOG;

const createInitialForm = () => ({
  name: "",
  category: "",
  subcategory: "",
  image: "",
  price: 0,
  originalPrice: "",
  stock: 0,
  countryPrices: [],
  sizes: "XS, S, M, L",
  colors: "Black, White",
  description: "",
  featured: false,
  isShowed: true,
});

const form = reactive(createInitialForm());

const products = computed(() => productsStore.products);
const countryPriceMap = computed(() =>
  Object.fromEntries(
    form.countryPrices.map((entry) => [entry.countryName, entry.price]),
  ),
);
const groupedCountryPrices = computed(() =>
  Object.values(
    form.countryPrices.reduce((groups, entry) => {
      const key = String(entry.price);
      groups[key] ??= {
        price: Number(entry.price),
        countries: [],
      };
      groups[key].countries.push(entry.countryName);
      return groups;
    }, {}),
  )
    .map((group) => ({
      ...group,
      countries: [...group.countries].sort((left, right) => left.localeCompare(right)),
    }))
    .sort((left, right) => left.price - right.price),
);
const priceFilterOptions = computed(() =>
  [...new Set(form.countryPrices.map((entry) => Number(entry.price)))]
    .sort((left, right) => left - right),
);
const featuredCount = computed(() => products.value.filter((product) => product.featured).length);
const categoryCount = computed(() => new Set(products.value.map((product) => product.category)).size);
const totalStock = computed(() => products.value.reduce((sum, product) => sum + (product.stock || 0), 0));
const lowStockCount = computed(() => products.value.filter((product) => product.stock > 0 && product.stock <= 10).length);
const outOfStockCount = computed(() => products.value.filter((product) => product.stock === 0).length);
const filteredProducts = computed(() => {
  let result = products.value;

  const keyword = search.value.trim().toLowerCase();
  if (keyword) {
    result = result.filter((product) =>
      [product.name, product.category, product.subcategory].some((value) => value?.toLowerCase().includes(keyword)),
    );
  }

  if (categoryFilter.value) {
    result = result.filter((p) => p.category === categoryFilter.value);
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

const stockFilterLabel = computed(() => {
  const labels = { out: "Out of Stock", low: "Low Stock", ok: "In Stock" };
  return labels[stockFilter.value] || "";
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

const clearImage = () => {
  form.image = "";
};

const openAddProductModal = () => {
  resetForm();
  showProductModal.value = true;
};

const closeProductModal = () => {
  showProductModal.value = false;
  resetForm();
};

const openCountryPricingModal = () => {
  const initialCountries = form.countryPrices.map((entry) => entry.countryName);
  countryPricingRef.value?.open(initialCountries);
  showCountryPricingModal.value = true;
};

const filterModalByPrice = (price) => {
  const countries = form.countryPrices
    .filter((entry) => Number(entry.price) === Number(price))
    .map((entry) => entry.countryName);
  countryPricingRef.value?.openWithFilter(price, countries);
  showCountryPricingModal.value = true;
};

const removeCountryPriceGroup = (price) => {
  form.countryPrices = form.countryPrices.filter(
    (entry) => Number(entry.price) !== Number(price),
  );
};

const handleApplyCountryPrice = (price, countries) => {
  const nextPrices = new Map(
    form.countryPrices.map((entry) => [entry.countryName, entry.price]),
  );

  countries.forEach((country) => {
    nextPrices.set(country, price);
  });

  form.countryPrices = Array.from(nextPrices.entries())
    .map(([countryName, value]) => ({
      countryName,
      price: value,
    }))
    .sort((left, right) => left.countryName.localeCompare(right.countryName));
};

const handleRemoveSelectedCountryPrices = (countries) => {
  form.countryPrices = form.countryPrices.filter(
    (entry) => !countries.includes(entry.countryName),
  );
};

const hydrateForm = (product) => {
  Object.assign(form, {
    name: product.name,
    category: product.category,
    subcategory: product.subcategory ?? "",
    image: product.image,
    price: product.basePrice ?? product.price,
    originalPrice: product.originalPrice ?? "",
    stock: product.stock ?? 0,
    countryPrices: (product.countryPrices || []).map((entry) => ({
      countryName: entry.countryName,
      price: entry.price,
    })),
    sizes: product.sizes.join(", "),
    colors: product.colors.join(", "),
    description: product.description,
    featured: product.featured,
    isShowed: product.isShowed,
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

const handleImageChange = async (event) => {
  const file = event.target.files?.[0];

  if (!file) {
    return;
  }

  uploadingImage.value = true;
  feedback.message = "";

  try {
    const formData = new FormData();
    formData.append("image", file);

    const { image } = await api.post("/products/upload-image", formData);
    form.image = image.url;
    feedback.type = "success";
    feedback.message = "Image uploaded successfully.";
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    uploadingImage.value = false;
    event.target.value = "";
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
      stock: Number(form.stock),
      countryPrices: form.countryPrices
        .map((entry) => ({
          countryName: entry.countryName.trim(),
          price: entry.price === "" ? "" : Number(entry.price),
        }))
        .filter((entry) => entry.countryName || entry.price !== ""),
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
    showProductModal.value = false;
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
  showProductModal.value = true;
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

const startInlineStockEdit = (product) => {
  editingStockId.value = product.id;
  editingStockValue.value = product.stock;
  nextTick(() => {
    stockInputRef.value?.$el?.focus?.();
    stockInputRef.value?.$el?.select?.();
  });
};

const cancelInlineStock = () => {
  editingStockId.value = null;
  editingStockValue.value = 0;
};

const saveInlineStock = async (product) => {
  const newStock = editingStockValue.value;
  if (newStock === product.stock) {
    cancelInlineStock();
    return;
  }

  savingStockId.value = product.id;

  try {
    await api.patch(`/products/${product.id}`, { stock: newStock });
    feedback.type = "success";
    feedback.message = `Stock updated: ${product.name} (${product.stock} → ${newStock})`;
    await fetchProducts();
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
    editingStockValue.value = product.stock;
  } finally {
    savingStockId.value = null;
    editingStockId.value = null;
  }
};

const onFilterChange = () => {
  // Filter changes reactively via computed
};

const clearStockFilter = () => {
  stockFilter.value = "";
};

const summarizeCountryPriceUsage = (countryPrices) => {
  const grouped = Object.values(
    countryPrices.reduce((groups, entry) => {
      const key = String(entry.price);
      groups[key] ??= {
        price: Number(entry.price),
        count: 0,
      };
      groups[key].count += 1;
      return groups;
    }, {}),
  ).sort((left, right) => left.price - right.price);

  return grouped
    .map((group) => `$${group.price.toLocaleString()}: ${group.count} countries`)
    .join(" | ");
};

watch(search, () => {
  // Reactive filtering
});

watch(stockFilter, () => {
  // Reactive filtering
});

onMounted(fetchProducts);
</script>

<style scoped>
.dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.16), transparent 32%),
    linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.42));
}

.stats-section {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

@media (min-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(6, 1fr);
  }
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: #fff;
  transition: all 0.2s ease;
  min-height: 90px;
}

.metric-card:hover {
  border-color: rgba(77, 16, 24, 0.15);
  box-shadow: 0 4px 16px rgba(77, 16, 24, 0.08);
  transform: translateY(-2px);
}

.metric-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 0.75rem;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.metric-card.primary .metric-icon {
  background: linear-gradient(135deg, #4d1018, #6c1823);
  color: #fff;
}

.metric-card.featured .metric-icon {
  background: linear-gradient(135deg, #c48d0c, #feb511);
  color: #fff;
}

.metric-card.categories .metric-icon {
  background: linear-gradient(135deg, #6c1823, #4d1018);
  color: #fff;
}

.metric-card.stock-total .metric-icon {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: #fff;
}

.metric-card.stock-low .metric-icon {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  color: #fff;
}

.metric-card.stock-out .metric-icon {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: #fff;
}

.metric-content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
}

.metric-label {
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--ink-muted);
  line-height: 1.3;
  margin-bottom: 0.125rem;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a2e;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.metric-sublabel {
  font-size: 0.6rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.7;
}

.metric-card.stock-low .metric-sublabel {
  color: #b8860b;
}

.metric-card.stock-out .metric-sublabel {
  color: #c82333;
}

.search-input {
  min-width: 280px;
  padding-left: 2.5rem;
}

@media (max-width: 767px) {
  .search-input {
    min-width: 100%;
  }
}

.search-icon {
  position: absolute;
  top: 50%;
  left: 0.9rem;
  transform: translateY(-50%);
  color: var(--ink-muted);
  pointer-events: none;
}

.stock-filter-select {
  width: auto;
  min-width: 180px;
}

@media (max-width: 767px) {
  .stock-filter-select {
    min-width: 140px;
  }
}

.dashboard-table-wrap {
  overflow-x: auto;
}

.dashboard-table {
  margin-bottom: 0;
}

.dashboard-table th {
  font-size: 0.7rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--ink-muted);
  border-bottom-width: 2px;
  border-bottom-color: rgba(77, 16, 24, 0.1);
  background: rgba(77, 16, 24, 0.02);
  white-space: nowrap;
}

.dashboard-table td,
.dashboard-table th {
  background: transparent;
  padding-top: 0.875rem;
  padding-bottom: 0.875rem;
  vertical-align: middle;
}

.dashboard-table tbody tr {
  transition: background-color 0.15s ease;
}

.dashboard-table tbody tr:hover {
  background: rgba(255, 241, 184, 0.25);
}

.dashboard-table tbody tr.editing-stock {
  background: rgba(40, 167, 69, 0.05);
}

.product-thumb {
  width: 60px;
  height: 72px;
  object-fit: cover;
  border-radius: 0.75rem;
  background: rgba(77, 16, 24, 0.08);
  flex-shrink: 0;
}

.product-info {
  min-width: 0;
}

.product-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  transition: all 0.2s ease;
}

.product-card:hover {
  border-color: rgba(77, 16, 24, 0.15);
  box-shadow: 0 4px 12px rgba(77, 16, 24, 0.08);
}

.product-card-thumb {
  width: 72px;
  height: 84px;
  object-fit: cover;
  border-radius: 0.75rem;
  background: rgba(77, 16, 24, 0.08);
  flex-shrink: 0;
}

.price-cell {
  line-height: 1.4;
}

.stock-cell {
  min-width: 120px;
}

.stock-edit-inline .input-group {
  max-width: 200px;
  margin: 0 auto;
}

.stock-actions {
  text-align: center;
}

.empty-state {
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1.25rem;
  margin-top: 1rem;
  padding: 3rem 1.5rem;
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

  .dashboard-hero {
    padding: 2rem 1.5rem;
  }

  .dashboard-hero h1 {
    font-size: 1.75rem;
  }

  .search-input {
    min-width: 100%;
  }

  .d-flex.gap-3.align-items-center.flex-wrap {
    flex-direction: column;
    align-items: stretch !important;
  }

  .stock-filter-select {
    width: 100%;
    min-width: 100%;
  }
}

@media (max-width: 575px) {
  .dashboard-table th,
  .dashboard-table td {
    padding: 0.625rem 0.5rem;
  }

  .product-thumb {
    width: 48px;
    height: 56px;
  }

  .metric-card strong {
    font-size: 1.25rem;
  }
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8rem;
}

.btn-outline-primary.btn-sm,
.btn-outline-dark.btn-sm,
.btn-outline-danger.btn-sm {
  min-width: 72px;
}
</style>