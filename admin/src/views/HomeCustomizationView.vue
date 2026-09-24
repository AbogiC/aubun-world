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

      <!-- Theme Colors -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h5 mb-0">Theme Colors</h2>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge text-bg-light border">Live storefront palette</span>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="resetThemeToDefaults"
              >
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to defaults
              </button>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">
              Pick the primary, secondary and universal accent colors for the whole storefront.
              Leave a field empty to use the default. Save settings to publish live.
            </p>

            <div class="row g-3 align-items-stretch">
              <div class="col-lg-7">
                <div class="theme-grid">
                  <div
                    v-for="field in themeFields"
                    :key="field.key"
                    class="theme-field"
                  >
                    <label class="theme-swatch" :style="{ background: form[field.key] || field.default }" :title="field.label">
                      <input
                        type="color"
                        :value="form[field.key] || field.default"
                        @input="form[field.key] = ($event.target.value || '').toLowerCase()"
                        :aria-label="field.label"
                      />
                      <span class="theme-swatch-ring" aria-hidden="true"></span>
                    </label>
                    <div class="theme-field-body">
                      <label class="form-label mb-1">{{ field.label }}</label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">#</span>
                        <input
                          type="text"
                          class="form-control font-monospace"
                          :value="(form[field.key] || '').replace(/^#/, '')"
                          @input="onThemeHexInput(field.key, $event)"
                          maxlength="6"
                          spellcheck="false"
                          :placeholder="field.default.replace('#', '')"
                        />
                      </div>
                      <small class="text-muted">{{ field.hint }}</small>
                    </div>
                    <button
                      type="button"
                      class="btn btn-sm btn-link text-muted theme-default-btn"
                      @click="form[field.key] = ''"
                      title="Use default color"
                    >
                      Default
                    </button>
                  </div>
                </div>

                <div v-if="themeError" class="alert alert-danger mt-3 mb-0 py-2 px-3">
                  <i class="bi bi-exclamation-triangle me-2"></i>{{ themeError }}
                </div>
              </div>

              <div class="col-lg-5">
                <div class="theme-preview" :style="themePreviewVars">
                  <p class="theme-preview-kicker">Aubun World · Live Preview</p>
                  <h3 class="theme-preview-title">Luxury, recolored live</h3>
                  <p class="theme-preview-copy">
                    Buttons, badges and surfaces below reflect your palette instantly.
                  </p>
                  <div class="theme-preview-gradient">
                    <span>Primary → Secondary</span>
                  </div>
                  <div class="theme-preview-actions">
                    <span class="theme-preview-btn-primary">Shop Collection</span>
                    <span class="theme-preview-btn-gold">Try Mix &amp; Match</span>
                  </div>
                  <div class="theme-preview-badges">
                    <span class="theme-preview-badge-light">Gold Light</span>
                    <span class="theme-preview-badge-cream">Cream Surface</span>
                    <span class="theme-preview-badge-dark">Gold Accent</span>
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

const DEFAULT_THEME = Object.freeze({
  themePrimary: "#4d1018",
  themeSecondary: "#6c1823",
  themeGold: "#feb511",
  themeGoldLight: "#fff1b8",
  themeGoldDark: "#c48d0c",
  themeCream: "#fef8e4",
  themeInkMuted: "#8c5a14",
});

const themeFields = Object.freeze([
  { key: "themePrimary", label: "Primary", default: DEFAULT_THEME.themePrimary, hint: "Buttons, header, footer" },
  { key: "themeSecondary", label: "Secondary", default: DEFAULT_THEME.themeSecondary, hint: "Gradients, hovers" },
  { key: "themeGold", label: "Gold accent", default: DEFAULT_THEME.themeGold, hint: "Badges, CTAs, highlights" },
  { key: "themeGoldLight", label: "Gold light", default: DEFAULT_THEME.themeGoldLight, hint: "Light surfaces, glow" },
  { key: "themeGoldDark", label: "Gold dark", default: DEFAULT_THEME.themeGoldDark, hint: "Borders, deep accents" },
  { key: "themeCream", label: "Cream surface", default: DEFAULT_THEME.themeCream, hint: "Cards, backgrounds" },
  { key: "themeInkMuted", label: "Muted ink", default: DEFAULT_THEME.themeInkMuted, hint: "Kickers, subtle text" },
]);

const themeError = ref("");

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
  themePrimary: "",
  themeSecondary: "",
  themeGold: "",
  themeGoldLight: "",
  themeGoldDark: "",
  themeCream: "",
  themeInkMuted: "",
});

const themeValueOrDefault = (key) => form.value[key] || DEFAULT_THEME[key];

const themePreviewVars = computed(() => ({
  "--pv-primary": themeValueOrDefault("themePrimary"),
  "--pv-secondary": themeValueOrDefault("themeSecondary"),
  "--pv-gold": themeValueOrDefault("themeGold"),
  "--pv-gold-light": themeValueOrDefault("themeGoldLight"),
  "--pv-gold-dark": themeValueOrDefault("themeGoldDark"),
  "--pv-cream": themeValueOrDefault("themeCream"),
  "--pv-ink-muted": themeValueOrDefault("themeInkMuted"),
}));

const isValidHexField = (value) => {
  if (!value) return true;
  return /^#[0-9a-f]{6}$/i.test(value);
};

const onThemeHexInput = (key, event) => {
  themeError.value = "";
  const raw = (event.target.value || "").replace(/[^0-9a-fA-F]/g, "").slice(0, 6);
  event.target.value = raw;
  form.value[key] = raw ? `#${raw.toLowerCase()}` : "";
};

const resetThemeToDefaults = () => {
  themeError.value = "";
  for (const field of themeFields) {
    form.value[field.key] = field.default;
  }
};

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
      form.value.themePrimary = s.themePrimary || "";
      form.value.themeSecondary = s.themeSecondary || "";
      form.value.themeGold = s.themeGold || "";
      form.value.themeGoldLight = s.themeGoldLight || "";
      form.value.themeGoldDark = s.themeGoldDark || "";
      form.value.themeCream = s.themeCream || "";
      form.value.themeInkMuted = s.themeInkMuted || "";
      themeError.value = "";
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
  const badField = themeFields.find((f) => !isValidHexField(form.value[f.key]));
  if (badField) {
    themeError.value = `${badField.label} must be a valid hex color (e.g. ${badField.default}). Leave it empty to use the default.`;
    showError("Invalid theme color", themeError.value);
    return;
  }
  themeError.value = "";

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
      themePrimary: form.value.themePrimary,
      themeSecondary: form.value.themeSecondary,
      themeGold: form.value.themeGold,
      themeGoldLight: form.value.themeGoldLight,
      themeGoldDark: form.value.themeGoldDark,
      themeCream: form.value.themeCream,
      themeInkMuted: form.value.themeInkMuted,
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
    showSuccess("Settings saved", "Your homepage customization (content, font and theme) has been published successfully.");
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
    "This will publish your homepage changes (hero, featured, typography and theme) to the live storefront.",
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

.theme-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.theme-field {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.55);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.theme-field:hover {
  border-color: rgba(77, 16, 24, 0.24);
  box-shadow: 0 4px 12px rgba(77, 16, 24, 0.08);
}

.theme-swatch {
  position: relative;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.9rem;
  flex-shrink: 0;
  cursor: pointer;
  overflow: hidden;
  border: 1px solid rgba(77, 16, 24, 0.2);
  box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.55);
  margin-bottom: 0;
}

.theme-swatch input[type="color"] {
  position: absolute;
  inset: -0.5rem;
  width: calc(100% + 1rem);
  height: calc(100% + 1rem);
  border: none;
  padding: 0;
  background: none;
  cursor: pointer;
}

.theme-swatch-ring {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  border: 1px solid rgba(255, 255, 255, 0.35);
  pointer-events: none;
}

.theme-field-body {
  flex: 1;
  min-width: 0;
}

.theme-field-body .form-label {
  font-size: 0.85rem;
  font-weight: 600;
}

.theme-default-btn {
  padding: 0.1rem 0.25rem;
  font-size: 0.72rem;
  text-decoration: none;
  flex-shrink: 0;
}

.theme-default-btn:hover {
  color: var(--primary-black) !important;
}

.font-monospace {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  text-transform: lowercase;
}

.theme-preview {
  height: 100%;
  min-height: 22rem;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  text-align: center;
  color: var(--pv-cream);
  background:
    radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.14), transparent 55%),
    linear-gradient(160deg, var(--pv-primary), var(--pv-secondary));
  border: 1px solid color-mix(in srgb, var(--pv-gold) 45%, transparent);
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.7rem;
  overflow: hidden;
}

.theme-preview-kicker {
  font-size: 0.68rem;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--pv-gold);
  margin-bottom: 0;
  font-weight: 600;
}

.theme-preview-title {
  font-size: clamp(1.5rem, 2.6vw, 2rem);
  color: var(--pv-gold-light);
  margin-bottom: 0;
  text-wrap: balance;
}

.theme-preview-copy {
  font-size: 0.9rem;
  line-height: 1.7;
  color: color-mix(in srgb, var(--pv-cream) 82%, transparent);
  margin-bottom: 0;
}

.theme-preview-gradient {
  border-radius: 999px;
  padding: 0.45rem 1rem;
  font-size: 0.7rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  background: color-mix(in srgb, var(--pv-gold-light) 16%, transparent);
  border: 1px solid color-mix(in srgb, var(--pv-gold) 45%, transparent);
  color: var(--pv-gold-light);
}

.theme-preview-actions {
  display: flex;
  gap: 0.6rem;
  justify-content: center;
  flex-wrap: wrap;
}

.theme-preview-btn-primary,
.theme-preview-btn-gold {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 1.2rem;
  border-radius: 0.75rem;
  font-size: 0.7rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  font-weight: 700;
}

.theme-preview-btn-primary {
  background: var(--pv-gold);
  color: var(--pv-primary);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28);
}

.theme-preview-btn-gold {
  background: transparent;
  color: var(--pv-gold-light);
  border: 1px solid var(--pv-gold);
}

.theme-preview-badges {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  flex-wrap: wrap;
}

.theme-preview-badge-light,
.theme-preview-badge-cream,
.theme-preview-badge-dark {
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  font-size: 0.68rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 600;
}

.theme-preview-badge-light {
  background: var(--pv-gold-light);
  color: var(--pv-gold-dark);
}

.theme-preview-badge-cream {
  background: var(--pv-cream);
  color: var(--pv-primary);
}

.theme-preview-badge-dark {
  background: var(--pv-primary);
  color: var(--pv-gold);
  border: 1px solid var(--pv-gold-dark);
}

@media (max-width: 991.98px) {
  .theme-grid {
    grid-template-columns: 1fr;
  }
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