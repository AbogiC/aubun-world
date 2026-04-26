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
          <h2>Featured Collection</h2>
          <p class="text-muted">Handpicked pieces for the discerning taste</p>
        </div>
        <div class="row">
          <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
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
