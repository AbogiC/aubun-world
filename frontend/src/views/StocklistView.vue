<template>
  <div ref="stocklistRootRef" class="stocklist-page">
    <section class="stocklist-hero" data-reveal-section>
      <div class="container text-center">
        <div class="hero-content">
          <p class="section-kicker" style="color: rgba(254, 181, 17, 0.7);">Where To Find Us</p>
          <div class="hero-divider"></div>
          <h1 class="display-3 mb-4" style="color: var(--gold-light);">Stocklist</h1>
          <p class="lead opacity-75 mb-0" style="color: var(--white);">
            Discover the boutiques and partners that carry Aubun World.
          </p>
          <div class="scroll-indicator">
            <span>Explore Stockists</span>
            <i class="bi bi-chevron-down"></i>
          </div>
        </div>
      </div>
      <div class="hero-ornament hero-ornament-1"></div>
      <div class="hero-ornament hero-ornament-2"></div>
    </section>

    <section class="stocklist-section py-5" data-reveal-section>
      <div class="container">
        <div class="section-heading section-title">
          <h2>Global Stockists</h2>
          <p class="text-muted">Authorized retailers carrying our collections</p>
        </div>

        <div v-if="loading" class="text-center py-5 section-content">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="!stores.length" class="text-center py-5 section-content">
          <i class="bi bi-shop text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No stockists yet. Check back soon.</p>
        </div>

        <div v-else class="row g-4 section-content">
          <div
            v-for="(store, index) in stores"
            :key="store.id"
            class="col-md-6 col-lg-4"
          >
            <div
              class="store-card surface h-100 p-4 hover-lift card-stagger d-flex flex-column"
              :style="{ transitionDelay: `${120 + index * 90}ms` }"
            >
              <div class="store-header d-flex align-items-center gap-3 mb-3">
                <div class="store-icon-wrap d-flex align-items-center justify-content-center">
                  <i :class="store.icon || 'bi bi-shop'"></i>
                </div>
                <div>
                  <h5 class="mb-0">{{ store.name }}</h5>
                  <span class="store-region">{{ store.region }}</span>
                </div>
              </div>
              <p class="store-address mb-2 text-muted small">
                <i class="bi bi-geo-alt-fill me-1"></i>{{ store.address }}
              </p>
              <p class="store-city mb-3 small">
                <i class="bi bi-buildings me-1"></i>{{ store.city }}
              </p>
              <div class="store-footer mt-auto d-flex justify-content-between align-items-center">
                <a
                  v-if="store.url"
                  :href="store.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="store-link"
                >
                  Visit Boutique <i class="bi bi-arrow-up-right"></i>
                </a>
                <span v-else class="store-link-muted">In-Store Only</span>
                <span class="store-badge">{{ store.type }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="stocklist-section stocklist-cta py-5" data-reveal-section>
      <div class="container">
        <div class="surface-elevated text-center p-5">
          <div class="cta-icon-wrap mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bi bi-shop"></i>
          </div>
          <h2 class="mb-3">Become a Stockist</h2>
          <p class="text-muted mx-auto mb-4" style="max-width: 560px;">
            Interested in carrying Aubun World in your store? We'd love to hear from you and explore
            a partnership that brings refined essentials to your community.
          </p>
          <a href="mailto:partners@aubunworld.com" class="btn btn-luxury">
            <i class="bi bi-envelope-paper me-2"></i>Get In Touch
          </a>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { api } from "../lib/api";

const stocklistRootRef = ref(null);
const stores = ref([]);
const loading = ref(true);

let sectionObserver;

const fetchStockists = async () => {
  try {
    const data = await api.get("/stockists");
    stores.value = data.stockists || [];
  } catch {
    stores.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchStockists();

  const sections = stocklistRootRef.value?.querySelectorAll("[data-reveal-section]");
  if (sections?.length) {
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
    sections.forEach((section) => sectionObserver.observe(section));
  }
});

onBeforeUnmount(() => {
  sectionObserver?.disconnect();
});
</script>

<style scoped>
.stocklist-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.2), transparent 28%),
    linear-gradient(180deg, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.62));
}

.stocklist-hero,
.stocklist-section {
  position: relative;
  opacity: 0;
  transform: translateY(44px);
  filter: blur(10px);
  transition:
    opacity 760ms cubic-bezier(0.22, 1, 0.36, 1),
    transform 760ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 760ms ease;
}

.stocklist-hero.is-visible,
.stocklist-section.is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.section-heading,
.section-content,
.card-stagger {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.stocklist-section.is-visible .section-heading {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 70ms;
}

.stocklist-section.is-visible .section-content {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 170ms;
}

.stocklist-section.is-visible .card-stagger {
  opacity: 1;
  transform: translateY(0);
}

.stocklist-hero {
  min-height: 85vh;
  display: flex;
  align-items: center;
  padding: 6rem 0 4rem;
  background:
    linear-gradient(135deg, rgba(77, 16, 24, 0.98), rgba(108, 24, 35, 0.94)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.15), transparent 45%);
  background-size: 200% 200%;
  animation: heroGradient 12s ease infinite;
  overflow: hidden;
  isolation: isolate;
}

@keyframes heroGradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.stocklist-hero::before,
.stocklist-hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

.stocklist-hero::before {
  width: 22rem;
  height: 22rem;
  top: -7rem;
  right: -5rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.25), transparent 72%);
}

.stocklist-hero::after {
  width: 18rem;
  height: 18rem;
  bottom: -6rem;
  left: -3rem;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.14), transparent 72%);
}

.hero-content {
  position: relative;
  z-index: 1;
  text-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
}

.hero-divider {
  width: 64px;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  margin: 1rem auto;
}

.scroll-indicator {
  margin-top: 3rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  color: rgba(254, 181, 17, 0.6);
  font-size: 0.7rem;
  letter-spacing: 0.24em;
  text-transform: uppercase;
}

.scroll-indicator i {
  font-size: 1.2rem;
  animation: bounceDown 2s ease infinite;
}

@keyframes bounceDown {
  0%, 100% { transform: translateY(0); opacity: 1; }
  50% { transform: translateY(6px); opacity: 0.5; }
}

.hero-ornament {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: var(--gold);
  opacity: 0.3;
  z-index: 0;
  pointer-events: none;
}

.hero-ornament-1 {
  top: 20%;
  left: 8%;
  width: 6px;
  height: 6px;
  animation: floatOrnament 6s ease-in-out infinite;
}

.hero-ornament-2 {
  bottom: 25%;
  right: 10%;
  width: 8px;
  height: 8px;
  animation: floatOrnament 8s ease-in-out infinite reverse;
}

@keyframes floatOrnament {
  0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
  50% { transform: translateY(-18px) scale(1.3); opacity: 0.6; }
}

.store-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-lg);
  position: relative;
}

.store-icon-wrap {
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  border-radius: var(--radius-md);
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.9), rgba(254, 181, 17, 0.6));
  font-size: 1.4rem;
  color: var(--primary-black);
}

.store-region {
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.store-address,
.store-city {
  color: var(--ink-soft);
}

.store-link {
  color: var(--primary-black);
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  transition: color var(--transition-base);
}

.store-link:hover {
  color: var(--secondary-black);
}

.store-link-muted {
  color: var(--ink-muted);
  font-size: 0.85rem;
}

.store-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.18);
  color: var(--ink-muted);
  font-size: 0.68rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.stocklist-cta {
  position: relative;
}

.cta-icon-wrap {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82));
  font-size: 1.7rem;
  color: var(--primary-black);
}

@media (max-width: 991.98px) {
  .stocklist-hero {
    min-height: auto;
    padding-top: 5.5rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .stocklist-hero,
  .stocklist-section,
  .section-heading,
  .section-content,
  .card-stagger {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }

  .hero-ornament,
  .scroll-indicator i {
    animation: none !important;
  }
}
</style>
