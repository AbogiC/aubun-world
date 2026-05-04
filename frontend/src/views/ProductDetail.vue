<template>
  <div class="product-detail py-5" v-if="product">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
          <li class="breadcrumb-item"><router-link to="/products">Collection</router-link></li>
          <li class="breadcrumb-item active">{{ product.name }}</li>
        </ol>
      </nav>

      <div class="row">
        <div class="col-lg-6 mb-4">
          <img
            :src="product.image"
            :alt="product.name"
            class="product-image surface subtle-glow"
          />
        </div>

        <div class="col-lg-6">
          <span class="badge bg-dark mb-3">{{ product.category }}</span>
          <h1 class="display-5 mb-2">{{ product.name }}</h1>

          <div class="d-flex align-items-center mb-3">
            <div class="text-warning me-2">
              <i
                v-for="star in 5"
                :key="star"
                :class="['bi', star <= Math.floor(product.rating) ? 'bi-star-fill' : 'bi-star']"
              >
              </i>
            </div>
            <span class="text-muted">{{ product.rating }} ({{ product.reviews }} reviews)</span>
          </div>

          <div class="mb-4">
            <span class="fs-3 fw-bold me-3">${{ product.price.toLocaleString() }}</span>
            <span v-if="product.originalPrice" class="text-decoration-line-through text-muted fs-5">
              ${{ product.originalPrice.toLocaleString() }}
            </span>
            <span v-if="product.originalPrice" class="badge bg-danger ms-2">
              {{
                Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)
              }}% OFF
            </span>
          </div>

          <p class="text-muted mb-4">{{ product.description }}</p>

          <div class="mb-4">
            <h6 class="fw-bold mb-3">
              Color: <span class="text-muted">{{ selectedColor }}</span>
            </h6>
            <div class="d-flex gap-2">
              <button
                v-for="color in product.colors"
                :key="color"
                @click="selectedColor = color"
                :class="['btn', selectedColor === color ? 'btn-dark' : 'btn-outline-dark']"
              >
                {{ color }}
              </button>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-bold mb-3">
              Size: <span class="text-muted">{{ selectedSize }}</span>
            </h6>
            <div class="d-flex gap-2">
              <button
                v-for="size in product.sizes"
                :key="size"
                @click="selectedSize = size"
                :class="['btn', selectedSize === size ? 'btn-dark' : 'btn-outline-dark']"
              >
                {{ size }}
              </button>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-bold mb-3">Quantity</h6>
            <div class="d-flex align-items-center gap-2">
              <button @click="quantity > 1 && quantity--" class="btn btn-outline-dark">-</button>
              <input
                type="number"
                v-model="quantity"
                class="form-control text-center"
                style="width: 70px"
                min="1"
              />
              <button @click="quantity++" class="btn btn-outline-dark">+</button>
            </div>
          </div>

          <div class="d-flex gap-3 mb-4">
            <button @click="addToCart" class="btn btn-luxury btn-lg flex-grow-1">
              Add to Cart - ${{ (product.price * quantity).toLocaleString() }}
            </button>
            <button class="btn btn-outline-dark btn-lg">
              <i class="bi bi-heart"></i>
            </button>
          </div>

          <div class="accordion" id="productAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button
                  class="accordion-button"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#details"
                >
                  Product Details
                </button>
              </h2>
              <div
                id="details"
                class="accordion-collapse collapse show"
                data-bs-parent="#productAccordion"
              >
                <div class="accordion-body">
                  <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check2 me-2"></i>Premium quality materials</li>
                    <li class="mb-2">
                      <i class="bi bi-check2 me-2"></i>Handcrafted with attention to detail
                    </li>
                    <li class="mb-2"><i class="bi bi-check2 me-2"></i>Made in Italy</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2"></i>Dry clean only</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button
                  class="accordion-button collapsed"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#shipping"
                >
                  Shipping & Returns
                </button>
              </h2>
              <div
                id="shipping"
                class="accordion-collapse collapse"
                data-bs-parent="#productAccordion"
              >
                <div class="accordion-body">
                  <p>Free shipping on orders over $500</p>
                  <p>30-day return policy</p>
                  <p>Express delivery available</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useProductsStore } from "../stores/products";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const route = useRoute();
const router = useRouter();
const productsStore = useProductsStore();
const cartStore = useCartStore();
const authStore = useAuthStore();

const product = computed(() =>
  productsStore.products.find((p) => p.id === parseInt(route.params.id)),
);

const selectedColor = ref("");
const selectedSize = ref("");
const quantity = ref(1);

watch(
  product,
  (value) => {
    if (!value) return;
    selectedColor.value = value.colors[0] || "";
    selectedSize.value = value.sizes[0] || "";
  },
  { immediate: true },
);

onMounted(() => {
  if (!productsStore.loaded) {
    productsStore.fetchProducts();
  }
});

const addToCart = () => {
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/cart" } });
    return;
  }

  cartStore.addToCart(product.value, selectedSize.value, selectedColor.value, quantity.value);
  router.push("/cart");
};
</script>

<style scoped>
.letter-space {
  letter-spacing: 0.2em;
}

.product-image {
  display: block;
  width: 100%;
  height: 600px;
  object-fit: cover;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(236, 236, 234, 0.88)),
    radial-gradient(circle at top right, rgba(0, 0, 0, 0.08), transparent 35%);
}

.breadcrumb {
  background: transparent;
  padding-left: 0;
}

.breadcrumb-item a {
  color: inherit;
  text-decoration: none;
  opacity: 0.7;
}
</style>
