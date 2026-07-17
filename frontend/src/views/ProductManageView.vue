<template>
  <div class="dashboard-page py-5">
    <section class="container mb-4">
      <div class="dashboard-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">Sales Management</p>
            <h1 class="display-5 mb-3">Product Management</h1>
            <p class="text-muted mb-0">
              Add new arrivals, refine catalog data, and retire products from the collection without leaving the storefront workflow.
            </p>
            <div class="mt-4">
              <div class="d-flex flex-wrap gap-3">
                <router-link to="/dashboard/shipping" class="btn btn-outline-dark">
                  Open Shipping Settings
                </router-link>
                <router-link to="/dashboard/vouchers" class="btn btn-dark">
                  Manage Vouchers
                </router-link>
              </div>
            </div>
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
        <div class="col-12">
          <div class="surface p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Catalog Control</p>
                <h2 class="h3 mb-0">Manage Products</h2>
              </div>
              <div class="d-flex gap-3 align-items-center">
                <div class="position-relative">
                  <i class="bi bi-search search-icon"></i>
                  <input v-model="search" type="search" class="form-control search-input" placeholder="Search by name or category" />
                </div>
                <button class="btn btn-luxury" @click="openAddProductModal">
                  <i class="bi bi-plus-lg me-1"></i> Add Product
                </button>
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
                      <div class="fw-semibold">${{ (product.basePrice ?? product.price).toLocaleString() }}</div>
                      <div v-if="product.originalPrice" class="small text-muted text-decoration-line-through">
                        ${{ product.originalPrice.toLocaleString() }}
                      </div>
                      <div v-if="product.countryPrices?.length" class="small text-muted mt-1">
                        {{ summarizeCountryPriceUsage(product.countryPrices) }}
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
import { computed, onMounted, reactive, ref } from "vue";
import { api } from "../lib/api";
import { COUNTRY_PRICING_CATALOG } from "../lib/countryPricingCatalog";
import { useProductsStore } from "../stores/products";
import ProductFormModal from "../components/ProductFormModal.vue";
import CountryPricingModal from "../components/CountryPricingModal.vue";

const productsStore = useProductsStore();
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const editingProductId = ref(null);
const search = ref("");
const uploadingImage = ref(false);
const showProductModal = ref(false);
const showCountryPricingModal = ref(false);
const countryPricingRef = ref(null);
const feedback = reactive({
  type: "success",
  message: "",
});
const countryPricingCatalog = COUNTRY_PRICING_CATALOG;

const createInitialForm = () => ({
  name: "",
  category: "",
  image: "",
  price: 0,
  originalPrice: "",
  countryPrices: [],
  rating: 4.5,
  reviews: 0,
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
    image: product.image,
    price: product.basePrice ?? product.price,
    originalPrice: product.originalPrice ?? "",
    countryPrices: (product.countryPrices || []).map((entry) => ({
      countryName: entry.countryName,
      price: entry.price,
    })),
    rating: product.rating,
    reviews: product.reviews,
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
      countryPrices: form.countryPrices
        .map((entry) => ({
          countryName: entry.countryName.trim(),
          price: entry.price === "" ? "" : Number(entry.price),
        }))
        .filter((entry) => entry.countryName || entry.price !== ""),
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

onMounted(fetchProducts);
</script>

<style scoped>
.dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.16), transparent 32%),
    linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.42));
}

.stats-grid {
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
  background: rgba(77, 16, 24, 0.08);
}

.empty-state {
  border: 1px dashed rgba(77, 16, 24, 0.18);
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
