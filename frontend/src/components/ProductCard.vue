<template>
  <div class="col-md-6 col-lg-3 mb-4 fade-in-up">
    <div class="product-card hover-lift" @click="$router.push(`/product/${product.id}`)">
      <div class="product-image-wrapper position-relative overflow-hidden">
        <div class="product-image d-flex align-items-center justify-content-center" style="height: 400px">
          <div class="text-center">
            <i class="bi bi-box-seam text-muted" style="font-size: 4.5rem"></i>
            <p class="text-muted mt-3">{{ product.name }}</p>
          </div>
        </div>

        <div class="product-actions position-absolute top-0 end-0 p-3">
          <button class="btn btn-luxury btn-sm rounded-circle mb-2 action-btn" @click.stop="quickAdd">
            <i class="bi bi-bag-plus"></i>
          </button>
        </div>

        <div v-if="product.featured" class="position-absolute top-0 start-0 m-3">
          <span class="badge bg-dark">Featured</span>
        </div>
      </div>

      <div class="product-info p-3">
        <p class="section-kicker mb-1">{{ product.category }}</p>
        <h5 class="mb-2">{{ product.name }}</h5>
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="rating-stars">
            <i class="bi bi-star-fill small"></i>
            <i class="bi bi-star-fill small"></i>
            <i class="bi bi-star-fill small"></i>
            <i class="bi bi-star-fill small"></i>
            <i class="bi bi-star-half small"></i>
          </div>
          <span class="text-muted small">({{ product.reviews }})</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="price">${{ product.price.toLocaleString() }}</span>
          <span
            v-if="product.originalPrice"
            class="original-price text-decoration-line-through text-muted"
          >
            ${{ product.originalPrice.toLocaleString() }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const props = defineProps({
  product: Object,
});

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();

const quickAdd = () => {
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/cart" } });
    return;
  }

  cartStore.addToCart(props.product, "M", props.product.colors[0], 1);
  router.push("/cart");
};
</script>

<style scoped>
.product-card {
  cursor: pointer;
  background: rgba(255, 255, 255, 0.82);
  border: 1px solid rgba(11, 11, 12, 0.1);
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-6px);
}

.price {
  font-family:
    Georgia,
    "Times New Roman",
    serif;
  font-size: 1.3rem;
  font-weight: 700;
}

.product-actions {
  opacity: 0;
  transition: opacity 220ms ease, transform 220ms ease;
  transform: translateY(-6px);
}

.product-card:hover .product-actions {
  opacity: 1;
  transform: translateY(0);
}

.product-image {
  background:
    linear-gradient(135deg, rgba(12, 12, 13, 0.96), rgba(46, 46, 48, 0.82)),
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent 40%);
  color: rgba(255, 255, 255, 0.9);
}

.product-image p {
  color: rgba(255, 255, 255, 0.72) !important;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  font-size: 0.72rem;
}

.action-btn {
  width: 2.8rem;
  height: 2.8rem;
  border-radius: 999px;
}

.rating-stars {
  color: #111;
}
</style>
