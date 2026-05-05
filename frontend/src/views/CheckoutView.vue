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

              <div class="shipping-options-panel surface-subtle p-3 p-md-4 mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                  <div>
                    <h2 class="h5 mb-2">Shipping Options</h2>
                    <p class="text-muted mb-0">
                      We use your shipping country to match the nearest available shop route.
                    </p>
                  </div>
                  <div v-if="shippingQuote.shopCountryName" class="shipping-origin">
                    Ships from {{ shippingQuote.shopCountryName }}
                  </div>
                </div>

                <div v-if="shippingLoading" class="text-muted mt-3">
                  Loading shipping options...
                </div>

                <div v-else-if="shippingQuote.message" class="alert mt-3 mb-0" :class="shippingQuote.alertClass">
                  {{ shippingQuote.message }}
                </div>

                <div v-if="shippingOptions.length" class="shipping-option-list mt-3">
                  <label
                    v-for="option in shippingOptions"
                    :key="`shipping-option-${option.id}`"
                    class="shipping-option"
                    :class="{ 'shipping-option--active': selectedShippingRateId === option.id }"
                  >
                    <input
                      v-model="selectedShippingRateId"
                      class="form-check-input"
                      type="radio"
                      name="shipping-rate"
                      :value="option.id"
                    />
                    <div class="shipping-option__body">
                      <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                          <div class="fw-semibold">{{ option.tierName }}</div>
                          <div class="small text-muted">{{ formatDistanceRange(option) }}</div>
                        </div>
                        <div class="fw-semibold">${{ Number(option.shippingCost).toLocaleString() }}</div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <div v-if="errorMessage" class="alert alert-danger mt-4 mb-0">
                {{ errorMessage }}
              </div>

              <button
                type="submit"
                class="btn btn-luxury w-100 mt-4"
                :disabled="submitting || !cartStore.items.length || !canPlaceOrder"
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
              <span>{{ shippingSummaryLabel }}</span>
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
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { api } from "../lib/api";
import { getBrowserLocation, lookupLocationByIp, reverseGeocode } from "../lib/location";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const submitting = ref(false);
const errorMessage = ref("");
const locating = ref(false);
const shippingLoading = ref(false);
const shippingOptions = ref([]);
const selectedShippingRateId = ref(null);
const latestShippingLookup = ref(0);
const locationMessage = ref("Your browser can ask permission to detect your current area.");
const locationAlertClass = ref("alert-secondary");
const shippingQuote = reactive({
  shopCountryName: "",
  message: "Enter or detect your shipping country to see available delivery options.",
  alertClass: "alert-secondary",
  available: false,
});
const form = reactive({
  firstName: authStore.user?.name?.split(" ")[0] || "",
  lastName: authStore.user?.name?.split(" ").slice(1).join(" ") || "",
  email: authStore.user?.email || "",
  address: "",
  city: "",
  country: "",
  postalCode: "",
});

const selectedShippingOption = computed(() =>
  shippingOptions.value.find((option) => option.id === selectedShippingRateId.value) || null,
);
const shippingAmount = computed(() => selectedShippingOption.value?.shippingCost ?? 0);
const shippingSummaryLabel = computed(() => {
  if (shippingLoading.value) {
    return "Loading...";
  }

  if (selectedShippingOption.value) {
    return `$${Number(selectedShippingOption.value.shippingCost).toLocaleString()}`;
  }

  if (form.country.trim() === "") {
    return "Select country";
  }

  if (!shippingQuote.available) {
    return "Unavailable";
  }

  return "Choose option";
});
const totalWithShipping = computed(() => cartStore.total + shippingAmount.value);
const canPlaceOrder = computed(() => {
  if (!form.country.trim() || shippingLoading.value) {
    return false;
  }

  if (!shippingQuote.available) {
    return false;
  }

  return selectedShippingRateId.value !== null;
});

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

const resetShippingQuote = (message = "Enter or detect your shipping country to see available delivery options.") => {
  shippingOptions.value = [];
  selectedShippingRateId.value = null;
  shippingQuote.shopCountryName = "";
  shippingQuote.message = message;
  shippingQuote.alertClass = "alert-secondary";
  shippingQuote.available = false;
};

const fetchShippingOptions = async (country) => {
  const normalizedCountry = country.trim();
  const requestId = latestShippingLookup.value + 1;
  latestShippingLookup.value = requestId;

  if (!normalizedCountry) {
    resetShippingQuote();
    return;
  }

  shippingLoading.value = true;
  errorMessage.value = "";

  try {
    const payload = await api.get(`/shipping-options?country=${encodeURIComponent(normalizedCountry)}`);

    if (latestShippingLookup.value !== requestId) {
      return;
    }

    shippingQuote.shopCountryName = payload.shopCountryName || "";
    shippingQuote.available = Boolean(payload.available);
    shippingOptions.value = payload.shippingOptions || [];

    if (!payload.available || shippingOptions.value.length === 0) {
      selectedShippingRateId.value = null;
      shippingQuote.alertClass = "alert-danger";
      shippingQuote.message = `Shipping is not available for ${normalizedCountry} yet.`;
      return;
    }

    if (shippingOptions.value.length === 1) {
      selectedShippingRateId.value = shippingOptions.value[0].id;
      shippingQuote.alertClass = "alert-success";
      shippingQuote.message = `1 shipping option is available for ${payload.country}.`;
      return;
    }

    const stillValid = shippingOptions.value.some((option) => option.id === selectedShippingRateId.value);
    selectedShippingRateId.value = stillValid ? selectedShippingRateId.value : null;
    shippingQuote.alertClass = "alert-warning";
    shippingQuote.message = `${shippingOptions.value.length} shipping options are available for ${payload.country}. Please choose one.`;
  } catch (error) {
    if (latestShippingLookup.value !== requestId) {
      return;
    }

    selectedShippingRateId.value = null;
    shippingOptions.value = [];
    shippingQuote.shopCountryName = "";
    shippingQuote.available = false;
    shippingQuote.alertClass = "alert-danger";
    shippingQuote.message = error.message || "Unable to load shipping options.";
  } finally {
    if (latestShippingLookup.value === requestId) {
      shippingLoading.value = false;
    }
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

  if (!canPlaceOrder.value) {
    errorMessage.value = "Please choose an available shipping option before placing your order.";
    return;
  }

  submitting.value = true;
  errorMessage.value = "";

  try {
    const order = await cartStore.checkout({
      ...form,
      shippingRateId: selectedShippingRateId.value,
    });
    alert(`Order ${order.orderNumber} placed successfully.`);
    router.push("/");
  } catch (error) {
    errorMessage.value = error.message || "Failed to place order.";
  } finally {
    submitting.value = false;
  }
};

const formatDistanceRange = (option) => {
  if (option.maxDistanceKm === null) {
    return `${Number(option.minDistanceKm).toLocaleString()}+ km distance band`;
  }

  return `${Number(option.minDistanceKm).toLocaleString()}-${Number(option.maxDistanceKm).toLocaleString()} km distance band`;
};

watch(
  () => form.country,
  (country) => {
    fetchShippingOptions(country);
  },
);

onMounted(() => {
  fetchShippingOptions(form.country);
});
</script>

<style scoped>
.location-panel {
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(11, 11, 12, 0.03), rgba(188, 154, 109, 0.08));
}

.shipping-options-panel {
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(11, 11, 12, 0.03), rgba(255, 255, 255, 0.92));
}

.shipping-origin {
  font-size: 0.8rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.shipping-option-list {
  display: grid;
  gap: 0.85rem;
}

.shipping-option {
  display: flex;
  gap: 0.9rem;
  align-items: flex-start;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.78);
  cursor: pointer;
}

.shipping-option--active {
  border-color: rgba(11, 11, 12, 0.28);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.shipping-option__body {
  flex: 1;
}
</style>
