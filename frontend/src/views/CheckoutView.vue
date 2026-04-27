<template>
  <div class="checkout-page py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="surface p-4 p-md-5">
            <p class="section-kicker mb-3">Secure Checkout</p>
            <h1 class="mb-4">Complete Your Order</h1>

            <form @submit.prevent="placeOrder">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">First Name</label>
                  <input v-model="form.firstName" class="form-control form-control-lg" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Last Name</label>
                  <input v-model="form.lastName" class="form-control form-control-lg" required />
                </div>
                <div class="col-12">
                  <label class="form-label">Email</label>
                  <input v-model="form.email" type="email" class="form-control form-control-lg" required />
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <input v-model="form.address" class="form-control form-control-lg" required />
                </div>
                <div class="col-md-5">
                  <label class="form-label">City</label>
                  <input v-model="form.city" class="form-control form-control-lg" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Country</label>
                  <input v-model="form.country" class="form-control form-control-lg" required />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Postal Code</label>
                  <input v-model="form.postalCode" class="form-control form-control-lg" required />
                </div>
              </div>

              <button type="submit" class="btn btn-luxury w-100 mt-4">
                Place Order
              </button>
            </form>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="surface p-4">
            <h4 class="mb-4">Order Summary</h4>
            <div
              v-for="item in cartStore.items"
              :key="`${item.id}-${item.size}-${item.color}`"
              class="d-flex justify-content-between align-items-start mb-3"
            >
              <div>
                <div class="fw-semibold">{{ item.name }}</div>
                <div class="text-muted small">{{ item.size }} / {{ item.color }} / {{ item.quantity }}</div>
              </div>
              <div class="fw-semibold">${{ (item.price * item.quantity).toLocaleString() }}</div>
            </div>

            <hr />

            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span>
              <span>${{ cartStore.subtotal.toLocaleString() }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Shipping</span>
              <span>{{ cartStore.subtotal > 500 ? "Free" : "$25.00" }}</span>
            </div>
            <div v-if="cartStore.discount" class="d-flex justify-content-between text-success mb-2">
              <span>Discount</span>
              <span>-${{ cartStore.discount.toLocaleString() }}</span>
            </div>
            <div class="d-flex justify-content-between fs-5 mt-4">
              <strong>Total</strong>
              <strong>${{ totalWithShipping.toLocaleString() }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const form = reactive({
  firstName: authStore.user?.name?.split(" ")[0] || "",
  lastName: authStore.user?.name?.split(" ").slice(1).join(" ") || "",
  email: authStore.user?.email || "",
  address: "",
  city: "",
  country: "",
  postalCode: "",
});

const totalWithShipping = computed(() =>
  cartStore.total + (cartStore.subtotal > 500 ? 0 : 25),
);

const placeOrder = () => {
  alert("Order placed successfully. This checkout flow is ready for payment integration next.");
  cartStore.clearCart();
  router.push("/");
};
</script>
