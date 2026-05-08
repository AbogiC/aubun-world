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
                     <div class="cart-thumb">
                       <img :src="item.image" :alt="item.name" class="cart-item-image" />
                     </div>
                   </div>
                   <div class="col-md-4">
                     <h6 class="mb-1">{{ item.name }}</h6>
                     <p class="text-muted small mb-1">
                       Size: {{ item.size }} | Color: {{ item.color }}
                     </p>
                     <div class="d-flex align-items-center gap-2 mt-2">
                       <div class="quantity-controls">
                         <button
                           @click="updateQuantity(item, item.quantity - 1)"
                           class="btn btn-outline-dark btn-sm"
                           :disabled="item.quantity <= 1"
                         >
                           -
                         </button>
                         <input
                           type="number"
                           v-model.number="item.quantity"
                           class="form-control form-control-sm text-center"
                           min="1"
                           @change="updateQuantity(item, item.quantity)"
                         />
                         <button
                           @click="updateQuantity(item, item.quantity + 1)"
                           class="btn btn-outline-dark btn-sm"
                         >
                           +
                         </button>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-2 d-flex align-items-center">
                     <div class="price-info text-end ms-auto">
                       <p class="unit-price text-muted small mb-0">
                         ${{ item.price.toLocaleString() }} × {{ item.quantity }}
                       </p>
                       <p class="total-price mb-0 fw-bold fs-5">
                         ${{ (item.price * item.quantity).toLocaleString() }}
                       </p>
                     </div>
                   </div>
                   <div class="col-md-2 d-flex align-items-center justify-content-end">
                     <button @click="removeItem(item)" class="btn btn-outline-danger btn-sm" title="Remove item">
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

              <router-link to="/checkout" class="btn btn-luxury btn-lg w-100 mb-2">
                Proceed to Checkout
              </router-link>

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
        <router-link to="/products" class="btn btn-luxury btn-lg mt-3">
          Start Shopping
        </router-link>
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

const applyPromo = async () => {
  try {
    const success = await cartStore.applyDiscount(promoCode.value);
    if (success) {
      alert("Discount applied successfully!");
      return;
    }
  } catch {
    // Fallback to the invalid-code message below.
  }

  alert("Invalid promo code");
};
</script>

<style scoped>
.cart-item:last-child {
  border-bottom: 0 !important;
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

.cart-thumb {
  height: 100px;
}

.cart-item-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.1), transparent 35%);
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  max-width: 180px;
}

.quantity-controls input {
  width: 70px !important;
  flex-shrink: 0;
  -moz-appearance: textfield;
}

.quantity-controls input::-webkit-outer-spin-button,
.quantity-controls input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.price-info {
  min-width: 100px;
}

.unit-price {
  font-size: 0.75rem;
  line-height: 1;
}

.total-price {
  font-size: 1.25rem;
  color: var(--primary-black);
}
</style>
