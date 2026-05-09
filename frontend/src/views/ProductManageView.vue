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
                  <label class="form-label">Product Photo</label>
                  <input
                    type="file"
                    class="form-control"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    :disabled="uploadingImage"
                    @change="handleImageChange"
                  />
                  <div class="form-text">Upload JPG, PNG, WEBP, or GIF up to 5 MB.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Base Price</label>
                  <input v-model.number="form.price" type="number" min="0.01" step="0.01" class="form-control" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Original Price</label>
                  <input v-model="form.originalPrice" type="number" min="0" step="0.01" class="form-control" placeholder="Optional" />
                </div>

                <div class="col-12">
                  <div class="country-pricing-panel">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                      <div>
                        <label class="form-label mb-1">Country Prices</label>
                        <div class="form-text mt-0">Add country-specific price overrides. Customers from those countries will see that price instead of the base price.</div>
                      </div>
                      <button type="button" class="btn btn-outline-dark btn-sm" @click="openCountryPricingModal">
                        Manage Country Prices
                      </button>
                    </div>

                    <div v-if="groupedCountryPrices.length" class="country-price-list">
                      <div
                        v-for="group in groupedCountryPrices"
                        :key="`country-price-${group.price}`"
                        class="country-price-chip"
                      >
                        <div>
                          <div class="fw-semibold">${{ Number(group.price).toLocaleString() }}</div>
                          <div class="small text-muted">{{ group.countries.length }} countries</div>
                        </div>
                        <div class="country-price-chip__actions">
                          <button type="button" class="btn btn-outline-dark btn-sm" @click="filterModalByPrice(group.price)">
                            View in Popup
                          </button>
                          <button type="button" class="btn btn-outline-danger country-price-chip__remove" @click="removeCountryPriceGroup(group.price)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <div v-else class="country-pricing-empty">
                      No country-specific prices yet. Base price will be used for all customers.
                    </div>
                  </div>
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
                  <div v-if="form.image" class="image-preview">
                    <img :src="form.image" alt="Product preview" class="image-preview__img" />
                    <button type="button" class="btn btn-outline-dark btn-sm" @click="clearImage">
                      Remove Image
                    </button>
                  </div>
                  <div v-else class="image-preview image-preview--empty">
                    Product image preview will appear here after upload.
                  </div>
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

    <div v-if="showCountryPricingModal" class="country-modal" @click.self="closeCountryPricingModal">
      <div class="country-modal__dialog surface elevated">
        <div class="country-modal__header">
          <div>
            <p class="section-kicker mb-2">Country Pricing</p>
            <h3 class="h3 mb-1">Set Prices by Continent</h3>
            <p class="text-muted mb-0">Select a continent or individual countries, then apply one price to all selected countries.</p>
          </div>
          <button type="button" class="btn btn-outline-dark btn-sm" @click="closeCountryPricingModal">
            Close
          </button>
        </div>

        <div class="country-modal__toolbar">
          <div class="country-modal__toolbar-input">
            <label class="form-label">Selected Price</label>
            <input v-model="modalPrice" type="number" min="0.01" step="0.01" class="form-control" placeholder="120.00" />
          </div>
          <div class="country-modal__toolbar-actions">
            <button type="button" class="btn btn-dark" :disabled="!selectedCountries.length || !modalPrice" @click="applyModalCountryPrice">
              Apply to Selected
            </button>
            <button type="button" class="btn btn-outline-danger" :disabled="!selectedCountries.length" @click="removeSelectedCountryPrices">
              Remove Selected
            </button>
            <button type="button" class="btn btn-outline-dark" :disabled="!selectedCountries.length" @click="clearCountrySelection">
              Clear Selection
            </button>
          </div>
        </div>

        <div v-if="priceFilterOptions.length" class="country-modal__filters">
          <button
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = 'all'"
          >
            All Countries
          </button>
          <button
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === 'unassigned' ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = 'unassigned'"
          >
            Base Price
          </button>
          <button
            v-for="price in priceFilterOptions"
            :key="`price-filter-${price}`"
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === price ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = price"
          >
            ${{ Number(price).toLocaleString() }}
          </button>
        </div>

        <div class="country-modal__selection" v-if="selectedCountries.length">
          <span
            v-for="country in selectedCountries"
            :key="`selected-${country}`"
            class="country-modal__selection-chip"
          >
            {{ country }}
          </span>
        </div>

        <div class="country-modal__body">
          <section
            v-for="continent in countryPricingCatalog"
            :key="continent.name"
            class="country-continent"
          >
            <div class="country-continent__header">
              <label class="form-check d-flex align-items-center gap-2 mb-0">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="isContinentFullySelected(continent)"
                  @change="toggleContinent(continent)"
                />
                <span class="fw-semibold">{{ continent.name }}</span>
              </label>
              <span class="small text-muted">{{ selectedCountInContinent(continent) }}/{{ continent.countries.length }} selected</span>
            </div>

            <div class="country-grid">
              <label
                v-for="country in filteredCountriesForContinent(continent)"
                :key="`${continent.name}-${country}`"
                class="country-option"
                :class="{ 'country-option--active': isCountrySelected(country) }"
              >
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="isCountrySelected(country)"
                  @change="toggleCountry(country)"
                />
                <div class="country-option__body">
                  <span class="country-option__name">{{ country }}</span>
                  <span v-if="countryPriceMap[country] !== undefined" class="country-option__price">
                    ${{ Number(countryPriceMap[country]).toLocaleString() }}
                  </span>
                  <span v-else class="country-option__price country-option__price--muted">
                    Base price
                  </span>
                </div>
              </label>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { api } from "../lib/api";
import { COUNTRY_PRICING_CATALOG } from "../lib/countryPricingCatalog";
import { useProductsStore } from "../stores/products";

const productsStore = useProductsStore();
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const editingProductId = ref(null);
const search = ref("");
const uploadingImage = ref(false);
const showCountryPricingModal = ref(false);
const modalPrice = ref("");
const selectedCountries = ref([]);
const activePriceFilter = ref("all");
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

const openCountryPricingModal = () => {
  showCountryPricingModal.value = true;
  modalPrice.value = "";
  selectedCountries.value = form.countryPrices.map((entry) => entry.countryName);
  activePriceFilter.value = "all";
};

const closeCountryPricingModal = () => {
  showCountryPricingModal.value = false;
  modalPrice.value = "";
  selectedCountries.value = [];
  activePriceFilter.value = "all";
};

const removeCountryPrice = (index) => {
  form.countryPrices.splice(index, 1);
};

const filterModalByPrice = (price) => {
  showCountryPricingModal.value = true;
  selectedCountries.value = form.countryPrices
    .filter((entry) => Number(entry.price) === Number(price))
    .map((entry) => entry.countryName);
  modalPrice.value = price;
  activePriceFilter.value = Number(price);
};

const removeCountryPriceGroup = (price) => {
  form.countryPrices = form.countryPrices.filter(
    (entry) => Number(entry.price) !== Number(price),
  );
};

const isCountrySelected = (country) => selectedCountries.value.includes(country);

const toggleCountry = (country) => {
  if (isCountrySelected(country)) {
    selectedCountries.value = selectedCountries.value.filter((value) => value !== country);
    return;
  }

  selectedCountries.value = [...selectedCountries.value, country];
};

const isContinentFullySelected = (continent) =>
  continent.countries.every((country) => selectedCountries.value.includes(country));

const selectedCountInContinent = (continent) =>
  continent.countries.filter((country) => selectedCountries.value.includes(country)).length;

const filteredCountriesForContinent = (continent) =>
  continent.countries.filter((country) => {
    if (activePriceFilter.value === "all") {
      return true;
    }

    const assignedPrice = countryPriceMap.value[country];

    if (activePriceFilter.value === "unassigned") {
      return assignedPrice === undefined;
    }

    return Number(assignedPrice) === Number(activePriceFilter.value);
  });

const toggleContinent = (continent) => {
  const visibleCountries = filteredCountriesForContinent(continent);

  if (visibleCountries.length === 0) {
    return;
  }

  if (visibleCountries.every((country) => selectedCountries.value.includes(country))) {
    selectedCountries.value = selectedCountries.value.filter(
      (country) => !visibleCountries.includes(country),
    );
    return;
  }

  selectedCountries.value = Array.from(
    new Set([...selectedCountries.value, ...visibleCountries]),
  );
};

const clearCountrySelection = () => {
  selectedCountries.value = [];
};

const applyModalCountryPrice = () => {
  const price = Number(modalPrice.value);

  if (!selectedCountries.value.length || !price) {
    return;
  }

  const nextPrices = new Map(
    form.countryPrices.map((entry) => [entry.countryName, entry.price]),
  );

  selectedCountries.value.forEach((country) => {
    nextPrices.set(country, price);
  });

  form.countryPrices = Array.from(nextPrices.entries())
    .map(([countryName, value]) => ({
      countryName,
      price: value,
    }))
    .sort((left, right) => left.countryName.localeCompare(right.countryName));
};

const removeSelectedCountryPrices = () => {
  if (!selectedCountries.value.length) {
    return;
  }

  form.countryPrices = form.countryPrices.filter(
    (entry) => !selectedCountries.value.includes(entry.countryName),
  );
  selectedCountries.value = [];
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

.country-pricing-panel {
  padding: 1rem;
  border: 1px dashed rgba(77, 16, 24, 0.2);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.62);
}

.country-pricing-empty {
  color: var(--ink-muted);
  font-size: 0.95rem;
}

.country-price-list {
  display: grid;
  gap: 0.85rem;
}

.country-price-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.74);
}

.country-price-chip__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.country-price-chip__remove {
  width: 2.75rem;
  height: 2.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  padding: 0;
}

.country-modal {
  position: fixed;
  inset: 0;
  z-index: 1050;
  padding: 1.5rem;
  background: rgba(77, 16, 24, 0.38);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.country-modal__dialog {
  width: min(1080px, 100%);
  max-height: 90vh;
  padding: 1.5rem;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.country-modal__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.country-modal__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
  padding: 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.72);
}

.country-modal__toolbar-input {
  width: min(220px, 100%);
}

.country-modal__toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.country-modal__selection {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 1rem 0 0.5rem;
}

.country-modal__selection-chip {
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(77, 16, 24, 0.1);
  font-size: 0.85rem;
}

.country-modal__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-top: 1rem;
}

.country-modal__body {
  overflow: auto;
  margin-top: 1rem;
  padding-right: 0.25rem;
  display: grid;
  gap: 1rem;
}

.country-continent {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  padding: 1rem;
  background: rgba(255, 241, 184, 0.68);
}

.country-continent__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.country-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.country-option {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 0.95rem;
  background: rgba(255, 241, 184, 0.78);
  cursor: pointer;
}

.country-option--active {
  border-color: rgba(77, 16, 24, 0.3);
  box-shadow: 0 10px 22px rgba(77, 16, 24, 0.12);
}

.country-option__body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.country-option__name {
  font-weight: 600;
  line-height: 1.3;
}

.country-option__price {
  font-size: 0.82rem;
  color: var(--ink-muted);
}

.country-option__price--muted {
  opacity: 0.72;
}

.image-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  border: 1px dashed rgba(77, 16, 24, 0.22);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.56);
}

.image-preview--empty {
  justify-content: center;
  color: var(--ink-muted);
  min-height: 120px;
  text-align: center;
}

.image-preview__img {
  width: 96px;
  height: 112px;
  object-fit: cover;
  border-radius: 0.9rem;
  background: rgba(77, 16, 24, 0.08);
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

  .country-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .country-price-chip,
  .country-modal__header,
  .country-continent__header {
    flex-direction: column;
    align-items: stretch;
  }

  .country-modal {
    padding: 0.75rem;
  }

  .country-modal__dialog {
    max-height: 95vh;
    padding: 1rem;
  }

  .country-grid {
    grid-template-columns: 1fr;
  }

  .image-preview {
    flex-direction: column;
    align-items: stretch;
  }

  .image-preview__img {
    width: 100%;
    height: 240px;
  }

  .search-input {
    min-width: 100%;
  }
}
</style>
