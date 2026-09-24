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
      <!-- Typography / Global Font -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h5 mb-0">Typography — Global Font</h2>
            <span class="badge text-bg-light border">.ttf only · replaces all storefront font</span>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">
              Upload a <code>.ttf</code> file to replace the entire storefront font (body + headings).
              Max 10&nbsp;MB. Save settings after uploading to apply it live. Remove to restore defaults.
            </p>

            <div class="row g-3 align-items-stretch">
              <div class="col-lg-6">
                <label class="form-label">Font family name</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.customFontFamily"
                  placeholder="e.g. My Luxury Sans"
                />
                <div class="form-text">Used as the CSS font-family name on the storefront.</div>

                <label class="form-label mt-3">Upload .ttf file</label>
                <input
                  ref="fontInputRef"
                  type="file"
                  class="form-control"
                  accept=".ttf,font/ttf"
                  @change="onFontFileSelected"
                />
                <div class="form-text">Only TrueType <code>.ttf</code> files are accepted.</div>

                <div v-if="selectedFontName" class="alert alert-secondary mt-3 mb-0 py-2 px-3">
                  <i class="bi bi-file-earmark-font me-2"></i>Selected: <strong>{{ selectedFontName }}</strong>
                </div>

                <div v-if="fontError" class="alert alert-danger mt-3 mb-0 py-2 px-3">
                  <i class="bi bi-exclamation-triangle me-2"></i>{{ fontError }}
                </div>

                <div v-if="form.customFontUrl" class="alert alert-success mt-3 mb-0 py-2 px-3 text-break">
                  <i class="bi bi-check-circle me-2"></i>Active font:
                  <strong>{{ form.customFontFamily || 'Custom font' }}</strong><br />
                  <small class="text-muted">{{ form.customFontUrl }}</small>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                  <button
                    type="button"
                    class="btn btn-luxury btn-sm"
                    @click="uploadCustomFont"
                    :disabled="!fontFile || uploadingFont"
                  >
                    <span v-if="uploadingFont" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-upload me-1"></i>
                    {{ uploadingFont ? 'Uploading…' : 'Upload Font' }}
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    @click="removeCustomFont"
                    :disabled="(!form.customFontUrl && !form.customFontFilename) || uploadingFont || removingFont"
                  >
                    <span v-if="removingFont" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-trash me-1"></i>
                    {{ removingFont ? 'Removing…' : 'Remove / Reset' }}
                  </button>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="font-preview-box">
                  <p class="section-kicker mb-2">Live preview</p>
                  <p class="font-preview-big" :style="fontPreviewStyle">Aa Bb Cc Dd Ee</p>
                  <p class="font-preview-sample" :style="fontPreviewStyle">
                    The quick brown fox jumps over the lazy dog 0123456789
                  </p>
                  <p class="font-preview-meta mb-0">
                    {{ form.customFontFamily || 'Default storefront font' }} · Body + Headings
                  </p>
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
        :disabled="loading || saving"
      >
        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
        Save Settings
      </button>
    </div>

    <StatusModal
      v-model="statusModal.show"
      :type="statusModal.type"
      :title="statusModal.title"
      :message="statusModal.message"
      :confirm-text="statusModal.confirmText"
      :cancel-text="statusModal.cancelText"
      :show-cancel="statusModal.showCancel"
      :loading="statusModal.loading"
      @confirm="onStatusConfirm"
      @cancel="onStatusCancel"
      @close="onStatusClose"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";
import StatusModal from "../components/StatusModal.vue";

const router = useRouter();
const productsStore = useProductsStore();

const loading = ref(false);
const saving = ref(false);
const availableCategories = ref([]);
const products = ref([]);

const fontInputRef = ref(null);
const fontFile = ref(null);
const selectedFontName = ref("");
const uploadingFont = ref(false);
const removingFont = ref(false);
const fontError = ref("");

const statusModal = ref({
  show: false,
  type: "success",
  title: "",
  message: "",
  confirmText: "Got it",
  cancelText: "Cancel",
  showCancel: false,
  loading: false,
  pendingAction: null,
});

const showStatus = (type, title, message, options = {}) => {
  statusModal.value = {
    show: true,
    type,
    title,
    message,
    confirmText: options.confirmText || (type === "warning" ? "Confirm" : "Got it"),
    cancelText: options.cancelText || "Cancel",
    showCancel: options.showCancel || false,
    loading: false,
    pendingAction: options.onConfirm || null,
  };
};

const showSuccess = (title, message, options = {}) =>
  showStatus("success", title, message, options);

const showError = (title, message, options = {}) =>
  showStatus("error", title, message, options);

const askConfirm = (title, message, options = {}) =>
  showStatus("warning", title, message, {
    showCancel: true,
    confirmText: options.confirmText || "Confirm",
    ...options,
  });

const onStatusConfirm = async () => {
  const action = statusModal.value.pendingAction;
  statusModal.value.pendingAction = null;
  statusModal.value.show = false;
  statusModal.value.loading = false;
  if (!action) return;
  await action();
};

const onStatusCancel = () => {
  statusModal.value.pendingAction = null;
  statusModal.value.show = false;
};

const onStatusClose = () => {
  if (statusModal.value.loading) return;
  statusModal.value.pendingAction = null;
};

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
  customFontFamily: "",
  customFontUrl: "",
  customFontFilename: "",
});

const fontPreviewStyle = computed(() => {
  if (form.value.customFontFamily && form.value.customFontUrl) {
    return { fontFamily: `'${form.value.customFontFamily}', sans-serif` };
  }
  return {};
});

const loadFontForPreview = async (family, url) => {
  if (!family || !url) return;
  try {
    if (!('FontFace' in window)) return;
    const face = new FontFace(family, `url("${url}")`, { display: 'swap' });
    const loaded = await face.load();
    document.fonts.add(loaded);
  } catch (err) {
    console.warn('Font preview failed to load:', err);
  }
};

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
      form.value.customFontFamily = s.customFontFamily || "";
      form.value.customFontUrl = s.customFontUrl || "";
      form.value.customFontFilename = s.customFontFilename || "";
      form.value.featuredItems = (settingsRes.featuredItems || []).map((item, idx) => ({
        ...item,
        id: item.id,
        sortOrder: item.sortOrder ?? idx,
        isActive: item.isActive ?? true,
      }));

      if (form.value.customFontFamily && form.value.customFontUrl) {
        loadFontForPreview(form.value.customFontFamily, form.value.customFontUrl);
      }
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

const onFontFileSelected = (event) => {
  fontError.value = "";
  const file = event.target.files?.[0] || null;

  if (!file) {
    fontFile.value = null;
    selectedFontName.value = "";
    return;
  }

  const lower = file.name.toLowerCase();
  if (!lower.endsWith(".ttf")) {
    fontError.value = "Only .ttf files are allowed.";
    fontFile.value = null;
    selectedFontName.value = "";
    if (fontInputRef.value) fontInputRef.value.value = "";
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    fontError.value = "Font must be 10 MB or smaller.";
    fontFile.value = null;
    selectedFontName.value = "";
    if (fontInputRef.value) fontInputRef.value.value = "";
    return;
  }

  fontFile.value = file;
  selectedFontName.value = file.name;
};

const uploadCustomFont = async () => {
  if (!fontFile.value) {
    fontError.value = "Please choose a .ttf file first.";
    showStatus(
      "warning",
      "No font selected",
      "Please choose a .ttf file first, then click Upload Font."
    );
    return;
  }

  fontError.value = "";
  uploadingFont.value = true;

  try {
    const data = new FormData();
    data.append("font", fontFile.value);

    const res = await api.post("/home-view/font-upload", data);

    if (res.font) {
      // Keep user-typed family name if present, otherwise use server-derived name.
      if (!form.value.customFontFamily && res.font.family) {
        form.value.customFontFamily = res.font.family;
      }
      form.value.customFontUrl = res.font.url || "";
      form.value.customFontFilename = res.font.filename || "";

      await loadFontForPreview(form.value.customFontFamily || res.font.family, form.value.customFontUrl);

      fontFile.value = null;
      selectedFontName.value = "";
      if (fontInputRef.value) fontInputRef.value.value = "";

      showSuccess(
        "Font uploaded",
        "Font uploaded successfully. Click “Save Settings” to apply it to the storefront."
      );
    }
  } catch (error) {
    console.error("Failed to upload font:", error);
    fontError.value = error.message || "Failed to upload font.";
    showError("Font upload failed", fontError.value);
  } finally {
    uploadingFont.value = false;
  }
};

const doRemoveCustomFont = async () => {
  fontError.value = "";
  removingFont.value = true;

  try {
    const filename = form.value.customFontFilename || "";
    if (filename) {
      try {
        await api.delete("/home-view/font", { filename });
      } catch (err) {
        // If backend has nothing saved yet, still clear locally.
        console.warn("Font delete request failed, clearing locally:", err);
      }
    }

    form.value.customFontFamily = "";
    form.value.customFontUrl = "";
    form.value.customFontFilename = "";
    fontFile.value = null;
    selectedFontName.value = "";
    if (fontInputRef.value) fontInputRef.value.value = "";

    showSuccess("Font removed", "Custom font removed. The storefront now uses the default fonts. Don’t forget to Save Settings.");
  } catch (error) {
    console.error("Failed to remove font:", error);
    fontError.value = error.message || "Failed to remove font.";
    showError("Remove failed", fontError.value);
  } finally {
    removingFont.value = false;
  }
};

const removeCustomFont = () => {
  if (!form.value.customFontUrl && !form.value.customFontFilename) return;

  askConfirm(
    "Remove custom font?",
    "This will remove the uploaded font and restore the default storefront fonts after you save.",
    {
      confirmText: "Remove font",
      onConfirm: doRemoveCustomFont,
    }
  );
};

const doSaveSettings = async () => {
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
      customFontFamily: form.value.customFontFamily,
      customFontUrl: form.value.customFontUrl,
      customFontFilename: form.value.customFontFilename,
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
    await fetchInitialData();
    showSuccess("Settings saved", "Your homepage customization has been published successfully.");
  } catch (error) {
    console.error("Failed to save settings:", error);
    showError("Save failed", error.message || "Failed to save settings. Please try again.");
  } finally {
    saving.value = false;
  }
};

const saveSettings = () => {
  askConfirm(
    "Save settings?",
    "This will publish your homepage changes (hero, featured, typography) to the live storefront.",
    {
      confirmText: "Save settings",
      onConfirm: doSaveSettings,
    }
  );
};

const resetForm = () => {
  askConfirm(
    "Discard changes?",
    "This will reset the form to the last saved settings. Unsaved changes will be lost.",
    {
      confirmText: "Reset form",
      onConfirm: async () => {
        await fetchInitialData();
      },
    }
  );
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

.font-preview-box {
  height: 100%;
  min-height: 16rem;
  border-radius: var(--radius-lg);
  border: 1px dashed rgba(77, 16, 24, 0.28);
  background:
    radial-gradient(circle at top, rgba(254, 181, 17, 0.18), transparent 55%),
    rgba(255, 248, 228, 0.6);
  padding: 1.25rem 1.4rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: center;
}

.font-preview-box .section-kicker {
  font-size: 0.7rem;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.font-preview-big {
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1.1;
  margin-bottom: 0.6rem;
  word-break: break-word;
}

.font-preview-sample {
  font-size: 1rem;
  line-height: 1.7;
  color: rgba(77, 16, 24, 0.82);
  word-break: break-word;
}

.font-preview-meta {
  margin-top: 1rem;
  font-size: 0.75rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: rgba(77, 16, 24, 0.6);
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