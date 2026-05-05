<template>
  <div class="home">
    <HeroSection />

    <section class="py-5">
      <div class="container">
        <div class="section-title">
          <h2>Shop by Category</h2>
          <p class="text-muted">Curated collections for every occasion</p>
        </div>
        <div class="row g-3">
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

    <section class="py-5 bg-white">
      <div class="container">
        <div class="section-title">
          <div>
            <h2>Featured Collection</h2>
            <p class="text-muted">Handpicked pieces for the discerning taste</p>
          </div>
        </div>
        <div class="featured-carousel">
          <div class="featured-carousel-row">
            <ProductCard
              v-for="(product, index) in featuredProducts"
              :key="product.id"
              :product="product"
              :style="{ '--card-index': index }"
            />
          </div>
        </div>
        <div class="text-center mt-4">
          <router-link to="/products" class="btn btn-luxury btn-lg px-5">
            View All Products
          </router-link>
        </div>
      </div>
    </section>

    <section class="py-5 bg-light">
      <div class="container">
        <div class="section-title">
          <h2>What Our Clients Say</h2>
        </div>
        <div class="row">
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
import { computed } from "vue";
import { useProductsStore } from "../stores/products";
import HeroSection from "../components/HeroSection.vue";
import ProductCard from "../components/ProductCard.vue";
import Newsletter from "../components/Newsletter.vue";

const productsStore = useProductsStore();
const featuredProducts = computed(() => productsStore.featuredProducts);

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
</script>

<style scoped>
.featured-carousel {
  padding-bottom: 1rem;
  overflow-x: auto;
  overflow-y: visible;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
  scrollbar-color: rgba(180, 147, 88, 0.5) rgba(17, 17, 17, 0.08);
}

.featured-carousel-row {
  display: flex;
  flex-wrap: nowrap;
  gap: 1.25rem;
  min-width: max-content;
  padding: 0.35rem 0.25rem 1rem;
}

.featured-carousel::-webkit-scrollbar {
  height: 0.5rem;
}

.featured-carousel::-webkit-scrollbar-track {
  background: rgba(17, 17, 17, 0.08);
  border-radius: 999px;
}

.featured-carousel::-webkit-scrollbar-thumb {
  background: linear-gradient(90deg, rgba(143, 112, 57, 0.6), rgba(201, 168, 106, 0.88));
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
  border: 1px solid rgba(180, 147, 88, 0.22);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 243, 236, 0.96)),
    radial-gradient(circle at top, rgba(201, 168, 106, 0.12), transparent 48%);
  box-shadow:
    0 18px 45px rgba(17, 17, 17, 0.08),
    0 2px 10px rgba(201, 168, 106, 0.12);
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
    linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent 34%, transparent 68%, rgba(201, 168, 106, 0.12));
  opacity: 0.85;
  pointer-events: none;
}

.featured-carousel :deep(.product-card:hover) {
  border-color: rgba(180, 147, 88, 0.42);
  box-shadow:
    0 28px 60px rgba(17, 17, 17, 0.12),
    0 10px 24px rgba(201, 168, 106, 0.16);
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
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.72));
}

.featured-carousel :deep(.section-kicker) {
  letter-spacing: 0.16em;
  color: rgba(110, 83, 35, 0.88);
}

.featured-carousel :deep(h5) {
  font-size: 1.1rem;
  line-height: 1.35;
}

.featured-carousel :deep(.price) {
  color: #16120d;
}

.featured-carousel :deep(.badge.bg-dark) {
  border-radius: 999px;
  padding: 0.5rem 0.8rem;
  background: rgba(17, 17, 17, 0.88) !important;
  backdrop-filter: blur(10px);
}

.featured-carousel :deep(.action-btn) {
  background: rgba(255, 255, 255, 0.88);
  color: #111;
  box-shadow: 0 10px 22px rgba(17, 17, 17, 0.12);
}

.featured-carousel :deep(.action-btn:hover) {
  background: #fff;
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
  border: 1px solid rgba(11, 11, 12, 0.08);
}

.category-card:hover {
  border-color: rgba(11, 11, 12, 0.22);
}

.testimonial-card {
  border: 1px solid rgba(11, 11, 12, 0.08);
}

.testimonial-card:hover {
  transform: translateY(-6px);
}
</style>
