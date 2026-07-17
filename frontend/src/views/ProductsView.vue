<template>
  <div class="products-page">
    <section class="page-hero py-5">
      <div class="container">
        <p class="section-kicker text-center">The Collection</p>
        <h1 class="display-4 text-center mb-0">Our Collection</h1>
        <p class="text-center text-muted mt-3">
          Discover timeless pieces crafted for the modern connoisseur
        </p>
      </div>
    </section>

    <div class="container py-5">
      <div class="row">
        <div class="col-lg-3 mb-4">
          <div class="card filter-panel">
            <div class="card-body">
              <h5 class="card-title mb-4">Filters</h5>

              <div class="mb-4">
                <h6 class="text-uppercase small fw-bold mb-3">Category</h6>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    v-for="cat in categories"
                    :key="cat"
                    @click="selectedCategory = cat"
                    :class="[
                      'btn',
                      selectedCategory === cat ? 'btn-dark' : 'btn-outline-dark',
                      'btn-sm',
                    ]"
                  >
                    {{ cat }}
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <h6 class="text-uppercase small fw-bold mb-3">Price Range</h6>
                <div class="d-flex gap-2 align-items-center">
                  <input
                    type="number"
                    v-model="minPrice"
                    class="form-control form-control-sm"
                    placeholder="Min"
                  />
                  <span class="text-muted">-</span>
                  <input
                    type="number"
                    v-model="maxPrice"
                    class="form-control form-control-sm"
                    placeholder="Max"
                  />
                </div>
              </div>

              <div class="mb-4">
                <h6 class="text-uppercase small fw-bold mb-3">Size</h6>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    v-for="size in sizes"
                    :key="size"
                    @click="toggleSize(size)"
                    :class="[
                      'btn',
                      selectedSizes.includes(size) ? 'btn-dark' : 'btn-outline-dark',
                      'btn-sm',
                    ]"
                  >
                    {{ size }}
                  </button>
                </div>
              </div>

              <button @click="resetFilters" class="btn btn-outline-luxury btn-sm w-100">
                Reset Filters
              </button>
            </div>
          </div>
        </div>

        <div class="col-lg-9">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="mb-0 text-muted">Showing {{ filteredProducts.length }} products</p>
            <select v-model="sortBy" class="form-select w-auto">
              <option value="featured">Featured</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="rating">Highest Rated</option>
              <option value="newest">Newest</option>
            </select>
          </div>

          <div class="row product-grid">
            <ProductCard
              v-for="(product, index) in sortedProducts"
              :key="product.id"
              :product="product"
              :style="{ '--card-index': index }"
            />
          </div>

          <div v-if="sortedProducts.length === 0" class="text-center py-5">
            <i class="bi bi-search display-1 text-muted"></i>
            <h3 class="mt-3">No products found</h3>
            <p class="text-muted">Try adjusting your filters</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { useProductsStore } from "../stores/products";
import ProductCard from "../components/ProductCard.vue";

const route = useRoute();
const productsStore = useProductsStore();
const categories = computed(() => productsStore.categories);

const selectedCategory = ref(route.query.category || "All");
const minPrice = ref(null);
const maxPrice = ref(null);
const selectedSizes = ref([]);
const sortBy = ref("featured");

const sizes = ["XS", "S", "M", "L", "XL", "XXL"];

const filteredProducts = computed(() => {
  let products = productsStore.productsByCategory(selectedCategory.value).filter((p) => p.isShowed !== false);

  if (minPrice.value) {
    products = products.filter((p) => p.price >= minPrice.value);
  }
  if (maxPrice.value) {
    products = products.filter((p) => p.price <= maxPrice.value);
  }
  if (selectedSizes.value.length > 0) {
    products = products.filter((p) => p.sizes.some((size) => selectedSizes.value.includes(size)));
  }
  return products;
});

const sortedProducts = computed(() => {
  let products = [...filteredProducts.value];
  switch (sortBy.value) {
    case "price-low": return products.sort((a, b) => a.price - b.price);
    case "price-high": return products.sort((a, b) => b.price - a.price);
    case "rating": return products.sort((a, b) => b.rating - a.rating);
    case "newest": return products.sort((a, b) => b.id - a.id);
    default: return products.sort((a, b) => (b.featured ? 1 : 0) - (a.featured ? 1 : 0));
  }
});

const toggleSize = (size) => {
  const index = selectedSizes.value.indexOf(size);
  if (index > -1) {
    selectedSizes.value.splice(index, 1);
  } else {
    selectedSizes.value.push(size);
  }
};

const resetFilters = () => {
  selectedCategory.value = "All";
  minPrice.value = null;
  maxPrice.value = null;
  selectedSizes.value = [];
  sortBy.value = "featured";
};

watch(
  () => route.query.category,
  (category) => { selectedCategory.value = category || "All"; },
);

onMounted(() => {
  if (!productsStore.loaded) { productsStore.fetchProducts(); }
});
</script>

<style scoped>
.page-hero {
  background:
    radial-gradient(circle at top, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.58) 60%, rgba(77, 16, 24, 0.16));
  border-bottom: 1px solid rgba(77, 16, 24, 0.1);
}

.filter-panel {
  background: rgba(255, 248, 228, 0.88);
  backdrop-filter: blur(14px);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(77, 16, 24, 0.08);
  box-shadow: 0 24px 60px rgba(77, 16, 24, 0.12);
}

.product-grid :deep(.col-md-6.col-lg-4) {
  animation: product-card-enter 620ms cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(var(--card-index, 0) * 90ms);
}

@keyframes product-card-enter {
  from { opacity: 0; transform: translateY(28px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (prefers-reduced-motion: reduce) {
  .product-grid :deep(.col-md-6.col-lg-4) { animation: none; }
}
</style>