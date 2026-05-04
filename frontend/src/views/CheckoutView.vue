<template>
  <div class="checkout-page py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="surface p-4 p-md-5">
            <p class="section-kicker mb-3">Secure Checkout</p>
            <h1 class="mb-4">Complete Your Order</h1>

            <form @submit.prevent="placeOrder">
              <div class="location-panel surface-subtle p-3 p-md-4 mb-4">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
                  <div>
                    <h2 class="h5 mb-2">Use your current location</h2>
                    <p class="text-muted mb-0">
                      Allow location access to auto-fill your shipping city, country, and postal code.
                    </p>
                  </div>
                  <button
                    type="button"
                    class="btn btn-outline-dark align-self-start align-self-md-center"
                    :disabled="locating"
                    @click="requestLocation"
                  >
                    {{ locating ? "Detecting..." : "Use My Location" }}
                  </button>
                </div>

                <div
                  v-if="locationMessage"
                  class="alert mb-0 mt-3"
                  :class="locationAlertClass"
                >
                  {{ locationMessage }}
                </div>
              </div>

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

              <div v-if="errorMessage" class="alert alert-danger mt-4 mb-0">
                {{ errorMessage }}
              </div>

              <button
                type="submit"
                class="btn btn-luxury w-100 mt-4"
                :disabled="submitting || !cartStore.items.length"
              >
                {{ submitting ? "Processing Order..." : "Place Order" }}
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
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { getBrowserLocation, lookupLocationByIp, reverseGeocode } from "../lib/location";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const submitting = ref(false);
const errorMessage = ref("");
const locating = ref(false);
const locationMessage = ref("Your browser can ask permission to detect your current area.");
const locationAlertClass = ref("alert-secondary");
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

const applyLocationToForm = (location) => {
  if (!form.address && location.address) {
    form.address = location.address;
  }

  if (location.city) {
    form.city = location.city;
  }

  if (location.country) {
    form.country = location.country;
  }

  if (location.postalCode) {
    form.postalCode = location.postalCode;
  }
};

const fillFromIpFallback = async (message) => {
  const location = await lookupLocationByIp();
  applyLocationToForm(location);
  locationAlertClass.value = "alert-warning";
  locationMessage.value =
    message || "We used your IP address to estimate your area. Please review the shipping details.";
};

const requestLocation = async () => {
  locating.value = true;
  locationAlertClass.value = "alert-secondary";
  locationMessage.value = "Waiting for your browser location permission...";

  try {
    const position = await getBrowserLocation();
    const location = await reverseGeocode(
      position.coords.latitude,
      position.coords.longitude,
    );

    applyLocationToForm(location);
    locationAlertClass.value = "alert-success";
    locationMessage.value =
      "Location access granted. We filled the available shipping details from your current area.";
  } catch (error) {
    const deniedPermission =
      error?.code === 1 ||
      /denied|permission/i.test(error?.message || "");

    try {
      await fillFromIpFallback(
        deniedPermission
          ? "Precise location was not allowed, so we estimated your area from your IP address."
          : "We could not get your precise location, so we estimated your area from your IP address.",
      );
    } catch {
      locationAlertClass.value = "alert-danger";
      locationMessage.value =
        "Location lookup is unavailable right now. You can still enter the shipping address manually.";
    }
  } finally {
    locating.value = false;
  }
};

const placeOrder = async () => {
  if (!cartStore.items.length) {
    errorMessage.value = "Your cart is empty.";
    return;
  }

  submitting.value = true;
  errorMessage.value = "";

  try {
    const order = await cartStore.checkout(form);
    alert(`Order ${order.orderNumber} placed successfully.`);
    router.push("/");
  } catch (error) {
    errorMessage.value = error.message || "Failed to place order.";
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.location-panel {
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(11, 11, 12, 0.03), rgba(188, 154, 109, 0.08));
}
</style>
