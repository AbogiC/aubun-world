<template>
  <div class="cart-page py-5">
    <div class="container">
      <p class="section-kicker">Your Selection</p>
      <h1 class="mb-5">Shopping Bag</h1>

      <div v-if="cartStore.items.length" class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <div
                v-for="item in cartStore.items"
                :key="`${item.id}-${item.size}-${item.color}`"
                class="cart-item border-bottom pb-3 mb-3"
              >
                <div class="row align-items-center">
                  <div class="col-md-2">
                    <div
                      class="cart-thumb d-flex align-items-center justify-content-center"
                      style="height: 100px"
                    >
                      <i class="bi bi-box-seam text-muted" style="font-size: 2rem"></i>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <h6 class="mb-1">{{ item.name }}</h6>
                    <p class="text-muted small mb-1">
                      Size: {{ item.size }} | Color: {{ item.color }}
                    </p>
                  </div>
                  <div class="col-md-2">
                    <div class="d-flex align-items-center">
                      <button
                        @click="updateQuantity(item, item.quantity - 1)"
                        class="btn btn-outline-dark btn-sm"
                      >
                        -
                      </button>
                      <input
                        type="number"
                        v-model="item.quantity"
                        class="form-control form-control-sm text-center mx-2"
                        style="width: 60px"
                        min="1"
                        @change="updateQuantity(item, item.quantity)"
                      />
                      <button @click="updateQuantity(item, item.quantity + 1)" class="btn btn-outline-dark btn-sm">
                        +
                      </button>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <p class="mb-0 fw-bold">${{ (item.price * item.quantity).toLocaleString() }}</p>
                  </div>
                  <div class="col-md-2 text-end">
                    <button @click="removeItem(item)" class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button @click="cartStore.clearCart()" class="btn btn-outline-luxury mt-3">
            Clear Cart
          </button>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-4">Order Summary</h5>

              <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span>${{ cartStore.subtotal.toLocaleString() }}</span>
              </div>

              <div class="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span>{{ cartStore.subtotal > 500 ? "Free" : "$25.00" }}</span>
              </div>

              <div
                v-if="cartStore.discount"
                class="d-flex justify-content-between mb-2 text-success"
              >
                <span>Discount</span>
                <span>-${{ cartStore.discount.toLocaleString() }}</span>
              </div>

              <hr />

              <div class="d-flex justify-content-between mb-4">
                <strong>Total</strong>
                <strong
                  >${{
                    (cartStore.total + (cartStore.subtotal > 500 ? 0 : 25)).toLocaleString()
                  }}</strong
                >
              </div>

              <div class="mb-4">
                <div class="input-group">
                  <input
                    v-model="promoCode"
                    type="text"
                    class="form-control"
                    placeholder="Promo code"
                  />
                  <button @click="applyPromo" class="btn btn-dark">Apply</button>
                </div>
                <small class="text-muted">Try code: LUXURY20 for 20% off</small>
              </div>

              <button class="btn btn-luxury btn-lg w-100 mb-2">Proceed to Checkout</button>

              <router-link to="/products" class="btn btn-outline-luxury w-100">
                Continue Shopping
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-5">
        <i class="bi bi-bag-x display-1 text-muted"></i>
        <h2 class="mt-4">Your bag is empty</h2>
        <p class="text-muted">Looks like you haven't added anything to your bag yet.</p>
        <router-link to="/products" class="btn btn-luxury btn-lg mt-3"> Start Shopping </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useCartStore } from "../stores/cart";

const cartStore = useCartStore();
const promoCode = ref("");

const updateQuantity = (item, newQuantity) => {
  if (newQuantity < 1) return;
  cartStore.updateQuantity(item.id, item.size, item.color, newQuantity);
};

const removeItem = (item) => {
  cartStore.removeFromCart(item.id, item.size, item.color);
};

const applyPromo = () => {
  const success = cartStore.applyDiscount(promoCode.value);
  if (success) {
    alert("Discount applied successfully!");
  } else {
    alert("Invalid promo code");
  }
};
</script>

<style scoped>
.cart-item:last-child {
  border-bottom: 0 !important;
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

.cart-thumb {
  background:
    linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(236, 236, 234, 0.86)),
    radial-gradient(circle at top right, rgba(0, 0, 0, 0.08), transparent 35%);
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
}
</style>
