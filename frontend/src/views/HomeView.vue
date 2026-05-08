<template>
  <div ref="homeRootRef" class="home">
    <HeroSection />

    <section class="home-section home-section-category py-5" data-reveal-section>
      <div class="container section-shell">
        <div class="section-title section-heading">
          <h2>Shop by Category</h2>
          <p class="text-muted">Curated collections for every occasion</p>
        </div>
        <div class="row g-3 section-content">
          <div v-for="(cat, index) in categories" :key="index" class="col-6 col-md-4 col-lg-2">
            <div
              class="category-card text-center p-4 surface hover-lift reveal"
              :class="`stagger-${(index % 4) + 1}`"
              @click="$router.push(`/products?category=${cat}`)"
            >
              <div class="category-icon mb-3">
                <i :class="getCategoryIcon(cat)" style="font-size: 2rem"></i>
              </div>
              <h6 class="text-uppercase mb-0">{{ cat }}</h6>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="home-section home-section-featured py-5 bg-white" data-reveal-section>
      <div class="container section-shell">
        <div class="section-title section-heading">
          <div>
            <h2>Featured Collection</h2>
            <p class="text-muted">Handpicked pieces for the discerning taste</p>
          </div>
        </div>
        <div class="featured-carousel section-content">
          <div class="featured-carousel-row">
            <ProductCard
              v-for="(product, index) in featuredProducts"
              :key="product.id"
              :product="product"
              :style="{ '--card-index': index }"
            />
          </div>
        </div>
        <div class="text-center mt-4 section-cta">
          <router-link to="/products" class="btn btn-luxury btn-lg px-5">
            View All Products
          </router-link>
        </div>
      </div>
    </section>

    <section class="home-section home-section-testimonials py-5 bg-light" data-reveal-section>
      <div class="container section-shell">
        <div class="section-title section-heading">
          <h2>What Our Clients Say</h2>
        </div>
        <div class="row section-content">
          <div v-for="(testimonial, index) in testimonials" :key="index" class="col-md-4 mb-4">
            <div
              class="testimonial-card text-center p-4 surface h-100 hover-lift reveal"
              :class="`stagger-${(index % 4) + 1}`"
            >
              <div class="mb-3">
                <i class="bi bi-quote display-4 text-muted"></i>
              </div>
              <p class="mb-3">{{ testimonial.text }}</p>
              <div class="stars text-warning mb-2">
                <i v-for="star in 5" :key="star" class="bi bi-star-fill"></i>
              </div>
              <h6 class="mb-0">{{ testimonial.name }}</h6>
              <small class="text-muted">{{ testimonial.title }}</small>
            </div>
          </div>
        </div>
      </div>
    </section>

    <Newsletter />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useProductsStore } from "../stores/products";
import HeroSection from "../components/HeroSection.vue";
import ProductCard from "../components/ProductCard.vue";
import Newsletter from "../components/Newsletter.vue";

const homeRootRef = ref(null);
const productsStore = useProductsStore();
const featuredProducts = computed(() => productsStore.featuredProducts);
let sectionObserver;

const categories = ["Dresses", "Outerwear", "Pants", "Shirts", "Blazers", "Knitwear"];

const testimonials = [
  {
    name: "Victoria M.",
    title: "Fashion Editor",
    text: "The quality and craftsmanship of Noir Elegance pieces are simply unmatched. Each garment tells a story of elegance.",
  },
  {
    name: "Alexander R.",
    title: "CEO, Tech Startup",
    text: "I've never felt more confident than when wearing their tailored suits. The attention to detail is extraordinary.",
  },
  {
    name: "Isabella L.",
    title: "Art Director",
    text: "Their evening gowns are works of art. I receive compliments every time I wear one of their pieces.",
  },
];

const getCategoryIcon = (cat) => {
  const icons = {
    Dresses: "bi bi-person-standing-dress",
    Outerwear: "bi bi-sunglasses",
    Pants: "bi bi-person",
    Shirts: "bi bi-person",
    Blazers: "bi bi-briefcase",
    Knitwear: "bi bi-person",
  };
  return icons[cat] || "bi bi-grid";
};

onMounted(() => {
  const sections = homeRootRef.value?.querySelectorAll("[data-reveal-section]");

  if (!sections?.length) {
    return;
  }

  sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        sectionObserver?.unobserve(entry.target);
      });
    },
    {
      threshold: 0.2,
      rootMargin: "0px 0px -10% 0px",
    },
  );

  sections.forEach((section) => {
    sectionObserver.observe(section);
  });
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

.home-section::before {
  content: "";
  position: absolute;
  inset: 1.5rem 2rem;
  border-radius: 2rem;
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.38), rgba(255, 241, 184, 0)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.08), transparent 42%);
  pointer-events: none;
  opacity: 0;
  transform: scale(0.98);
  transition:
    opacity 760ms ease,
    transform 760ms cubic-bezier(0.22, 1, 0.36, 1);
}

.home-section.is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.home-section.is-visible::before {
  opacity: 1;
  transform: scale(1);
}

.section-shell {
  position: relative;
  z-index: 1;
}

.section-heading,
.section-content,
.section-cta {
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

.home-section.is-visible .section-cta {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 260ms;
}

.home-section-featured::after,
.home-section-testimonials::after {
  content: "";
  position: absolute;
  width: min(24vw, 18rem);
  height: min(24vw, 18rem);
  border-radius: 999px;
  background: radial-gradient(circle, rgba(77, 16, 24, 0.16), transparent 70%);
  filter: blur(10px);
  pointer-events: none;
}

.home-section-featured::after {
  top: 1rem;
  right: 3rem;
}

.home-section-testimonials::after {
  bottom: 2rem;
  left: 3rem;
}

.featured-carousel {
  padding-bottom: 1rem;
  overflow-x: auto;
  overflow-y: visible;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
  scrollbar-color: rgba(77, 16, 24, 0.55) rgba(254, 181, 17, 0.18);
}

.featured-carousel-row {
  display: flex;
  flex-wrap: nowrap;
  gap: 1.25rem;
  min-width: max-content;
  padding: 0.35rem 0.25rem 1rem;
}

.home-section.is-visible .featured-carousel-row :deep(.col-md-6.col-lg-4) {
  animation: luxuryCardRise 720ms cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(var(--card-index, 0) * 110ms + 180ms);
}

.featured-carousel::-webkit-scrollbar {
  height: 0.5rem;
}

.featured-carousel::-webkit-scrollbar-track {
  background: rgba(77, 16, 24, 0.08);
  border-radius: 999px;
}

.featured-carousel::-webkit-scrollbar-thumb {
  background: linear-gradient(90deg, rgba(77, 16, 24, 0.7), rgba(254, 181, 17, 0.92));
  border-radius: 999px;
}

.featured-carousel :deep(.col-md-6.col-lg-4) {
  flex: 0 0 calc((100% - 2.5rem) / 3);
  max-width: calc((100% - 2.5rem) / 3);
  margin-bottom: 0;
  padding: 0;
  scroll-snap-align: start;
  transition: transform 240ms ease, opacity 240ms ease;
}

.featured-carousel :deep(.product-card) {
  position: relative;
  border-radius: 1.75rem;
  border: 1px solid rgba(77, 16, 24, 0.18);
  background:
    linear-gradient(180deg, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.3)),
    radial-gradient(circle at top, rgba(77, 16, 24, 0.12), transparent 48%);
  box-shadow:
    0 18px 45px rgba(77, 16, 24, 0.12),
    0 2px 10px rgba(254, 181, 17, 0.2);
  overflow: hidden;
  isolation: isolate;
  transform: translateY(calc(var(--card-index, 0) * 2px));
  transition:
    transform 260ms ease,
    box-shadow 260ms ease,
    border-color 260ms ease,
    background 260ms ease;
}

.featured-carousel :deep(.product-card)::after {
  content: "";
  position: absolute;
  inset: 0;
  background:
    linear-gradient(135deg, rgba(255, 241, 184, 0.22), transparent 34%, transparent 68%, rgba(77, 16, 24, 0.12));
  opacity: 0.85;
  pointer-events: none;
}

.featured-carousel :deep(.product-card:hover) {
  border-color: rgba(77, 16, 24, 0.38);
  box-shadow:
    0 28px 60px rgba(77, 16, 24, 0.16),
    0 10px 24px rgba(254, 181, 17, 0.2);
  transform: translateY(-10px);
}

.featured-carousel :deep(.product-image-wrapper) {
  border-radius: 1.75rem 1.75rem 1.15rem 1.15rem;
}

.featured-carousel :deep(.product-image) {
  transition:
    transform 420ms ease,
    filter 320ms ease;
  filter: saturate(0.92) contrast(1.02);
}

.featured-carousel :deep(.product-card:hover .product-image) {
  transform: scale(1.045);
  filter: saturate(1) contrast(1.05);
}

.featured-carousel :deep(.product-info) {
  position: relative;
  z-index: 1;
  padding: 1.4rem 1.4rem 1.55rem;
  background: linear-gradient(180deg, rgba(255, 241, 184, 0.18), rgba(255, 241, 184, 0.82));
}

.featured-carousel :deep(.section-kicker) {
  letter-spacing: 0.16em;
  color: rgba(77, 16, 24, 0.76);
}

.featured-carousel :deep(h5) {
  font-size: 1.1rem;
  line-height: 1.35;
}

.featured-carousel :deep(.price) {
  color: var(--primary-black);
}

.featured-carousel :deep(.badge.bg-dark) {
  border-radius: 999px;
  padding: 0.5rem 0.8rem;
  background: rgba(77, 16, 24, 0.9) !important;
  backdrop-filter: blur(10px);
}

.featured-carousel :deep(.action-btn) {
  background: rgba(255, 241, 184, 0.92);
  color: var(--primary-black);
  box-shadow: 0 10px 22px rgba(77, 16, 24, 0.14);
}

.featured-carousel :deep(.action-btn:hover) {
  background: var(--soft-white);
}

@media (max-width: 991.98px) {
  .featured-carousel :deep(.col-md-6.col-lg-4) {
    flex-basis: calc((100% - 1.25rem) / 2);
    max-width: calc((100% - 1.25rem) / 2);
  }
}

@media (max-width: 767.98px) {
  .featured-carousel-row {
    gap: 1rem;
    padding-inline: 0.1rem;
  }

  .featured-carousel :deep(.col-md-6.col-lg-4) {
    flex-basis: min(84vw, 22rem);
    max-width: min(84vw, 22rem);
  }
}

.category-card {
  cursor: pointer;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: 1.35rem;
  background:
    linear-gradient(180deg, rgba(255, 241, 184, 0.96), rgba(254, 181, 17, 0.3)),
    radial-gradient(circle at top, rgba(77, 16, 24, 0.08), transparent 56%);
  box-shadow: 0 14px 34px rgba(77, 16, 24, 0.08);
  transition:
    border-color 220ms ease,
    transform 220ms ease,
    box-shadow 220ms ease;
}

.category-card:hover {
  border-color: rgba(77, 16, 24, 0.26);
  box-shadow: 0 22px 44px rgba(77, 16, 24, 0.12);
}

.testimonial-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1.6rem;
  background:
    linear-gradient(180deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.26)),
    radial-gradient(circle at top left, rgba(77, 16, 24, 0.08), transparent 42%);
  box-shadow: 0 18px 40px rgba(77, 16, 24, 0.08);
  transition:
    transform 240ms ease,
    box-shadow 240ms ease,
    border-color 240ms ease;
}

.testimonial-card:hover {
  transform: translateY(-6px);
  border-color: rgba(77, 16, 24, 0.24);
  box-shadow: 0 26px 54px rgba(77, 16, 24, 0.14);
}

@keyframes luxuryCardRise {
  from {
    opacity: 0;
    transform: translateY(38px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .home-section,
  .home-section::before,
  .section-heading,
  .section-content,
  .section-cta,
  .featured-carousel :deep(.col-md-6.col-lg-4),
  .category-card,
  .testimonial-card {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }
}
</style>
