<template>
  <div ref="homeRootRef" class="home">
    <section class="home-hero" :style="heroBackgroundStyle" data-reveal-section>
      <div class="container hero-shell">
        <p class="section-kicker hero-kicker">Luxury Everyday Wear</p>
        <h1>AUBUN WORLD</h1>
        <p class="hero-copy">
          A sharper first impression for the brand, with refined essentials and elevated layering
          designed for everyday styling.
        </p>
        <div class="hero-actions">
          <router-link to="/products" class="btn btn-luxury btn-lg">Shop Collection</router-link>
          <a href="#mix-match" class="btn btn-outline-luxury btn-lg">Try Mix & Match</a>
        </div>
      </div>
    </section>

    <section class="home-section featured-section py-5" data-reveal-section>
      <div class="container section-shell">
        <div class="section-title section-heading">
          <h2>Featured</h2>
          <p class="text-muted">Curated categories for effortless browsing.</p>
        </div>

        <div class="row g-4 section-content">
          <div v-for="item in featuredCollections" :key="item.label" class="col-md-6 col-xl-4">
            <article class="feature-card surface h-100" @click="navigateToCategory(item.routeCategory)">
              <div class="feature-image-wrap">
                <img
                  v-if="item.product?.image"
                  :src="item.product.image"
                  :alt="item.label"
                  class="feature-image"
                  loading="lazy"
                />
                <div v-else class="feature-image feature-image-placeholder">
                  <span>{{ item.label }}</span>
                </div>
                <span class="feature-badge">{{ item.label }}</span>
              </div>
              <div class="feature-body">
                <p class="section-kicker mb-2">{{ item.eyebrow }}</p>
                <h3>{{ item.title }}</h3>
                <p class="mb-0">{{ item.description }}</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section id="mix-match" class="home-section mix-match-section py-5" data-reveal-section>
      <div class="container section-shell">
        <div class="section-title section-heading">
          <h2>Mix & Match</h2>
          <p class="text-muted">Build your look with our curated selection.</p>
        </div>

        <div class="row g-4 align-items-stretch section-content">
          <div class="col-lg-6">
            <div class="mix-stage surface h-100">
              <div class="mix-stage-header">
                <p class="section-kicker mb-2">Preview</p>
                <h3 class="mb-0">Build a look</h3>
              </div>

              <div class="mix-figure">
                <div class="mix-layer mix-layer-upper">
                  <img
                    v-if="selectedUpper?.image"
                    :src="selectedUpper.image"
                    :alt="selectedUpper.name"
                    class="mix-image"
                  />
                  <div v-else class="mix-empty">Choose upper wear</div>
                </div>
                <div class="mix-layer mix-layer-lower">
                  <img
                    v-if="selectedLower?.image"
                    :src="selectedLower.image"
                    :alt="selectedLower.name"
                    class="mix-image"
                  />
                  <div v-else class="mix-empty">Choose lower wear</div>
                </div>
                <div class="mix-divider"></div>
              </div>

              <div class="mix-summary">
                <div>
                  <span class="section-kicker d-block mb-1">Upper</span>
                  <strong>{{ selectedUpper?.name || "No selection" }}</strong>
                </div>
                <div>
                  <span class="section-kicker d-block mb-1">Lower</span>
                  <strong>{{ selectedLower?.name || "No selection" }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="mix-controls surface h-100">
              <div class="mix-control-block">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                  <div>
                    <p class="section-kicker mb-2">Upper Wear</p>
                    <h3 class="mb-0">Outers & T-Shirts</h3>
                  </div>
                </div>

                <div class="mix-option-list">
                  <button
                    v-for="product in upperOptions"
                    :key="product.id"
                    type="button"
                    class="mix-option"
                    :class="{ active: product.id === selectedUpperId }"
                    @click="selectedUpperId = product.id"
                  >
                    <img :src="product.image" :alt="product.name" class="mix-option-thumb" loading="lazy" />
                    <span>
                      <strong>{{ product.name }}</strong>
                      <small>{{ product.category }}</small>
                    </span>
                  </button>
                </div>
              </div>

              <div class="mix-control-block">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                  <div>
                    <p class="section-kicker mb-2">Lower Wear</p>
                    <h3 class="mb-0">Pants</h3>
                  </div>
                </div>

                <div class="mix-option-list">
                  <button
                    v-for="product in lowerOptions"
                    :key="product.id"
                    type="button"
                    class="mix-option"
                    :class="{ active: product.id === selectedLowerId }"
                    @click="selectedLowerId = product.id"
                  >
                    <img :src="product.image" :alt="product.name" class="mix-option-thumb" loading="lazy" />
                    <span>
                      <strong>{{ product.name }}</strong>
                      <small>{{ product.category }}</small>
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useProductsStore } from "../stores/products";

const router = useRouter();
const homeRootRef = ref(null);
const productsStore = useProductsStore();
const selectedUpperId = ref(null);
const selectedLowerId = ref(null);
let sectionObserver;
const heroBackgroundImage =
  "https://wwd.com/wp-content/uploads/2024/12/GettyImages1735100420.jpg?w=910&h=511&crop=1";

const upperCategoryNames = ["Outerwear", "Shirts"];
const lowerCategoryNames = ["Pants"];

const featuredCollections = computed(() => [
  {
    label: "Pants",
    routeCategory: "Pants",
    title: "Tailored Pants",
    eyebrow: "Featured Essential",
    description: "Clean structure and versatile cuts for everyday styling.",
    product: productsStore.products.find((product) => product.category === "Pants"),
  },
  {
    label: "Outers",
    routeCategory: "Outerwear",
    title: "Statement Outers",
    eyebrow: "Layering Focus",
    description: "Outer layers that keep the silhouette polished and confident.",
    product: productsStore.products.find((product) => product.category === "Outerwear"),
  },
  {
    label: "T-Shirts",
    routeCategory: "Shirts",
    title: "Premium T-Shirts",
    eyebrow: "Daily Base Layer",
    description: "Simple foundations that are easy to pair into a complete look.",
    product: productsStore.products.find((product) => product.category === "Shirts"),
  },
]);

const upperOptions = computed(() =>
  productsStore.products.filter((product) => upperCategoryNames.includes(product.category)),
);

const lowerOptions = computed(() =>
  productsStore.products.filter((product) => lowerCategoryNames.includes(product.category)),
);

const selectedUpper = computed(() =>
  upperOptions.value.find((product) => product.id === selectedUpperId.value) || upperOptions.value[0] || null,
);

const selectedLower = computed(() =>
  lowerOptions.value.find((product) => product.id === selectedLowerId.value) || lowerOptions.value[0] || null,
);

const navigateToCategory = (category) => {
  router.push(`/products?category=${category}`);
};

const heroBackgroundStyle = computed(() => ({
  backgroundImage: `linear-gradient(180deg, rgba(77, 16, 24, 0.56), rgba(77, 16, 24, 0.68)), url("${heroBackgroundImage}")`,
}));

watch(
  upperOptions,
  (products) => {
    if (!products.length) { selectedUpperId.value = null; return; }
    if (!products.some((product) => product.id === selectedUpperId.value)) {
      selectedUpperId.value = products[0].id;
    }
  },
  { immediate: true },
);

watch(
  lowerOptions,
  (products) => {
    if (!products.length) { selectedLowerId.value = null; return; }
    if (!products.some((product) => product.id === selectedLowerId.value)) {
      selectedLowerId.value = products[0].id;
    }
  },
  { immediate: true },
);

onMounted(() => {
  const sections = homeRootRef.value?.querySelectorAll("[data-reveal-section]");
  if (!sections?.length) return;

  sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        sectionObserver?.unobserve(entry.target);
      });
    },
    { threshold: 0.18, rootMargin: "0px 0px -10% 0px" },
  );

  sections.forEach((section) => { sectionObserver.observe(section); });
});

onBeforeUnmount(() => {
  sectionObserver?.disconnect();
});
</script>

<style scoped>
.home {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.2), transparent 28%),
    linear-gradient(180deg, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.62));
}

.home-hero,
.home-section {
  position: relative;
  opacity: 0;
  transform: translateY(44px);
  filter: blur(10px);
  transition:
    opacity 760ms cubic-bezier(0.22, 1, 0.36, 1),
    transform 760ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 760ms ease;
}

.home-hero.is-visible,
.home-section.is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.home-hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 6rem 0 4rem;
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  isolation: isolate;
}

.hero-shell,
.section-shell {
  position: relative;
  z-index: 1;
}

.hero-shell {
  position: relative;
  min-height: min(78vh, 52rem);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: clamp(2.5rem, 6vw, 5rem) 1rem;
}

.home-hero::before,
.home-hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

.home-hero::before {
  width: 22rem;
  height: 22rem;
  top: -7rem;
  right: -5rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.3), transparent 72%);
}

.home-hero::after {
  width: 18rem;
  height: 18rem;
  bottom: -6rem;
  left: -3rem;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.16), transparent 72%);
}

.hero-kicker,
.hero-shell h1,
.hero-copy,
.hero-actions {
  position: relative;
  z-index: 1;
  text-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
}

.hero-kicker {
  color: rgba(255, 241, 184, 0.88);
  margin-bottom: 1.5rem;
}

.hero-shell h1 {
  color: var(--gold-light);
  font-size: clamp(3.25rem, 12vw, 7.5rem);
  letter-spacing: 0.18em;
  margin-bottom: 1.5rem;
}

.hero-copy {
  max-width: 42rem;
  color: rgba(255, 241, 184, 0.84);
  font-size: clamp(1rem, 2vw, 1.2rem);
  line-height: 1.8;
  margin-bottom: 2rem;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
}

.section-heading,
.section-content {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.home-section.is-visible .section-heading {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 70ms;
}

.home-section.is-visible .section-content {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 170ms;
}

.featured-section::before,
.mix-match-section::before {
  content: "";
  position: absolute;
  inset: 1.25rem 2rem;
  border-radius: var(--radius-xl);
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.3), rgba(255, 241, 184, 0)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.08), transparent 42%);
  pointer-events: none;
}

.feature-card {
  cursor: pointer;
  overflow: hidden;
  border-radius: var(--radius-xl);
  border: 1px solid rgba(77, 16, 24, 0.12);
  transition:
    transform 240ms ease,
    box-shadow 240ms ease,
    border-color 240ms ease;
}

.feature-card:hover {
  transform: translateY(-8px);
  border-color: rgba(77, 16, 24, 0.24);
  box-shadow: 0 30px 60px rgba(77, 16, 24, 0.15);
}

.feature-image-wrap {
  position: relative;
  height: 22rem;
  overflow: hidden;
  background: linear-gradient(180deg, rgba(77, 16, 24, 0.94), rgba(254, 181, 17, 0.6));
}

.feature-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 380ms ease;
}

.feature-card:hover .feature-image {
  transform: scale(1.04);
}

.feature-image-placeholder {
  display: grid;
  place-items: center;
  color: rgba(255, 241, 184, 0.86);
  font-size: 1.35rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.feature-badge {
  position: absolute;
  top: 1.25rem;
  left: 1.25rem;
  padding: 0.55rem 0.95rem;
  border-radius: 999px;
  background: rgba(77, 16, 24, 0.86);
  color: var(--gold-light);
  font-size: 0.76rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.feature-body {
  padding: 1.5rem;
}

.feature-body h3,
.mix-stage h3,
.mix-controls h3 {
  font-size: clamp(1.45rem, 2vw, 1.9rem);
  margin-bottom: 0.8rem;
}

.feature-body p:last-child {
  color: rgba(77, 16, 24, 0.8);
  line-height: 1.7;
}

.mix-stage,
.mix-controls {
  border-radius: var(--radius-xl);
  padding: clamp(1.4rem, 3vw, 2rem);
}

.mix-stage {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.mix-stage-header {
  text-align: center;
}

.mix-figure {
  position: relative;
  min-height: 34rem;
  border-radius: 1.75rem;
  overflow: hidden;
  background:
    radial-gradient(circle at top, rgba(255, 241, 184, 0.44), transparent 38%),
    linear-gradient(180deg, rgba(77, 16, 24, 0.16), rgba(254, 181, 17, 0.16));
  border: 1px solid rgba(77, 16, 24, 0.12);
  isolation: isolate;
}

.mix-figure::before {
  content: "";
  position: absolute;
  inset: 2rem;
  border-radius: 999px 999px 2.5rem 2.5rem;
  background: linear-gradient(180deg, rgba(255, 241, 184, 0.18), rgba(77, 16, 24, 0.06));
  border: 1px solid rgba(77, 16, 24, 0.08);
}

.mix-layer {
  position: absolute;
  inset: 0;
}

.mix-layer-upper {
  clip-path: inset(0 0 46% 0 round 1.75rem);
}

.mix-layer-lower {
  clip-path: inset(52% 0 0 0 round 1.75rem);
}

.mix-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.mix-empty {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: rgba(77, 16, 24, 0.56);
  font-size: 1rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.mix-divider {
  position: absolute;
  top: 49.5%;
  left: 12%;
  right: 12%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(77, 16, 24, 0.55), transparent);
  box-shadow: 0 0 18px rgba(77, 16, 24, 0.2);
}

.mix-summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.mix-summary > div {
  padding: 1rem 1.1rem;
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.58);
  border: 1px solid rgba(77, 16, 24, 0.1);
}

.mix-controls {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.mix-control-block {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mix-option-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.mix-option {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem;
  text-align: left;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(77, 16, 24, 0.1);
  background: rgba(255, 248, 228, 0.5);
  color: inherit;
  transition:
    transform 220ms ease,
    border-color 220ms ease,
    box-shadow 220ms ease,
    background 220ms ease;
}

.mix-option:hover,
.mix-option.active {
  transform: translateY(-2px);
  border-color: rgba(77, 16, 24, 0.26);
  background: rgba(255, 248, 228, 0.86);
  box-shadow: 0 16px 28px rgba(77, 16, 24, 0.1);
}

.mix-option-thumb {
  width: 4.5rem;
  height: 4.5rem;
  border-radius: var(--radius-md);
  object-fit: cover;
  flex-shrink: 0;
  background: rgba(77, 16, 24, 0.08);
}

.mix-option span {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.mix-option small {
  color: rgba(77, 16, 24, 0.72);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

@media (max-width: 991.98px) {
  .home-hero {
    min-height: auto;
    padding-top: 5.5rem;
  }

  .hero-shell {
    min-height: 36rem;
  }

  .mix-figure {
    min-height: 28rem;
  }
}

@media (max-width: 767.98px) {
  .featured-section::before,
  .mix-match-section::before {
    inset: 0.75rem;
  }

  .hero-shell h1 {
    letter-spacing: 0.1em;
  }

  .hero-actions {
    flex-direction: column;
    width: 100%;
  }

  .hero-actions .btn {
    width: 100%;
  }

  .feature-image-wrap {
    height: 18rem;
  }

  .mix-summary {
    grid-template-columns: 1fr;
  }

  .mix-option {
    align-items: flex-start;
  }
}

@media (prefers-reduced-motion: reduce) {
  .home-hero, .home-section, .section-heading, .section-content,
  .feature-card, .feature-image, .mix-option {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }
}
</style>