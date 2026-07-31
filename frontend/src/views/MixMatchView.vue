<template>
  <div ref="mixRootRef" class="mix-match-page">
    <section class="mix-hero" data-reveal-section>
      <div class="container mix-hero-shell">
        <p class="section-kicker mix-kicker">The Look Studio</p>
        <h1>Mix &amp; Match</h1>
        <p class="mix-hero-copy">
          Compose a complete outfit in seconds. Layer tops, bottoms and outerwear, preview your
          look live, then add the entire ensemble to your bag in one tap.
        </p>
        <div class="mix-hero-actions">
          <a href="#look-studio" class="btn btn-luxury btn-lg">Start Building</a>
          <router-link to="/products" class="btn btn-outline-luxury btn-lg">Browse Collection</router-link>
        </div>
      </div>
    </section>

    <section class="mix-steps py-5" data-reveal-section>
      <div class="container">
        <div class="row g-4">
          <div class="col-md-4" v-for="(step, index) in steps" :key="step.title">
            <div class="mix-step surface h-100">
              <div class="mix-step-head">
                <span class="mix-step-num">0{{ index + 1 }}</span>
                <i :class="step.icon"></i>
              </div>
              <h3>{{ step.title }}</h3>
              <p>{{ step.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mix-presets-section py-5" data-reveal-section>
      <div class="container">
        <div class="section-title section-heading">
          <h2>Start From A Style</h2>
          <p class="text-muted">One tap applies a curated look — then refine it to taste.</p>
        </div>

        <div class="mix-preset-rail">
          <button
            v-for="preset in presets"
            :key="preset.name"
            type="button"
            class="mix-preset"
            :class="{ active: isPresetActive(preset) }"
            @click="applyPreset(preset)"
          >
            <span class="mix-preset-icon"><i :class="preset.icon"></i></span>
            <span class="mix-preset-name">{{ preset.name }}</span>
            <small>{{ preset.blurb }}</small>
          </button>
        </div>
      </div>
    </section>

    <section class="mix-saved-section py-4" data-reveal-section>
      <div class="container">
        <div class="mix-saved-head">
          <div>
            <p class="section-kicker mb-1">Your Wardrobe</p>
            <h3 class="mb-0">Saved Looks</h3>
          </div>
          <button
            v-if="authStore.isAuthenticated"
            type="button"
            class="btn btn-outline-luxury btn-sm"
            :disabled="!activeOutfitSlots.length"
            @click="saveLookOpen = !saveLookOpen"
          >
            <i class="bi bi-plus-lg me-1"></i> Save Current Look
          </button>
        </div>

        <div v-if="!authStore.isAuthenticated" class="mix-saved-empty">
          <i class="bi bi-person-lock"></i>
          <span>Sign in to keep your looks saved across devices.</span>
          <router-link to="/login" class="btn btn-outline-luxury btn-sm ms-2">Sign In</router-link>
        </div>

        <form v-else-if="saveLookOpen" class="mix-save-form" @submit.prevent="saveCurrentLook">
          <input
            v-model="lookNameInput"
            class="form-control"
            placeholder="Name this look (e.g. Autumn boardroom)"
            maxlength="120"
            required
          />
          <button type="submit" class="btn btn-luxury" :disabled="!lookNameInput.trim()">Save</button>
          <button type="button" class="btn btn-outline-luxury" @click="closeSaveForm">Cancel</button>
        </form>

        <p v-if="savedLookError" class="mix-saved-error">{{ savedLookError }}</p>

        <div v-if="authStore.isAuthenticated && savedLooksLoading" class="mix-saved-grid">
          <div v-for="n in 2" :key="n" class="mix-option mix-option--skeleton"></div>
        </div>

        <div v-else-if="authStore.isAuthenticated && savedLooks.length" class="mix-saved-grid">
          <div v-for="look in savedLooks" :key="look.id" class="mix-saved-card surface">
            <div class="mix-saved-card-main">
              <strong>{{ look.name }}</strong>
              <small>{{ look.count }} piece{{ look.count === 1 ? "" : "s" }} · ${{ look.total.toLocaleString() }}</small>
            </div>
            <div class="mix-saved-card-actions">
              <button type="button" class="btn btn-luxury btn-sm" @click="applySavedLook(look)">Load</button>
              <button
                type="button"
                class="mix-outfit-remove"
                title="Delete look"
                @click="deleteSavedLook(look)"
              >
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>
        </div>

        <p v-else-if="authStore.isAuthenticated" class="mix-saved-empty">
          <i class="bi bi-bookmark"></i>
          Compose a look, then save it here to rebuild it in one tap.
        </p>
      </div>
    </section>

    <section id="look-studio" class="mix-builder-section py-5" data-reveal-section>
      <div class="container">
        <div class="row g-4 align-items-start">
          <div class="col-xl-6 mix-stage-col">
            <div class="mix-stage surface">
              <div class="mix-stage-top">
                <div>
                  <p class="section-kicker mb-2">Live Preview</p>
                  <h3 class="mb-0">Your Look</h3>
                </div>
                <div class="mix-stage-actions">
                  <button
                    type="button"
                    class="mix-icon-btn"
                    title="Surprise me"
                    :disabled="!shownProducts.length"
                    @click="shuffleLook"
                  >
                    <i class="bi bi-shuffle"></i>
                  </button>
                  <button
                    type="button"
                    class="mix-icon-btn"
                    title="Clear look"
                    :disabled="!activeOutfitSlots.length"
                    @click="clearLook"
                  >
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>

              <div class="mix-figure">
                <div class="mix-figure-frame"></div>
                <div class="mannequin">
                  <div class="mannequin-head"></div>
                  <div class="mannequin-body"></div>
                </div>

                <div class="mix-layer mix-layer--dress" v-if="selections.dress.product">
                  <img
                    :src="selections.dress.product.image"
                    :alt="selections.dress.product.name"
                    class="mix-image"
                  />
                </div>

                <div class="mix-layer mix-layer--bottom" v-if="selections.bottom.product && !selections.dress.product">
                  <img
                    :src="selections.bottom.product.image"
                    :alt="selections.bottom.product.name"
                    class="mix-image"
                  />
                </div>

                <div class="mix-layer mix-layer--top" v-if="selections.top.product && !selections.dress.product">
                  <img
                    :src="selections.top.product.image"
                    :alt="selections.top.product.name"
                    class="mix-image"
                  />
                </div>

                <div class="mix-layer mix-layer--outer" v-if="selections.outer.product">
                  <img
                    :src="selections.outer.product.image"
                    :alt="selections.outer.product.name"
                    class="mix-image"
                  />
                </div>

                <div v-if="!activeOutfitSlots.length" class="mix-empty">
                  <i class="bi bi-person-standing"></i>
                  <p>Pick a preset or choose pieces to preview your look.</p>
                </div>
              </div>

              <div class="mix-outfit-list">
                <div v-if="!activeOutfitSlots.length" class="mix-outfit-empty">
                  <i class="bi bi-magic"></i>
                  <span>Your look starts here.</span>
                </div>

                <div
                  v-for="slot in activeOutfitSlots"
                  :key="slot.key"
                  class="mix-outfit-row"
                >
                  <div class="mix-outfit-thumb">
                    <img :src="selections[slot.key].product.image" :alt="selections[slot.key].product.name" loading="lazy" />
                  </div>
                  <div class="mix-outfit-body">
                    <div class="mix-outfit-name-row">
                      <strong>{{ selections[slot.key].product.name }}</strong>
                      <span class="mix-outfit-price">${{ selections[slot.key].product.price.toLocaleString() }}</span>
                    </div>
                    <div class="mix-outfit-opts">
                      <select
                        v-model="selections[slot.key].size"
                        class="form-select form-select-sm"
                        :aria-label="`${slot.label} size`"
                      >
                        <option v-for="size in selections[slot.key].product.sizes" :key="size" :value="size">Size {{ size }}</option>
                      </select>
                      <select
                        v-model="selections[slot.key].color"
                        class="form-select form-select-sm"
                        :aria-label="`${slot.label} colour`"
                      >
                        <option v-for="color in selections[slot.key].product.colors" :key="color" :value="color">{{ color }}</option>
                      </select>
                      <button
                        type="button"
                        class="mix-outfit-remove"
                        :title="`Remove ${slot.label}`"
                        @click="clearSlot(slot.key)"
                      >
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mix-total-row">
                <div>
                  <span class="section-kicker d-block mb-1">Look Total</span>
                  <span class="mix-total-note">{{ activeOutfitSlots.length }} piece{{ activeOutfitSlots.length === 1 ? "" : "s" }}</span>
                </div>
                <strong class="mix-total-price">${{ outfitTotal.toLocaleString() }}</strong>
              </div>

              <button
                type="button"
                class="btn btn-luxury btn-lg w-100"
                :disabled="!activeOutfitSlots.length"
                @click="addOutfitToCart"
              >
                <i class="bi bi-bag-plus me-2"></i> Add Look to Bag
              </button>
            </div>
          </div>

          <div class="col-xl-6 mix-controls-col">
            <div class="mix-controls surface">
              <div class="mix-slot-tabs" role="tablist" aria-label="Piece type">
                <button
                  v-for="slot in slotDefs"
                  :key="slot.key"
                  type="button"
                  role="tab"
                  :aria-selected="selectedSlot === slot.key"
                  class="mix-slot-tab"
                  :class="{ active: selectedSlot === slot.key, filled: selections[slot.key].product }"
                  @click="setSlot(slot.key)"
                >
                  <i :class="slot.icon"></i>
                  <span>{{ slot.label }}</span>
                </button>
              </div>

              <div class="mix-cat-pills" v-if="currentSlotDef && currentSlotDef.categories.length > 1">
                <button
                  v-for="cat in currentSlotDef.categories"
                  :key="cat"
                  type="button"
                  class="mix-cat-pill"
                  :class="{ active: slotCatFilter[selectedSlot] === cat }"
                  @click="toggleCategory(cat)"
                >
                  {{ cat }}
                </button>
              </div>

              <div v-if="productsLoading" class="mix-options mix-options--loading">
                <div v-for="n in 4" :key="n" class="mix-option mix-option--skeleton"></div>
              </div>

              <div v-else-if="currentSlotProducts.length" class="mix-options">
                <button
                  v-for="product in currentSlotProducts"
                  :key="product.id"
                  type="button"
                  class="mix-option"
                  :class="{ active: selections[selectedSlot].product?.id === product.id }"
                  @click="selectProduct(selectedSlot, product)"
                >
                  <span class="mix-option-thumb">
                    <img :src="product.image" :alt="product.name" loading="lazy" />
                  </span>
                  <span class="mix-option-info">
                    <strong>{{ product.name }}</strong>
                    <small>{{ product.category }}</small>
                    <span class="mix-option-price">${{ product.price.toLocaleString() }}</span>
                  </span>
                  <span class="mix-option-check"><i class="bi bi-check-lg"></i></span>
                </button>
              </div>

              <div v-else class="mix-empty-state">
                <i class="bi bi-search"></i>
                <p>No pieces available in this selection yet.</p>
                <router-link to="/products" class="btn btn-outline-luxury btn-sm">Browse Collection</router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mix-tips py-5" data-reveal-section>
      <div class="container">
        <div class="section-title section-heading">
          <h2>Styling Notes</h2>
          <p class="text-muted">Little rules our stylists live by.</p>
        </div>
        <div class="row g-4">
          <div class="col-md-4" v-for="tip in tips" :key="tip.title">
            <div class="mix-tip surface h-100">
              <span class="mix-tip-icon"><i :class="tip.icon"></i></span>
              <h3>{{ tip.title }}</h3>
              <p class="mb-0">{{ tip.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mix-cta py-5" data-reveal-section>
      <div class="container">
        <div class="mix-cta-panel">
          <p class="section-kicker mix-cta-kicker">The Complete Collection</p>
          <h2>Looking for the full range?</h2>
          <p>
            Every piece in the studio is available across our full collection, sized to you and
            shipped worldwide.
          </p>
          <router-link to="/products" class="btn btn-luxury btn-lg">
            Shop All Pieces <i class="bi bi-arrow-right ms-2"></i>
          </router-link>
        </div>
      </div>
    </section>

    <div
      v-if="activeOutfitSlots.length"
      class="mix-mobile-bar d-xl-none"
      aria-hidden="true"
    >
      <div class="mix-mobile-total">
        <small>Look total</small>
        <strong>${{ outfitTotal.toLocaleString() }}</strong>
      </div>
      <button type="button" class="btn btn-luxury" @click="addOutfitToCart">
        <i class="bi bi-bag-plus me-1"></i> Add to Bag
      </button>
    </div>

    <div v-if="addedModal.open" class="mix-modal-overlay" @click.self="closeAddedModal">
      <div class="mix-modal-box surface-elevated" role="dialog" aria-modal="true" aria-label="Look added to bag">
        <div class="mix-modal-icon"><i class="bi bi-bag-check"></i></div>
        <p class="section-kicker mb-2">Bag Updated</p>
        <h2>Your look is ready</h2>
        <p class="mix-modal-message">
          {{ addedModal.count }} piece{{ addedModal.count === 1 ? "" : "s" }} added to your bag.
          Continue styling or head to checkout whenever you're ready.
        </p>
        <div class="mix-modal-actions">
          <router-link to="/products" class="btn btn-outline-luxury" @click="closeAddedModal">Keep Exploring</router-link>
          <router-link to="/cart" class="btn btn-luxury" @click="closeAddedModal">View Bag</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { api } from "../lib/api";
import { useProductsStore } from "../stores/products";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const productsStore = useProductsStore();
const cartStore = useCartStore();
const authStore = useAuthStore();

const mixRootRef = ref(null);
const selectedSlot = ref("");
const slotCatFilter = reactive({});
const addedModal = reactive({ open: false, count: 0 });
const configLoading = ref(true);
let sectionObserver;

const mixConfig = ref({ slots: [], presets: [], categories: [] });
const slotDefs = computed(() => mixConfig.value.slots);
const presets = computed(() => mixConfig.value.presets);
const currentSlotDef = computed(
  () => slotDefs.value.find((slot) => slot.key === selectedSlot.value) || null,
);

const steps = [
  { icon: "bi bi-magic", title: "Pick a Style", text: "Start from a curated preset or build your look from scratch." },
  { icon: "bi bi-handbag", title: "Compose Pieces", text: "Choose a top, bottom and outer layer and preview them instantly." },
  { icon: "bi bi-bag-check", title: "Add to Bag", text: "Set your size and colour, then add the whole look in one tap." },
];

const tips = [
  { icon: "bi bi-gem", title: "Balance Proportions", text: "Pair a structured blazer with a soft knit or a slim trouser to keep the silhouette composed." },
  { icon: "bi bi-palette", title: "Anchor Your Palette", text: "Stay in one neutral base and let a single statement piece carry the colour of the look." },
  { icon: "bi bi-stack", title: "Layer Deliberately", text: "A crisp shirt under a relaxed coat reads effortless, refined and unmistakably luxurious." },
];

const selections = reactive({
  top: { product: null, size: "", color: "" },
  bottom: { product: null, size: "", color: "" },
  outer: { product: null, size: "", color: "" },
  dress: { product: null, size: "", color: "" },
});

const savedLooks = ref([]);
const savedLooksLoading = ref(false);
const saveLookOpen = ref(false);
const lookNameInput = ref("");
const savedLookError = ref("");

const productsLoading = computed(
  () => configLoading.value || (productsStore.loading && !productsStore.loaded),
);

const shownProducts = computed(() =>
  productsStore.products.filter((product) => product.isShowed !== false),
);

const productsFor = (categories) => {
  const set = new Set(categories || []);
  return shownProducts.value
    .filter((product) => set.has(product.category))
    .sort((a, b) => (b.featured ? 1 : 0) - (a.featured ? 1 : 0) || b.id - a.id);
};

const currentSlotProducts = computed(() => {
  if (!currentSlotDef.value) return [];
  const filter = slotCatFilter[selectedSlot.value];
  if (filter && filter !== "All") return productsFor([filter]);
  return productsFor(currentSlotDef.value.categories);
});

const activeOutfitSlots = computed(() =>
  slotDefs.value.filter((slot) => selections[slot.key]?.product),
);

const outfitTotal = computed(() =>
  activeOutfitSlots.value.reduce((sum, slot) => sum + (selections[slot.key].product?.price || 0), 0),
);

const setSlot = (key) => {
  selectedSlot.value = key;
  slotCatFilter[key] = "All";
};

const toggleCategory = (category) => {
  slotCatFilter[selectedSlot.value] =
    slotCatFilter[selectedSlot.value] === category ? "All" : category;
};

const resetSelection = (key) => {
  selections[key].product = null;
  selections[key].size = "";
  selections[key].color = "";
};

const clearSlot = (key) => {
  resetSelection(key);
};

const clearLook = () => {
  Object.keys(selections).forEach(resetSelection);
};

const selectProduct = (slotKey, product) => {
  const selection = selections[slotKey];
  if (selection.product?.id === product.id) {
    resetSelection(slotKey);
    return;
  }
  selection.product = product;
  selection.size = product.sizes[0] || "";
  selection.color = product.colors[0] || "";
  if (slotKey === "dress") {
    resetSelection("top");
    resetSelection("bottom");
  } else if (slotKey === "top" || slotKey === "bottom") {
    resetSelection("dress");
  }
};

const applyPreset = (preset) => {
  clearLook();
  Object.entries(preset.slots).forEach(([key, categories]) => {
    const options = productsFor(categories);
    if (options.length) selectProduct(key, options[0]);
  });
};

const isPresetActive = (preset) =>
  Object.keys(preset.slots).every((key) => selections[key].product);

const shuffleLook = () => {
  const randomItem = (list) =>
    list.length ? list[Math.floor(Math.random() * list.length)] : null;
  clearLook();

  const fullSlot = slotDefs.value.find((slot) => slot.scope === "full" || slot.key === "dress");
  const overlaySlot = slotDefs.value.find((slot) => slot.position === "overlay");
  const partSlots = slotDefs.value.filter(
    (slot) =>
      slot.position !== "overlay" &&
      !(fullSlot && slot.key === fullSlot.key),
  );

  if (fullSlot && Math.random() < 0.35) {
    const dress = randomItem(productsFor(fullSlot.categories));
    if (dress) selectProduct(fullSlot.key, dress);
    if (overlaySlot) {
      const overlay = randomItem(productsFor(overlaySlot.categories));
      if (overlay) selectProduct(overlaySlot.key, overlay);
    }
    return;
  }

  partSlots.forEach((def) => {
    const item = randomItem(productsFor(def.categories));
    if (item) selectProduct(def.key, item);
  });
  if (overlaySlot) {
    const overlay = randomItem(productsFor(overlaySlot.categories));
    if (overlay) selectProduct(overlaySlot.key, overlay);
  }
};

const addOutfitToCart = () => {
  if (!activeOutfitSlots.value.length) return;
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/mix-match" } });
    return;
  }
  activeOutfitSlots.value.forEach((slot) => {
    const selection = selections[slot.key];
    cartStore.addToCart(selection.product, selection.size, selection.color, 1);
  });
  addedModal.open = true;
  addedModal.count = activeOutfitSlots.value.length;
};

const closeAddedModal = () => { addedModal.open = false; };

const closeSaveForm = () => {
  saveLookOpen.value = false;
  lookNameInput.value = "";
  savedLookError.value = "";
};

const loadConfig = async () => {
  configLoading.value = true;
  try {
    const config = await api.get("/mix-match");
    mixConfig.value = config;
    slotDefs.value.forEach((slot) => {
      if (!selections[slot.key]) {
        selections[slot.key] = { product: null, size: "", color: "" };
      }
      slotCatFilter[slot.key] = "All";
    });
    if (!slotDefs.value.some((slot) => slot.key === selectedSlot.value)) {
      selectedSlot.value = slotDefs.value[0]?.key || "";
    }
  } catch {
    mixConfig.value = { slots: [], presets: [], categories: [] };
  } finally {
    configLoading.value = false;
  }
};

const loadSavedLooks = async () => {
  if (!authStore.isAuthenticated) return;
  savedLooksLoading.value = true;
  savedLookError.value = "";
  try {
    const { looks } = await api.get("/mix-match/looks");
    savedLooks.value = looks;
  } catch (error) {
    savedLookError.value = error.message || "Could not load your saved looks.";
  } finally {
    savedLooksLoading.value = false;
  }
};

const saveCurrentLook = async () => {
  if (!activeOutfitSlots.value.length) return;
  const name = lookNameInput.value.trim();
  if (!name) return;
  savedLookError.value = "";

  const pieces = activeOutfitSlots.value.map((slot) => {
    const selection = selections[slot.key];
    return { slot: slot.key, productId: selection.product.id, size: selection.size, color: selection.color };
  });

  try {
    await api.post("/mix-match/looks", { name, pieces });
    closeSaveForm();
    await loadSavedLooks();
  } catch (error) {
    savedLookError.value = error.message || "Could not save this look.";
  }
};

const applySavedLook = (look) => {
  clearLook();
  look.pieces.forEach((piece) => {
    const selection = selections[piece.slot];
    if (!selection) return;
    selection.product = {
      id: piece.productId,
      name: piece.name,
      category: piece.category,
      image: piece.image,
      price: piece.price,
      sizes: piece.sizes,
      colors: piece.colors,
    };
    selection.size = piece.size;
    selection.color = piece.color;
  });
  closeSaveForm();
};

const deleteSavedLook = async (look) => {
  savedLookError.value = "";
  try {
    await api.delete(`/mix-match/looks/${look.id}`);
    savedLooks.value = savedLooks.value.filter((item) => item.id !== look.id);
  } catch (error) {
    savedLookError.value = error.message || "Could not delete this look.";
  }
};

onMounted(async () => {
  await loadConfig();
  if (!productsStore.loaded) await productsStore.fetchProducts();
  if (authStore.isAuthenticated) loadSavedLooks();

  const firstPreset = presets.value.find((preset) =>
    Object.values(preset.slots).some((categories) => productsFor(categories).length),
  );
  if (firstPreset) applyPreset(firstPreset);

  await nextTick();

  const sections = mixRootRef.value?.querySelectorAll("[data-reveal-section]");
  if (!sections?.length) return;

  sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        sectionObserver?.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -8% 0px" },
  );
  sections.forEach((section) => sectionObserver.observe(section));
});

onBeforeUnmount(() => {
  sectionObserver?.disconnect();
});
</script>

<style scoped>
.mix-match-page {
  padding-bottom: 5rem;
}

/* ========== HERO ========== */
.mix-hero {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at 18% 12%, rgba(254, 181, 17, 0.3), transparent 42%),
    radial-gradient(circle at 86% 18%, rgba(108, 24, 35, 0.7), transparent 48%),
    linear-gradient(165deg, #2a080d 0%, #4d1018 55%, #6c1823 100%);
  isolation: isolate;
}

.mix-hero::before,
.mix-hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

.mix-hero::before {
  width: 26rem;
  height: 26rem;
  top: -9rem;
  right: -6rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.22), transparent 70%);
}

.mix-hero::after {
  width: 20rem;
  height: 20rem;
  bottom: -8rem;
  left: -5rem;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.14), transparent 70%);
}

.mix-hero-shell {
  position: relative;
  z-index: 1;
  min-height: min(72vh, 46rem);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: clamp(4rem, 8vw, 6rem) 1rem;
}

.mix-kicker,
.mix-hero h1,
.mix-hero-copy,
.mix-hero-actions {
  position: relative;
  z-index: 1;
  text-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}

.mix-kicker {
  color: rgba(255, 241, 184, 0.9);
  margin-bottom: 1.25rem;
}

.mix-hero h1 {
  color: var(--gold-light);
  font-size: clamp(3rem, 11vw, 6.5rem);
  letter-spacing: 0.16em;
  margin-bottom: 1.25rem;
}

.mix-hero-copy {
  max-width: 42rem;
  color: rgba(255, 241, 184, 0.86);
  font-size: clamp(1rem, 2vw, 1.15rem);
  line-height: 1.8;
  margin-bottom: 2rem;
}

.mix-hero-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* ========== REVEAL ========== */
[data-reveal-section] {
  opacity: 0;
  transform: translateY(40px);
  filter: blur(10px);
  transition:
    opacity 800ms cubic-bezier(0.22, 1, 0.36, 1),
    transform 800ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 800ms ease;
}

[data-reveal-section].is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

/* ========== STEPS ========== */
.mix-steps {
  position: relative;
}

.mix-step {
  padding: clamp(1.5rem, 3vw, 2rem);
  border-radius: var(--radius-xl);
  text-align: left;
  position: relative;
  overflow: hidden;
}

.mix-step-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.mix-step-num {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 0.95rem;
  letter-spacing: 0.3em;
  color: var(--gold-dark);
}

.mix-step-head i {
  font-size: 2rem;
  color: var(--primary-black);
  opacity: 0.85;
}

.mix-step h3 {
  font-size: clamp(1.25rem, 1.6vw, 1.5rem);
  margin-bottom: 0.6rem;
}

.mix-step p {
  color: var(--ink-soft);
  line-height: 1.7;
  font-size: 0.95rem;
  margin-bottom: 0;
}

/* ========== PRESETS ========== */
.mix-presets-section {
  position: relative;
}

.section-heading,
.section-content {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

[data-reveal-section].is-visible .section-heading {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 70ms;
}

[data-reveal-section].is-visible .mix-preset-rail {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 170ms;
}

.mix-preset-rail {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 0.25rem 0.25rem 0.75rem;
  scroll-snap-type: x proximity;
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.mix-preset {
  flex: 0 0 auto;
  scroll-snap-align: start;
  min-width: 168px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  padding: 1.25rem;
  text-align: left;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(77, 16, 24, 0.12);
  background: rgba(255, 248, 228, 0.82);
  color: inherit;
  cursor: pointer;
  transition:
    transform 220ms ease,
    border-color 220ms ease,
    box-shadow 220ms ease,
    background 220ms ease;
}

.mix-preset:hover {
  transform: translateY(-4px);
  border-color: rgba(77, 16, 24, 0.26);
  box-shadow: 0 20px 40px rgba(77, 16, 24, 0.14);
}

.mix-preset.active {
  border-color: var(--primary-black);
  background: linear-gradient(160deg, rgba(255, 248, 228, 0.96), rgba(254, 181, 17, 0.22));
  box-shadow: 0 20px 44px rgba(77, 16, 24, 0.18);
}

.mix-preset-icon {
  width: 3rem;
  height: 3rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(145deg, var(--secondary-black), var(--primary-black));
  color: var(--gold);
  font-size: 1.3rem;
  margin-bottom: 0.4rem;
}

.mix-preset-name {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 1.15rem;
  font-weight: 600;
}

.mix-preset small {
  color: var(--ink-soft);
  font-size: 0.82rem;
}

/* ========== BUILDER ========== */
.mix-builder-section {
  position: relative;
}

.mix-builder-section::before {
  content: "";
  position: absolute;
  inset: 1.25rem 2rem;
  border-radius: var(--radius-xl);
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.3), rgba(255, 241, 184, 0)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.08), transparent 42%);
  pointer-events: none;
}

.mix-stage-col,
.mix-controls-col {
  position: relative;
  z-index: 1;
}

.mix-stage,
.mix-controls {
  border-radius: var(--radius-xl);
  padding: clamp(1.4rem, 3vw, 2rem);
}

.mix-stage {
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

@media (min-width: 1200px) {
  .mix-stage {
    position: sticky;
    top: 7rem;
  }
}

.mix-stage-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.mix-stage-actions {
  display: flex;
  gap: 0.5rem;
}

.mix-icon-btn {
  width: 2.6rem;
  height: 2.6rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(77, 16, 24, 0.16);
  background: rgba(255, 248, 228, 0.7);
  color: var(--primary-black);
  font-size: 1.05rem;
  cursor: pointer;
  transition:
    transform 220ms ease,
    background 220ms ease,
    border-color 220ms ease;
}

.mix-icon-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

.mix-icon-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ========== FIGURE ========== */
.mix-figure {
  position: relative;
  min-height: 32rem;
  border-radius: 1.75rem;
  overflow: hidden;
  background:
    radial-gradient(circle at 50% 18%, rgba(255, 241, 184, 0.5), transparent 40%),
    linear-gradient(180deg, rgba(77, 16, 24, 0.14), rgba(254, 181, 17, 0.14));
  border: 1px solid rgba(77, 16, 24, 0.12);
  isolation: isolate;
}

.mix-figure-frame {
  position: absolute;
  inset: 1.5rem;
  border-radius: 999px 999px 2.5rem 2.5rem;
  background: linear-gradient(180deg, rgba(255, 241, 184, 0.2), rgba(77, 16, 24, 0.05));
  border: 1px solid rgba(77, 16, 24, 0.08);
  z-index: 0;
}

.mannequin {
  position: absolute;
  left: 50%;
  bottom: 1rem;
  transform: translateX(-50%);
  width: 36%;
  height: 88%;
  z-index: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  opacity: 0.5;
}

.mannequin-head {
  width: 3.4rem;
  height: 4.2rem;
  border-radius: 999px 999px 3.5rem 3.5rem;
  background:
    radial-gradient(circle at 42% 30%, rgba(255, 241, 184, 0.55), transparent 42%),
    linear-gradient(180deg, rgba(77, 16, 24, 0.12), rgba(77, 16, 24, 0.24));
  border: 1px solid rgba(77, 16, 24, 0.2);
  margin-bottom: -1rem;
}

.mannequin-body {
  width: 92%;
  flex: 1;
  border-radius: 7rem 7rem 3.5rem 3.5rem;
  background:
    radial-gradient(circle at 50% 16%, rgba(255, 241, 184, 0.4), transparent 46%),
    linear-gradient(180deg, rgba(77, 16, 24, 0.1), rgba(77, 16, 24, 0.26));
  border: 1px solid rgba(77, 16, 24, 0.2);
  clip-path: polygon(28% 0, 72% 0, 100% 18%, 100% 100%, 0 100%, 0 18%);
}

.mix-layer {
  position: absolute;
  inset: 0;
  z-index: 1;
  transition: opacity 260ms ease;
}

.mix-layer--dress {
  clip-path: inset(0 0 0 0 round 1.75rem);
}

.mix-layer--bottom {
  clip-path: inset(32% 0 0 0 round 1.75rem);
}

.mix-layer--top {
  clip-path: inset(0 0 42% 0 round 1.75rem);
}

.mix-layer--outer {
  clip-path: inset(0 0 10% 0 round 1.75rem);
  z-index: 2;
}

.mix-layer--outer ~ .mix-layer--dress {
  z-index: 1;
}

.mix-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
}

.mix-empty {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  text-align: center;
  padding: 1.5rem;
  color: rgba(77, 16, 24, 0.56);
}

.mix-empty i {
  font-size: 3rem;
  opacity: 0.7;
}

.mix-empty p {
  max-width: 18rem;
  font-size: 0.92rem;
  letter-spacing: 0.05em;
  margin-bottom: 0;
}

/* ========== OUTFIT LIST ========== */
.mix-outfit-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.mix-outfit-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 1.1rem;
  border-radius: var(--radius-md);
  border: 1px dashed rgba(77, 16, 24, 0.24);
  color: var(--ink-soft);
  font-size: 0.92rem;
}

.mix-outfit-row {
  display: flex;
  gap: 1rem;
  padding: 0.9rem;
  border-radius: var(--radius-lg);
  background: rgba(255, 248, 228, 0.62);
  border: 1px solid rgba(77, 16, 24, 0.1);
}

.mix-outfit-thumb {
  flex-shrink: 0;
  width: 5rem;
  height: 5rem;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.62));
}

.mix-outfit-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.mix-outfit-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.mix-outfit-name-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
}

.mix-outfit-name-row strong {
  font-size: 0.98rem;
  line-height: 1.35;
}

.mix-outfit-price {
  font-family: "Playfair Display", Georgia, serif;
  font-weight: 700;
  font-size: 1.02rem;
  white-space: nowrap;
}

.mix-outfit-opts {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.mix-outfit-opts .form-select {
  width: auto;
  min-width: 6.5rem;
  font-size: 0.8rem;
}

.mix-outfit-remove {
  width: 2rem;
  height: 2rem;
  display: grid;
  place-items: center;
  margin-left: auto;
  border-radius: 50%;
  border: 1px solid rgba(77, 16, 24, 0.16);
  background: transparent;
  color: var(--primary-black);
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 200ms ease, color 200ms ease, border-color 200ms ease;
}

.mix-outfit-remove:hover {
  background: rgba(194, 37, 59, 0.1);
  color: var(--error);
  border-color: rgba(194, 37, 59, 0.3);
}

/* ========== TOTAL ========== */
.mix-total-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  padding-top: 1.1rem;
  border-top: 1px solid rgba(77, 16, 24, 0.12);
}

.mix-total-note {
  color: var(--ink-soft);
  font-size: 0.85rem;
}

.mix-total-price {
  font-family: "Playfair Display", Georgia, serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  line-height: 1;
}

/* ========== CONTROLS ========== */
.mix-controls {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.mix-slot-tabs {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.5rem;
  padding: 0.35rem;
  border-radius: var(--radius-lg);
  background: rgba(77, 16, 24, 0.06);
}

.mix-slot-tab {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  padding: 0.7rem 0.35rem;
  border: 0;
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--ink-soft);
  font-size: 0.68rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  font-weight: 600;
  cursor: pointer;
  transition: background 220ms ease, color 220ms ease, box-shadow 220ms ease;
}

.mix-slot-tab i {
  font-size: 1.15rem;
}

.mix-slot-tab:hover {
  color: var(--primary-black);
}

.mix-slot-tab.active {
  background: linear-gradient(180deg, var(--secondary-black), var(--primary-black));
  color: var(--gold);
  box-shadow: 0 12px 24px rgba(77, 16, 24, 0.22);
}

.mix-slot-tab.filled:not(.active)::after {
  content: "";
  position: absolute;
  top: 0.4rem;
  right: 0.5rem;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 0 2px rgba(255, 248, 228, 0.9);
}

.mix-cat-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.mix-cat-pill {
  padding: 0.5rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(77, 16, 24, 0.2);
  background: rgba(255, 248, 228, 0.7);
  color: var(--primary-black);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 600;
  cursor: pointer;
  transition: background 200ms ease, color 200ms ease, border-color 200ms ease;
}

.mix-cat-pill:hover {
  border-color: rgba(77, 16, 24, 0.38);
}

.mix-cat-pill.active {
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

/* ========== OPTIONS ========== */
.mix-options {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  max-height: 34rem;
  overflow-y: auto;
  padding-right: 0.25rem;
}

.mix-options::-webkit-scrollbar {
  width: 6px;
}

.mix-option {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
  padding: 0.8rem;
  text-align: left;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(77, 16, 24, 0.1);
  background: rgba(255, 248, 228, 0.55);
  color: inherit;
  cursor: pointer;
  transition:
    transform 220ms ease,
    border-color 220ms ease,
    box-shadow 220ms ease,
    background 220ms ease;
}

.mix-option:hover {
  transform: translateY(-2px);
  border-color: rgba(77, 16, 24, 0.26);
  background: rgba(255, 248, 228, 0.88);
  box-shadow: 0 16px 28px rgba(77, 16, 24, 0.1);
}

.mix-option.active {
  border-color: var(--primary-black);
  background: linear-gradient(160deg, rgba(255, 248, 228, 0.96), rgba(254, 181, 17, 0.18));
  box-shadow: 0 16px 30px rgba(77, 16, 24, 0.16);
}

.mix-option-thumb {
  flex-shrink: 0;
  width: 4.4rem;
  height: 4.4rem;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.62));
}

.mix-option-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.mix-option-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.mix-option-info strong {
  font-size: 0.95rem;
  line-height: 1.3;
}

.mix-option-info small {
  color: var(--ink-soft);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
}

.mix-option-price {
  font-family: "Playfair Display", Georgia, serif;
  font-weight: 700;
  font-size: 0.98rem;
}

.mix-option-check {
  flex-shrink: 0;
  width: 1.7rem;
  height: 1.7rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(77, 16, 24, 0.18);
  color: transparent;
  font-size: 0.85rem;
  transition: background 200ms ease, color 200ms ease, border-color 200ms ease;
}

.mix-option.active .mix-option-check {
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

.mix-option--skeleton {
  height: 6rem;
  background: linear-gradient(100deg, rgba(255, 248, 228, 0.4) 20%, rgba(255, 248, 228, 0.9) 40%, rgba(255, 248, 228, 0.4) 60%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.3s ease-in-out infinite;
}

@keyframes skeleton-shimmer {
  from { background-position: 120% 0; }
  to { background-position: -120% 0; }
}

.mix-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  text-align: center;
  color: var(--ink-soft);
  border-radius: var(--radius-lg);
  border: 1px dashed rgba(77, 16, 24, 0.24);
}

.mix-empty-state i {
  font-size: 2.4rem;
  opacity: 0.6;
}

.mix-empty-state p {
  margin-bottom: 0.25rem;
}

/* ========== SAVED LOOKS ========== */
.mix-saved-section {
  position: relative;
}

.mix-saved-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.mix-save-form {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.mix-save-form .form-control {
  flex: 1 1 16rem;
}

.mix-saved-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
  gap: 0.85rem;
}

.mix-saved-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
  border-radius: var(--radius-lg);
}

.mix-saved-card-main {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.mix-saved-card-main strong {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 1.05rem;
}

.mix-saved-card-main small {
  color: var(--ink-soft);
  font-size: 0.82rem;
}

.mix-saved-card-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.mix-saved-error {
  color: var(--error);
  font-size: 0.85rem;
  margin: 0.25rem 0 0.75rem;
}

.mix-saved-empty {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem;
  padding: 1.1rem 1.25rem;
  border-radius: var(--radius-md);
  border: 1px dashed rgba(77, 16, 24, 0.24);
  color: var(--ink-soft);
  font-size: 0.92rem;
}

.mix-saved-empty i {
  font-size: 1.1rem;
  opacity: 0.7;
}

/* ========== TIPS ========== */
.mix-tip {
  padding: clamp(1.5rem, 3vw, 2rem);
  border-radius: var(--radius-xl);
  text-align: center;
}

.mix-tip-icon {
  width: 3.5rem;
  height: 3.5rem;
  display: grid;
  place-items: center;
  margin: 0 auto 1.1rem;
  border-radius: 50%;
  background: linear-gradient(145deg, var(--secondary-black), var(--primary-black));
  color: var(--gold);
  font-size: 1.4rem;
  box-shadow: 0 14px 28px rgba(77, 16, 24, 0.2);
}

.mix-tip h3 {
  font-size: clamp(1.2rem, 1.6vw, 1.45rem);
  margin-bottom: 0.6rem;
}

.mix-tip p {
  color: var(--ink-soft);
  line-height: 1.7;
  font-size: 0.95rem;
}

/* ========== CTA ========== */
.mix-cta-panel {
  position: relative;
  overflow: hidden;
  padding: clamp(2.5rem, 6vw, 4.5rem);
  border-radius: var(--radius-xl);
  text-align: center;
  background:
    radial-gradient(circle at 15% 20%, rgba(254, 181, 17, 0.26), transparent 40%),
    radial-gradient(circle at 88% 80%, rgba(254, 181, 17, 0.18), transparent 40%),
    linear-gradient(160deg, #2a080d, #4d1018 60%, #6c1823);
  border: 1px solid rgba(254, 181, 17, 0.2);
  box-shadow: var(--shadow-xl);
}

.mix-cta-kicker {
  color: rgba(255, 241, 184, 0.85);
  margin-bottom: 1rem;
}

.mix-cta-panel h2 {
  color: var(--gold-light);
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  margin-bottom: 1rem;
}

.mix-cta-panel p {
  max-width: 34rem;
  margin: 0 auto 1.75rem;
  color: rgba(255, 241, 184, 0.8);
  line-height: 1.7;
}

/* ========== MOBILE BAR ========== */
.mix-mobile-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1040;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.8rem 1rem calc(0.8rem + env(safe-area-inset-bottom));
  background: rgba(255, 248, 228, 0.96);
  backdrop-filter: blur(18px);
  border-top: 1px solid rgba(77, 16, 24, 0.14);
  box-shadow: 0 -12px 32px rgba(77, 16, 24, 0.14);
  animation: mobile-bar-in 320ms cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes mobile-bar-in {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.mix-mobile-total {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.mix-mobile-total small {
  color: var(--ink-soft);
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.mix-mobile-total strong {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 1.35rem;
}

/* ========== MODAL ========== */
.mix-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.48), rgba(20, 10, 12, 0.62)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.14), transparent 38%);
  backdrop-filter: blur(6px);
}

.mix-modal-box {
  width: min(100%, 480px);
  padding: 2rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-lg);
  text-align: center;
  animation: modalIn 0.25s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.mix-modal-icon {
  width: 4.25rem;
  height: 4.25rem;
  margin: 0 auto 1rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(254, 181, 17, 0.18), rgba(77, 16, 24, 0.1));
  color: var(--gold-dark);
  font-size: 1.75rem;
}

.mix-modal-box h2 {
  margin-bottom: 0.75rem;
  font-size: clamp(1.45rem, 2vw, 1.8rem);
}

.mix-modal-message {
  max-width: 28rem;
  margin: 0 auto;
  color: var(--ink-soft);
  line-height: 1.6;
}

.mix-modal-actions {
  margin-top: 1.75rem;
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 991.98px) {
  .mix-hero-shell {
    min-height: 32rem;
  }

  .mix-figure {
    min-height: 28rem;
  }
}

@media (max-width: 767.98px) {
  .mix-hero-actions {
    flex-direction: column;
    width: 100%;
  }

  .mix-hero-actions .btn {
    width: 100%;
  }

  .mix-hero h1 {
    letter-spacing: 0.1em;
  }

  .mix-figure {
    min-height: 24rem;
  }

  .mix-builder-section::before {
    inset: 0.75rem;
  }

  .mix-outfit-opts .form-select {
    min-width: 0;
    flex: 1 1 5.5rem;
  }

  .mix-option-thumb {
    width: 3.9rem;
    height: 3.9rem;
  }

  .mix-slot-tab {
    font-size: 0.6rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  [data-reveal-section],
  .section-heading,
  .mix-preset-rail,
  .mix-option,
  .mix-preset,
  .mix-step,
  .mix-tip,
  .mix-icon-btn {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }
}
</style>
