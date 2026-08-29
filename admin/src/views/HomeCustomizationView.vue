<template>
  <div class="home-customization-page">
    <div class="page-header">
      <h1>Home View Customization</h1>
      <p class="text-muted">Customize the hero section and featured content on the homepage</p>
    </div>

    <div class="row g-4">
      <!-- Hero Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header">
            <h2 class="h5 mb-0">Hero Section</h2>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Background Image URL</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroBackgroundImage"
                  placeholder="https://example.com/hero-image.jpg"
                />
                <div class="form-text">Leave empty to use default background</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Kicker Text</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroKicker"
                  placeholder="Luxury Everyday Wear"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Title</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroTitle"
                  placeholder="AUBUN WORLD"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea
                  class="form-control"
                  v-model="form.heroCopy"
                  rows="3"
                  placeholder="A sharper first impression for the brand..."
                ></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Primary Button Text</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroPrimaryButtonText"
                  placeholder="Shop Collection"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Primary Button Link</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroPrimaryButtonLink"
                  placeholder="/products"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Secondary Button Text</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroSecondaryButtonText"
                  placeholder="Try Mix & Match"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Secondary Button Link</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.heroSecondaryButtonLink"
                  placeholder="#mix-match"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Featured Content</h2>
            <button
              type="button"
              class="btn btn-sm btn-luxury"
              @click="addFeaturedItem"
            >
              <i class="bi bi-plus-lg me-1"></i> Add Item
            </button>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Section Title</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.featuredTitle"
                  placeholder="Featured"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Section Subtitle</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.featuredSubtitle"
                  placeholder="Curated categories for effortless browsing."
                />
              </div>
            </div>

            <div v-if="form.featuredItems.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-collection d-block mb-2" style="font-size: 2rem;"></i>
              <p>No featured items yet. Click "Add Item" to create your first featured item.</p>
            </div>

            <div class="featured-items-list" v-else>
              <div
                v-for="(item, index) in form.featuredItems"
                :key="item.id || index"
                class="featured-item-card card mb-3"
              >
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-3">
                    <span class="drag-handle text-muted" style="cursor: grab;">
                      <i class="bi bi-grip-vertical"></i>
                    </span>
                    <div>
                      <strong>{{ item.label || `Item ${index + 1}` }}</strong>
                      <span class="badge bg-secondary ms-2">{{ item.routeCategory }}</span>
                    </div>
                  </div>
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="moveFeaturedItem(index, -1)"
                      :disabled="index === 0"
                    >
                      <i class="bi bi-chevron-up"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="moveFeaturedItem(index, 1)"
                      :disabled="index === form.featuredItems.length - 1"
                    >
                      <i class="bi bi-chevron-down"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger"
                      @click="removeFeaturedItem(index)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Label</label>
                      <input
                        type="text"
                        class="form-control"
                        v-model="item.label"
                        placeholder="Pants"
                      />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Route Category</label>
                      <select class="form-select" v-model="item.routeCategory">
                        <option value="">Select category</option>
                        <option
                          v-for="cat in availableCategories"
                          :key="cat"
                          :value="cat"
                        >
                          {{ cat }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Product (Optional)</label>
                      <select class="form-select" v-model="item.productId">
                        <option value="">Select a product</option>
                        <option
                          v-for="product in products"
                          :key="product.id"
                          :value="product.id"
                        >
                          {{ product.name }} ({{ product.category }})
                        </option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Title</label>
                      <input
                        type="text"
                        class="form-control"
                        v-model="item.title"
                        placeholder="Tailored Pants"
                      />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Eyebrow / Badge</label>
                      <input
                        type="text"
                        class="form-control"
                        v-model="item.eyebrow"
                        placeholder="Featured Essential"
                      />
                    </div>
                    <div class="col-12">
                      <label class="form-label">Description</label>
                      <textarea
                        class="form-control"
                        v-model="item.description"
                        rows="2"
                        placeholder="Clean structure and versatile cuts..."
                      ></textarea>
                    </div>
                    <div class="col-12">
                      <div class="form-check">
                        <input
                          type="checkbox"
                          class="form-check-input"
                          v-model="item.isActive"
                          :id="'featured-active-' + index"
                        />
                        <label class="form-check-label" :for="'featured-active-' + index">
                          Active
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="d-flex justify-content-end gap-2 mt-4">
      <button
        type="button"
        class="btn btn-outline-secondary"
        @click="resetForm"
        :disabled="loading"
      >
        Reset
      </button>
      <button
        type="button"
        class="btn btn-luxury"
        @click="saveSettings"
        :disabled="loading"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        Save Settings
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";

const router = useRouter();
const productsStore = useProductsStore();

const loading = ref(false);
const saving = ref(false);
const availableCategories = ref([]);
const products = ref([]);

const form = ref({
  heroBackgroundImage: "",
  heroKicker: "",
  heroTitle: "",
  heroCopy: "",
  heroPrimaryButtonText: "",
  heroPrimaryButtonLink: "",
  heroSecondaryButtonText: "",
  heroSecondaryButtonLink: "",
  featuredTitle: "",
  featuredSubtitle: "",
  featuredItems: [],
});

const fetchInitialData = async () => {
  loading.value = true;
  try {
    const [settingsRes, productsRes] = await Promise.all([
      api.get("/home-view"),
      productsStore.fetchProducts(),
    ]);

    if (settingsRes.settings) {
      const s = settingsRes.settings;
      form.value.heroBackgroundImage = s.heroBackgroundImage || "";
      form.value.heroKicker = s.heroKicker || "";
      form.value.heroTitle = s.heroTitle || "";
      form.value.heroCopy = s.heroCopy || "";
      form.value.heroPrimaryButtonText = s.heroPrimaryButtonText || "";
      form.value.heroPrimaryButtonLink = s.heroPrimaryButtonLink || "";
      form.value.heroSecondaryButtonText = s.heroSecondaryButtonText || "";
      form.value.heroSecondaryButtonLink = s.heroSecondaryButtonLink || "";
      form.value.featuredTitle = s.featuredTitle || "";
      form.value.featuredSubtitle = s.featuredSubtitle || "";
      form.value.featuredItems = (settingsRes.featuredItems || []).map((item, idx) => ({
        ...item,
        id: item.id,
        sortOrder: item.sortOrder ?? idx,
        isActive: item.isActive ?? true,
      }));
    }

    availableCategories.value = [...new Set(productsStore.products.map(p => p.category))].sort();
    products.value = productsStore.products;
  } catch (error) {
    console.error("Failed to load home view settings:", error);
  } finally {
    loading.value = false;
  }
};

const addFeaturedItem = () => {
  form.value.featuredItems.push({
    label: "",
    routeCategory: "",
    title: "",
    eyebrow: "",
    description: "",
    productId: null,
    sortOrder: form.value.featuredItems.length,
    isActive: true,
  });
};

const removeFeaturedItem = (index) => {
  form.value.featuredItems.splice(index, 1);
  form.value.featuredItems.forEach((item, idx) => {
    item.sortOrder = idx;
  });
};

const moveFeaturedItem = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= form.value.featuredItems.length) return;

  [form.value.featuredItems[index], form.value.featuredItems[newIndex]] = [
    form.value.featuredItems[newIndex],
    form.value.featuredItems[index],
  ];

  form.value.featuredItems.forEach((item, idx) => {
    item.sortOrder = idx;
  });
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const payload = {
      heroBackgroundImage: form.value.heroBackgroundImage,
      heroKicker: form.value.heroKicker,
      heroTitle: form.value.heroTitle,
      heroCopy: form.value.heroCopy,
      heroPrimaryButtonText: form.value.heroPrimaryButtonText,
      heroPrimaryButtonLink: form.value.heroPrimaryButtonLink,
      heroSecondaryButtonText: form.value.heroSecondaryButtonText,
      heroSecondaryButtonLink: form.value.heroSecondaryButtonLink,
      featuredTitle: form.value.featuredTitle,
      featuredSubtitle: form.value.featuredSubtitle,
      featuredItems: form.value.featuredItems.map(item => ({
        label: item.label,
        routeCategory: item.routeCategory,
        title: item.title,
        eyebrow: item.eyebrow,
        description: item.description,
        productId: item.productId,
        sortOrder: item.sortOrder,
        isActive: item.isActive,
      })),
    };

    await api.patch("/home-view", payload);
    alert("Settings saved successfully!");
    await fetchInitialData();
  } catch (error) {
    console.error("Failed to save settings:", error);
    alert(error.message || "Failed to save settings");
  } finally {
    saving.value = false;
  }
};

const resetForm = async () => {
  if (confirm("Are you sure you want to reset the form to current saved settings?")) {
    await fetchInitialData();
  }
};

onMounted(() => {
  fetchInitialData();
});
</script>

<style scoped>
.home-customization-page {
  padding: 1.5rem;
}

.page-header {
  margin-bottom: 1.5rem;
}

.page-header h1 {
  font-size: 1.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.featured-item-card {
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: var(--radius-lg);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.featured-item-card:hover {
  border-color: rgba(77, 16, 24, 0.24);
  box-shadow: 0 4px 12px rgba(77, 16, 24, 0.08);
}

.featured-item-card .card-header {
  background: rgba(255, 248, 228, 0.5);
  border-bottom: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
  padding: 0.75rem 1rem;
}

.featured-item-card .card-body {
  padding: 1rem;
}

.drag-handle:hover {
  color: var(--primary-black) !important;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

@media (max-width: 767.98px) {
  .home-customization-page {
    padding: 1rem;
  }

  .featured-item-card .card-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .btn-group-sm {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>