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
                    <button
                      @click="removeItem(item)"
                      class="btn btn-outline-danger btn-sm"
                      title="Remove item"
                    >
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
                <span>Discount ({{ cartStore.discountCode }})</span>
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
                    placeholder="Voucher code"
                  />
                  <button @click="applyPromo" class="btn btn-dark">Apply</button>
                </div>
                <small class="text-muted"
                  >Enter an active voucher code from the store manager.</small
                >
              </div>

              <button
                type="button"
                class="btn btn-luxury btn-lg w-100 mb-2"
                @click="proceedToCheckout"
              >
                Proceed to Checkout
              </button>

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

      <div
        v-if="showVerificationModal"
        class="verification-modal"
        @click.self="closeVerificationModal"
      >
        <div class="verification-modal__dialog surface elevated">
          <div class="verification-modal__icon">
            <i class="bi bi-envelope-exclamation"></i>
          </div>
          <p class="section-kicker mb-2">Verification Required</p>
          <h2 class="verification-modal__title">Verify your email before checkout</h2>
          <p class="verification-modal__message">
            Please verify your email address first so we can continue with your checkout and send
            order updates correctly.
          </p>

          <div class="verification-modal__actions">
            <button type="button" class="btn btn-outline-luxury" @click="closeVerificationModal">
              Not Now
            </button>
            <button type="button" class="btn btn-luxury" @click="goToProfileForVerification">
              Go to Profile
            </button>
          </div>
        </div>
      </div>

      <div v-if="promoModal.open" class="feedback-modal" @click.self="closePromoModal">
        <div class="feedback-modal__dialog surface elevated">
          <div
            class="feedback-modal__icon"
            :class="
              promoModal.type === 'success'
                ? 'feedback-modal__icon--success'
                : 'feedback-modal__icon--error'
            "
          >
            <i
              :class="
                promoModal.type === 'success'
                  ? 'bi bi-ticket-perforated'
                  : 'bi bi-exclamation-circle'
              "
            ></i>
          </div>
          <p class="section-kicker mb-2">
            {{ promoModal.type === "success" ? "Voucher Applied" : "Voucher Notice" }}
          </p>
          <h2 class="feedback-modal__title">{{ promoModal.title }}</h2>
          <p class="feedback-modal__message">
            {{ promoModal.message }}
          </p>

          <div class="feedback-modal__actions">
            <button type="button" class="btn btn-luxury" @click="closePromoModal">Continue</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const promoCode = ref("");
const showVerificationModal = ref(false);
const promoModal = reactive({
  open: false,
  type: "success",
  title: "",
  message: "",
});

const updateQuantity = (item, newQuantity) => {
  if (newQuantity < 1) return;
  cartStore.updateQuantity(item.id, item.size, item.color, newQuantity);
};

const removeItem = (item) => {
  cartStore.removeFromCart(item.id, item.size, item.color);
};

const openPromoModal = ({ type, title, message }) => {
  promoModal.open = true;
  promoModal.type = type;
  promoModal.title = title;
  promoModal.message = message;
};

const applyPromo = async () => {
  try {
    const success = await cartStore.applyDiscount(promoCode.value);
    if (success) {
      openPromoModal({
        type: "success",
        title: "Voucher added to your order",
        message:
          "Your discount has been applied successfully and your order summary has been updated.",
      });
      promoCode.value = "";
      return;
    }
  } catch (error) {
    openPromoModal({
      type: "error",
      title: "Unable to apply this voucher",
      message: error.message || "This voucher code is invalid or unavailable right now.",
    });
    return;
  }

  openPromoModal({
    type: "error",
    title: "Login required",
    message: "Please log in to apply a voucher code to your shopping bag.",
  });
};

const closeVerificationModal = () => {
  showVerificationModal.value = false;
};

const closePromoModal = () => {
  promoModal.open = false;
};

const goToProfileForVerification = () => {
  closeVerificationModal();
  router.push("/profile");
};

const proceedToCheckout = async () => {
  try {
    await authStore.refreshUser();
  } catch {
    // Keep the local auth state fallback below if refreshing the user fails.
  }

  const isEmailVerified = Boolean(authStore.user?.email_verified || authStore.user?.emailVerified);

  if (!isEmailVerified) {
    showVerificationModal.value = true;
    return;
  }

  router.push("/checkout");
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

.verification-modal {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.48), rgba(20, 10, 12, 0.62)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.14), transparent 38%);
  backdrop-filter: blur(6px);
}

.verification-modal__dialog {
  width: min(100%, 480px);
  padding: 2rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 1.5rem;
  background: linear-gradient(180deg, rgba(255, 248, 228, 0.98), rgba(255, 255, 255, 0.96));
  box-shadow: 0 1.5rem 4rem rgba(43, 17, 22, 0.22);
  text-align: center;
}

.verification-modal__icon {
  width: 4.25rem;
  height: 4.25rem;
  margin: 0 auto 1rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(254, 181, 17, 0.16), rgba(77, 16, 24, 0.1));
  color: #7a5200;
  font-size: 1.75rem;
}

.verification-modal__title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.5rem, 2vw, 1.85rem);
}

.verification-modal__message {
  margin: 0 auto;
  max-width: 28rem;
  color: var(--ink-muted);
  line-height: 1.6;
}

.feedback-modal {
  position: fixed;
  inset: 0;
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.5), rgba(20, 10, 12, 0.64)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.16), transparent 38%);
  backdrop-filter: blur(8px);
}

.feedback-modal__dialog {
  width: min(100%, 460px);
  padding: 2rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 1.5rem;
  background: linear-gradient(180deg, rgba(255, 248, 228, 0.98), rgba(255, 255, 255, 0.96));
  box-shadow: 0 1.5rem 4rem rgba(43, 17, 22, 0.22);
  text-align: center;
}

.feedback-modal__icon {
  width: 4.25rem;
  height: 4.25rem;
  margin: 0 auto 1rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  font-size: 1.75rem;
}

.feedback-modal__icon--success {
  background: linear-gradient(145deg, rgba(254, 181, 17, 0.18), rgba(77, 16, 24, 0.1));
  color: #7a5200;
}

.feedback-modal__icon--error {
  background: linear-gradient(145deg, rgba(156, 34, 51, 0.14), rgba(77, 16, 24, 0.1));
  color: #8a2132;
}

.feedback-modal__title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.45rem, 2vw, 1.8rem);
}

.feedback-modal__message {
  margin: 0 auto;
  max-width: 26rem;
  color: var(--ink-muted);
  line-height: 1.6;
}

.feedback-modal__actions {
  margin-top: 1.75rem;
  display: flex;
  justify-content: center;
}

.verification-modal__actions {
  margin-top: 1.75rem;
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

@media (max-width: 575.98px) {
  .verification-modal__dialog {
    padding: 1.5rem;
  }

  .feedback-modal__dialog {
    padding: 1.5rem;
  }

  .verification-modal__actions {
    flex-direction: column-reverse;
  }

  .verification-modal__actions .btn {
    width: 100%;
  }

  .feedback-modal__actions .btn {
    width: 100%;
  }
}
</style>
