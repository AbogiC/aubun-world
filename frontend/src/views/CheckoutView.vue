<template>
  <div class="checkout-page py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="surface p-4 p-md-5">
            <p class="section-kicker mb-3">Secure Checkout</p>
            <h1 class="mb-4">Complete Your Order</h1>

            <form ref="checkoutFormRef" @submit.prevent>
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

              <div class="surface-subtle p-3 p-md-4 mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                  <div>
                    <h2 class="h5 mb-2">Pay with PayPal</h2>
                    <p class="text-muted mb-0">
                      Complete checkout with the PayPal flow from your example: create order first, then capture payment.
                    </p>
                  </div>
                  <div v-if="paypalLoading" class="text-muted small">Loading PayPal...</div>
                </div>

                <div v-if="paypalErrorMessage" class="alert alert-danger mt-3 mb-0">
                  {{ paypalErrorMessage }}
                </div>

                <div v-if="!paypalEnabled && !paypalLoading" class="alert alert-warning mt-3 mb-0">
                  PayPal checkout is not configured yet. Add your PayPal client credentials on the backend first.
                </div>

                <div
                  v-show="paypalEnabled"
                  id="paypal-button-container"
                  class="mt-3"
                  :class="{ 'paypal-button-container--busy': submitting }"
                ></div>

                <div v-if="paypalResultMessage" class="alert alert-success mt-3 mb-0">
                  {{ paypalResultMessage }}
                </div>
              </div>
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

      <div
        v-if="showOrderSuccessModal"
        class="checkout-modal"
        @click.self="closeOrderSuccessModal"
      >
        <div class="checkout-modal__dialog surface elevated">
          <div class="checkout-modal__icon">
            <i class="bi bi-check2-circle"></i>
          </div>
          <p class="section-kicker mb-2">Payment Successful</p>
          <h2 class="checkout-modal__title">Your order has been placed</h2>
          <p class="checkout-modal__message">
            {{ orderSuccessMessage }}
          </p>
          <div class="checkout-modal__actions">
            <button
              type="button"
              class="btn btn-luxury"
              @click="closeOrderSuccessModal"
            >
              Continue
            </button>
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
const checkoutFormRef = ref(null);
const submitting = ref(false);
const errorMessage = ref("");
const locating = ref(false);
const shippingLoading = ref(false);
const paypalLoading = ref(false);
const paypalEnabled = ref(false);
const paypalClientId = ref("");
const paypalCurrencyCode = ref("USD");
const paypalErrorMessage = ref("");
const paypalResultMessage = ref("");
const paypalButtonsRendered = ref(false);
const showOrderSuccessModal = ref(false);
const orderSuccessMessage = ref("");
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
const checkoutPayload = computed(() => ({
  ...form,
  shippingRateId: selectedShippingRateId.value,
}));
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

const formatDistanceRange = (option) => {
  if (option.maxDistanceKm === null) {
    return `${Number(option.minDistanceKm).toLocaleString()}+ km distance band`;
  }

  return `${Number(option.minDistanceKm).toLocaleString()}-${Number(option.maxDistanceKm).toLocaleString()} km distance band`;
};

const validateCheckoutBeforePayment = () => {
  paypalResultMessage.value = "";

  if (checkoutFormRef.value && !checkoutFormRef.value.reportValidity()) {
    errorMessage.value = "Please complete all required checkout fields before continuing to PayPal.";
    return false;
  }

  if (!cartStore.items.length) {
    errorMessage.value = "Your cart is empty.";
    return false;
  }

  if (!canPlaceOrder.value) {
    errorMessage.value = "Please choose an available shipping option before placing your order.";
    return false;
  }

  errorMessage.value = "";
  return true;
};

const closeOrderSuccessModal = () => {
  showOrderSuccessModal.value = false;
  router.push("/");
};

const loadPayPalSdk = (clientId, currencyCode) =>
  new Promise((resolve, reject) => {
    const existingScript = document.querySelector("#paypal-sdk-script");

    if (existingScript) {
      if (window.paypal?.Buttons) {
        resolve(window.paypal);
        return;
      }

      existingScript.addEventListener("load", () => resolve(window.paypal), { once: true });
      existingScript.addEventListener("error", () => reject(new Error("Failed to load the PayPal SDK.")), { once: true });
      return;
    }

    const script = document.createElement("script");
    script.id = "paypal-sdk-script";
    script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currencyCode)}`;
    script.async = true;
    script.onload = () => resolve(window.paypal);
    script.onerror = () => reject(new Error("Failed to load the PayPal SDK."));
    document.head.appendChild(script);
  });

const renderPayPalButtons = async () => {
  if (!paypalEnabled.value || paypalButtonsRendered.value || !paypalClientId.value) {
    return;
  }

  paypalLoading.value = true;

  try {
    await loadPayPalSdk(paypalClientId.value, paypalCurrencyCode.value);

    if (!window.paypal?.Buttons) {
      throw new Error("PayPal SDK is unavailable.");
    }

    await window.paypal.Buttons({
      style: {
        shape: "rect",
        layout: "vertical",
        color: "gold",
        label: "paypal",
      },
      async onClick(_data, actions) {
        if (!validateCheckoutBeforePayment()) {
          return actions.reject();
        }

        paypalErrorMessage.value = "";
        return actions.resolve();
      },
      async createOrder() {
        submitting.value = true;
        paypalErrorMessage.value = "";

        try {
          const orderData = await cartStore.createPayPalOrder(checkoutPayload.value);

          if (orderData.id) {
            return orderData.id;
          }

          const errorDetail = orderData?.details?.[0];
          const errorMessageText = errorDetail
            ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
            : JSON.stringify(orderData);

          throw new Error(errorMessageText);
        } catch (error) {
          paypalErrorMessage.value = error.message || "Could not initiate PayPal checkout.";
          throw error;
        } finally {
          submitting.value = false;
        }
      },
      async onApprove(data, actions) {
        submitting.value = true;
        paypalErrorMessage.value = "";
        paypalResultMessage.value = "";

        try {
          const { order, paypalOrder } = await cartStore.capturePayPalOrder(data.orderID, checkoutPayload.value);
          const errorDetail = paypalOrder?.details?.[0];

          if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
            return actions.restart();
          }

          if (errorDetail) {
            throw new Error(`${errorDetail.description} (${paypalOrder.debug_id})`);
          }

          if (!paypalOrder.purchase_units) {
            throw new Error(JSON.stringify(paypalOrder));
          }

          const transaction =
            paypalOrder.purchase_units?.[0]?.payments?.captures?.[0] ||
            paypalOrder.purchase_units?.[0]?.payments?.authorizations?.[0];

          paypalResultMessage.value = transaction
            ? `Transaction ${transaction.status}: ${transaction.id}`
            : `Order ${order.orderNumber} placed successfully.`;

          orderSuccessMessage.value = `Order ${order.orderNumber} placed successfully.`;
          showOrderSuccessModal.value = true;
        } catch (error) {
          paypalErrorMessage.value = error.message || "Sorry, your transaction could not be processed.";
        } finally {
          submitting.value = false;
        }
      },
    }).render("#paypal-button-container");

    paypalButtonsRendered.value = true;
  } catch (error) {
    paypalErrorMessage.value = error.message || "Unable to initialize PayPal checkout.";
  } finally {
    paypalLoading.value = false;
  }
};

const initPayPalCheckout = async () => {
  paypalLoading.value = true;
  paypalErrorMessage.value = "";

  try {
    const config = await api.get("/orders/paypal-config");
    paypalEnabled.value = Boolean(config.enabled && config.clientId);
    paypalClientId.value = config.clientId || "";
    paypalCurrencyCode.value = config.currencyCode || "USD";

    if (paypalEnabled.value) {
      await renderPayPalButtons();
    }
  } catch (error) {
    paypalEnabled.value = false;
    paypalErrorMessage.value = error.message || "Unable to load PayPal checkout settings.";
  } finally {
    paypalLoading.value = false;
  }
};

watch(
  () => form.country,
  (country) => {
    fetchShippingOptions(country);
  },
);

onMounted(() => {
  fetchShippingOptions(form.country);
  initPayPalCheckout();
});
</script>

<style scoped>
.location-panel {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.06), rgba(254, 181, 17, 0.18));
}

.shipping-options-panel {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.06), rgba(255, 241, 184, 0.92));
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
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.78);
  cursor: pointer;
}

.shipping-option--active {
  border-color: rgba(77, 16, 24, 0.28);
  box-shadow: 0 12px 24px rgba(77, 16, 24, 0.12);
}

.shipping-option__body {
  flex: 1;
}

.paypal-button-container--busy {
  opacity: 0.72;
  pointer-events: none;
}

.checkout-modal {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.5), rgba(20, 10, 12, 0.66)),
    radial-gradient(circle at top, rgba(40, 167, 69, 0.14), transparent 38%);
  backdrop-filter: blur(6px);
}

.checkout-modal__dialog {
  width: min(100%, 480px);
  padding: 2rem;
  border: 1px solid rgba(40, 167, 69, 0.16);
  border-radius: 1.5rem;
  background:
    linear-gradient(180deg, rgba(246, 255, 250, 0.98), rgba(255, 255, 255, 0.96));
  box-shadow: 0 1.5rem 4rem rgba(43, 17, 22, 0.22);
  text-align: center;
}

.checkout-modal__icon {
  width: 4.25rem;
  height: 4.25rem;
  margin: 0 auto 1rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(40, 167, 69, 0.16), rgba(254, 181, 17, 0.16));
  color: #1a7f4d;
  font-size: 1.85rem;
}

.checkout-modal__title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.5rem, 2vw, 1.85rem);
}

.checkout-modal__message {
  margin: 0 auto;
  max-width: 28rem;
  color: var(--ink-muted);
  line-height: 1.6;
}

.checkout-modal__actions {
  margin-top: 1.75rem;
  display: flex;
  justify-content: center;
}

@media (max-width: 575.98px) {
  .checkout-modal__dialog {
    padding: 1.5rem;
  }

  .checkout-modal__actions .btn {
    width: 100%;
  }
}
</style>
