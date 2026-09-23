<template>
  <div class="checkout-page py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="surface-elevated p-4 p-md-5">
            <p class="section-kicker mb-3">Secure Checkout</p>
            <h1 class="mb-4">Complete Your Order</h1>

            <form ref="checkoutFormRef" @submit.prevent>
              <div class="location-panel p-3 p-md-4 mb-4">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
                  <div>
                    <h2 class="h5 mb-2">Use your current location</h2>
                    <p class="text-muted mb-0 small">
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

                <div v-if="locationMessage" class="alert mb-0 mt-3" :class="locationAlertClass">
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

              <div class="shipping-options-panel p-3 p-md-4 mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                  <div>
                    <h2 class="h5 mb-2">Shipping Options</h2>
                    <p class="text-muted mb-0 small">
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

              <div v-if="resumeLoading" class="alert alert-info mt-4 mb-0">
                Loading your pending order… please wait.
              </div>

              <div v-if="errorMessage" class="alert alert-danger mt-4 mb-0">
                {{ errorMessage }}
              </div>

            </form>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="surface-elevated p-4">
            <h4 class="mb-4">Order Summary</h4>
            <div
              v-for="item in cartStore.items"
              :key="`${item.id}-${item.size}-${item.color}`"
              class="d-flex justify-content-between align-items-start mb-3"
            >
              <div>
                <div class="fw-semibold">{{ item.name }}</div>
                <div class="text-muted small">{{ item.size }} / {{ item.color }} / Qty: {{ item.quantity }}</div>
              </div>
              <div class="fw-semibold">${{ (item.price * item.quantity).toLocaleString() }}</div>
            </div>

            <hr />

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Subtotal</span>
              <span class="fw-semibold">${{ cartStore.subtotal.toLocaleString() }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Shipping</span>
              <span class="fw-semibold">{{ shippingSummaryLabel }}</span>
            </div>
            <div v-if="cartStore.discount" class="d-flex justify-content-between mb-2" style="color: var(--success);">
              <span>Discount</span>
              <span>-${{ cartStore.discount.toLocaleString() }}</span>
            </div>
            <div class="d-flex justify-content-between fs-5 mt-4 pt-3" style="border-top: 1px solid var(--border-black);">
              <strong>Total</strong>
              <strong>${{ totalWithShipping.toLocaleString() }}</strong>
            </div>

            <button
              type="button"
              class="btn btn-luxury w-100 mt-4"
              :disabled="processingPayment || !canPlaceOrder"
              @click="processPayment"
            >
              {{ processingPayment ? "Processing Payment..." : "Process Payment" }}
            </button>
          </div>
        </div>
      </div>

      <CheckoutPaymentModal
        :show="showPaymentModal"
        :email="form.email"
        :item-count="pendingItemCount ?? cartStore.totalItems"
        :total="pendingPayPalTotal ?? totalWithShipping"
        :payment-error="paymentErrorMessage"
        :invoice-email="pendingInvoiceEmail"
        :invoice-sent="pendingInvoiceSent"
        :paypal-loading="paypalLoading"
        :paypal-error="paypalErrorMessage"
        :paypal-enabled="paypalEnabled"
        :submitting="submitting"
        @close="closePaymentModal"
      />

      <div
        v-if="showOrderSuccessModal"
        class="modal-overlay"
        @click.self="closeOrderSuccessModal"
      >
        <div class="modal-dialog-box surface-elevated">
          <div class="modal-icon" style="background: linear-gradient(145deg, rgba(40, 167, 69, 0.16), rgba(254, 181, 17, 0.12)); color: var(--success);">
            <i class="bi bi-check2-circle"></i>
          </div>
          <p class="section-kicker mb-2">Payment Successful</p>
          <h2 class="modal-title">Your order has been placed</h2>
          <p class="modal-message">{{ orderSuccessMessage }}</p>
          <div v-if="orderInvoiceEmail" class="order-invoice-notice mt-3">
            <i class="bi bi-envelope-check"></i>
            <span>Invoice and order details sent to <strong>{{ orderInvoiceEmail }}</strong> via <strong>PayPal</strong>.</span>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-luxury" @click="closeOrderSuccessModal">
              Continue
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import CheckoutPaymentModal from "../components/CheckoutPaymentModal.vue";
import { api } from "../lib/api";
import { getBrowserLocation, lookupLocationByIp, reverseGeocode } from "../lib/location";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { getAuthToken } from "../lib/api";

const route = useRoute();
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
const paypalRenderSeq = ref(0);
const resumeLoading = ref(false);
const showOrderSuccessModal = ref(false);
const showPaymentModal = ref(false);
const selectedPaymentMethod = ref("paypal");
const processingPayment = ref(false);
const paymentErrorMessage = ref("");
const pendingPayPalOrderId = ref(null);
const pendingPayPalTotal = ref(null);
const pendingInvoiceEmail = ref("");
const pendingInvoiceSent = ref(false);
const pendingItemCount = ref(null);
const pendingPayloadSnapshot = ref(null);
const orderSuccessMessage = ref("");
const orderInvoiceEmail = ref("");
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

const isAuthenticated = computed(() => Boolean(getAuthToken()));

const selectedShippingOption = computed(() =>
  shippingOptions.value.find((option) => option.id === selectedShippingRateId.value) || null,
);
const shippingAmount = computed(() => selectedShippingOption.value?.shippingCost ?? 0);
const shippingSummaryLabel = computed(() => {
  if (shippingLoading.value) return "Loading...";
  if (selectedShippingOption.value) return `$${Number(selectedShippingOption.value.shippingCost).toLocaleString()}`;
  if (form.country.trim() === "") return "Select country";
  if (!shippingQuote.available) return "Unavailable";
  return "Choose option";
});
const totalWithShipping = computed(() => cartStore.total + shippingAmount.value);
const paymentMethodLabel = computed(() => "PayPal");
const checkoutPayload = computed(() => ({
  ...form,
  paymentMethod: selectedPaymentMethod.value,
  paymentMethodLabel: paymentMethodLabel.value,
  shippingRateId: selectedShippingRateId.value,
  shippingCost: shippingAmount.value,
  shippingTierName: selectedShippingOption.value?.tierName || '',
  shopCountryName: shippingQuote.shopCountryName || '',
}));
const canPlaceOrder = computed(() => {
  if (!form.country.trim() || shippingLoading.value) return false;
  if (!shippingQuote.available) return false;
  return selectedShippingRateId.value !== null;
});

const applyLocationToForm = (location) => {
  if (!form.address && location.address) form.address = location.address;
  if (location.city) form.city = location.city;
  if (location.country) form.country = location.country;
  if (location.postalCode) form.postalCode = location.postalCode;
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
  if (!normalizedCountry) { resetShippingQuote(); return; }

  shippingLoading.value = true;
  errorMessage.value = "";

  try {
    const payload = await api.get(`/shipping-options?country=${encodeURIComponent(normalizedCountry)}`);
    if (latestShippingLookup.value !== requestId) return;

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
    if (latestShippingLookup.value !== requestId) return;
    selectedShippingRateId.value = null;
    shippingOptions.value = [];
    shippingQuote.shopCountryName = "";
    shippingQuote.available = false;
    shippingQuote.alertClass = "alert-danger";
    shippingQuote.message = error.message || "Unable to load shipping options.";
  } finally {
    if (latestShippingLookup.value === requestId) shippingLoading.value = false;
  }
};

const fillFromIpFallback = async (message) => {
  const location = await lookupLocationByIp();
  applyLocationToForm(location);
  locationAlertClass.value = "alert-warning";
  locationMessage.value = message || "We used your IP address to estimate your area. Please review the shipping details.";
};

const requestLocation = async () => {
  locating.value = true;
  locationAlertClass.value = "alert-secondary";
  locationMessage.value = "Waiting for your browser location permission...";

  try {
    const position = await getBrowserLocation();
    const location = await reverseGeocode(position.coords.latitude, position.coords.longitude);
    applyLocationToForm(location);
    locationAlertClass.value = "alert-success";
    locationMessage.value = "Location access granted. We filled the available shipping details from your current area.";
  } catch (error) {
    const deniedPermission = error?.code === 1 || /denied|permission/i.test(error?.message || "");
    try {
      await fillFromIpFallback(
        deniedPermission
          ? "Precise location was not allowed, so we estimated your area from your IP address."
          : "We could not get your precise location, so we estimated your area from your IP address.",
      );
    } catch {
      locationAlertClass.value = "alert-danger";
      locationMessage.value = "Location lookup is unavailable right now. You can still enter the shipping address manually.";
    }
  } finally {
    locating.value = false;
  }
};

const formatDistanceRange = (option) => {
  if (option.maxDistanceKm === null) return `${Number(option.minDistanceKm).toLocaleString()}+ km distance band`;
  return `${Number(option.minDistanceKm).toLocaleString()}-${Number(option.maxDistanceKm).toLocaleString()} km distance band`;
};

const validateFormOnly = () => {
  if (checkoutFormRef.value && !checkoutFormRef.value.reportValidity()) {
    errorMessage.value = "Please complete all required checkout fields before continuing to PayPal.";
    return false;
  }
  if (!canPlaceOrder.value) { errorMessage.value = "Please choose an available shipping option before placing your order."; return false; }
  errorMessage.value = "";
  return true;
};

const validateCheckoutBeforePayment = () => {
  paypalResultMessage.value = "";
  if (!validateFormOnly()) return false;

  // Check cart based on authentication status
  if (!cartStore.items.length && !pendingPayloadSnapshot.value) {
    errorMessage.value = "Your cart is empty. Please add items to your cart before checkout.";
    return false;
  }

  return true;
};

const closeOrderSuccessModal = () => {
  showOrderSuccessModal.value = false;
  orderInvoiceEmail.value = "";
  router.push("/");
};

const destroyPayPalButtons = () => {
  // Invalidate any in-flight PayPal render so a stale async render can
  // never wipe the container out from under the current one (this was the
  // "Detected container element removed from DOM" error).
  paypalRenderSeq.value += 1;
  const container = document.querySelector("#checkout-paypal-button-container");
  if (!container) return;

  container.innerHTML = "";
  paypalButtonsRendered.value = false;
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
  paymentErrorMessage.value = "";
  paypalErrorMessage.value = "";
  paypalResultMessage.value = "";
  destroyPayPalButtons();
};

const processPayment = async () => {
  // Reopen case: cart was already cleared after the invoice email was
  // sent, but the pending PayPal order still awaits payment.
  if (!cartStore.items.length && pendingPayPalOrderId.value && pendingPayloadSnapshot.value) {
    if (!validateFormOnly()) return;
    selectedPaymentMethod.value = "paypal";
    destroyPayPalButtons();
    showPaymentModal.value = true;
    return;
  }

  if (!validateCheckoutBeforePayment()) return;

  // Create the pending order + send the "awaiting payment" invoice email
  // NOW (on Process Payment click), then open the PayPal modal.
  // The PayPal Buttons reuse this pre-created order instead of creating
  // a second one, so there are no duplicate DB orders.
  processingPayment.value = true;
  paymentErrorMessage.value = "";
  paypalErrorMessage.value = "";
  paypalResultMessage.value = "";

  try {
    const currentTotal = totalWithShipping.value;
    const needsNewOrder =
      !pendingPayPalOrderId.value ||
      pendingPayPalTotal.value !== currentTotal ||
      pendingInvoiceEmail.value !== form.email;

    if (needsNewOrder) {
      // Snapshot the order payload BEFORE clearing: capture needs these
      // exact items/totals after the cart is emptied below.
      const payloadToSend = cartStore.buildOrderPayload(checkoutPayload.value);
      const itemCountToSend = cartStore.totalItems;
      const orderData = await cartStore.createPayPalOrder(checkoutPayload.value);
      if (!orderData?.id) {
        throw new Error("Could not initiate PayPal checkout. Please try again.");
      }
      pendingPayPalOrderId.value = orderData.id;
      pendingPayPalTotal.value = currentTotal;
      pendingInvoiceEmail.value = form.email;
      pendingInvoiceSent.value = Boolean(orderData.pendingEmailSent);
      pendingItemCount.value = itemCountToSend;
      pendingPayloadSnapshot.value = payloadToSend;

      // Invoice email sent — empty the cart now, for guests (localStorage)
      // and logged-in users (DB cart via API + backend cleared it when the
      // pending order was created).
      cartStore.clearCart();
    }

    selectedPaymentMethod.value = "paypal";
    destroyPayPalButtons();
    showPaymentModal.value = true;
  } catch (error) {
    paymentErrorMessage.value = "";
    errorMessage.value = error.message || "Could not process payment. Please try again.";
  } finally {
    processingPayment.value = false;
  }
};

const loadPayPalSdk = (clientId, currencyCode) =>
  new Promise((resolve, reject) => {
    const existingScript = document.querySelector("#paypal-sdk-script");
    if (existingScript) {
      if (window.paypal?.Buttons) { resolve(window.paypal); return; }
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
  const containerSelector = "#checkout-paypal-button-container";
  const mySeq = paypalRenderSeq.value + 1;
  paypalRenderSeq.value = mySeq;

  await nextTick();
  if (mySeq !== paypalRenderSeq.value) return;

  const container = document.querySelector(containerSelector);

  if (container) {
    container.innerHTML = "";
  } else {
    // Modal not in DOM (e.g. closed mid-flight) — abort quietly.
    return;
  }

  if (!paypalEnabled.value || !paypalClientId.value) return;
  paypalLoading.value = true;

  try {
    await loadPayPalSdk(paypalClientId.value, paypalCurrencyCode.value);
    if (mySeq !== paypalRenderSeq.value) return;
    if (!window.paypal?.Buttons) throw new Error("PayPal SDK is unavailable.");
    if (!document.querySelector(containerSelector)) return;

    await window.paypal.Buttons({
      style: { shape: "rect", layout: "vertical", color: "gold", label: "paypal" },
      async onClick(_data, actions) {
        // Cart is already cleared once the invoice email is sent — in that
        // case the snapshot holds the order, so only the form needs to be valid.
        const valid = pendingPayloadSnapshot.value ? validateFormOnly() : validateCheckoutBeforePayment();
        if (!valid) return actions.reject();
        paypalErrorMessage.value = "";
        return actions.resolve();
      },
      async createOrder() {
        submitting.value = true;
        paypalErrorMessage.value = "";
        try {
          // Reuse the pending PayPal order created by Process Payment
          // (invoice email already sent, cart already cleared).
          if (pendingPayPalOrderId.value) {
            return pendingPayPalOrderId.value;
          }
          const orderData = await cartStore.createPayPalOrder(pendingPayloadSnapshot.value ?? checkoutPayload.value);
          if (orderData.id) {
            pendingPayPalOrderId.value = orderData.id;
            pendingPayPalTotal.value = pendingPayloadSnapshot.value?.total ?? totalWithShipping.value;
            pendingInvoiceEmail.value = form.email;
            pendingInvoiceSent.value = Boolean(orderData.pendingEmailSent);
            return orderData.id;
          }
          const errorDetail = orderData?.details?.[0];
          throw new Error(errorDetail ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})` : JSON.stringify(orderData));
        } catch (error) {
          paypalErrorMessage.value = error.message || "Could not initiate PayPal checkout.";
          throw error;
        } finally { submitting.value = false; }
      },
      async onApprove(data, actions) {
        submitting.value = true;
        paypalErrorMessage.value = "";
        paypalResultMessage.value = "";
        try {
          const { order, paypalOrder } = await cartStore.capturePayPalOrder(data.orderID, pendingPayloadSnapshot.value ?? checkoutPayload.value);
          const errorDetail = paypalOrder?.details?.[0];
          if (errorDetail?.issue === "INSTRUMENT_DECLINED") return actions.restart();
          if (errorDetail) throw new Error(`${errorDetail.description} (${paypalOrder.debug_id})`);
          if (!paypalOrder.purchase_units) throw new Error(JSON.stringify(paypalOrder));

          const transaction =
            paypalOrder.purchase_units?.[0]?.payments?.captures?.[0] ||
            paypalOrder.purchase_units?.[0]?.payments?.authorizations?.[0];

          paypalResultMessage.value = transaction
            ? `Transaction ${transaction.status}: ${transaction.id}`
            : `Order ${order.orderNumber} placed successfully.`;
          orderSuccessMessage.value = `Order ${order.orderNumber} placed successfully and paid with PayPal.`;
          // Business flow: invoice + order details emailed to the address
          // the customer filled in before clicking Process Payment.
          orderInvoiceEmail.value = order.customerEmail || form.email;
          // Pending order is now paid — clear it so the next checkout
          // creates a fresh PayPal order instead of reusing this one.
          // (Cart was already emptied when the invoice email was sent.)
          pendingPayPalOrderId.value = null;
          pendingPayPalTotal.value = null;
          pendingInvoiceSent.value = false;
          pendingItemCount.value = null;
          pendingPayloadSnapshot.value = null;
          cartStore.clearCart();
          closePaymentModal();
          showOrderSuccessModal.value = true;
        } catch (error) {
          paypalErrorMessage.value = error.message || "Sorry, your transaction could not be processed.";
        } finally { submitting.value = false; }
      },
    }).render(containerSelector);

    if (mySeq !== paypalRenderSeq.value) return;
    paypalButtonsRendered.value = true;
  } catch (error) {
    if (mySeq !== paypalRenderSeq.value) return;
    paypalErrorMessage.value = error.message || "Unable to initialize PayPal checkout.";
  } finally {
    if (mySeq === paypalRenderSeq.value) paypalLoading.value = false;
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
    if (paypalEnabled.value && showPaymentModal.value) await renderPayPalButtons();
  } catch (error) {
    paypalEnabled.value = false;
    paypalErrorMessage.value = error.message || "Unable to load PayPal checkout settings.";
  } finally { paypalLoading.value = false; }
};

/**
 * Resume a pending order from the email "Pay with PayPal / Card" link:
 * /checkout?resumePayment=1&order=AUB-...
 * Fetches the order, prefills the form + shipping, primes the PayPal
 * snapshot, then opens the payment modal. Works logged in or not.
 */
const resumePendingOrder = async (orderNumber) => {
  resumeLoading.value = true;
  errorMessage.value = "";
  try {
    const { order, canPay } = await api.get(`/orders/resume?order=${encodeURIComponent(orderNumber)}`);

    if (!order) throw new Error("Order not found or expired.");

    if (!canPay || (order.status ?? "") !== "pending") {
      errorMessage.value =
        (order.status ?? "") === "paid"
          ? `Order ${order.orderNumber} has already been paid. Thank you!`
          : `Order ${order.orderNumber} can no longer be paid (status: ${order.status}). Please place a new order.`;
      return;
    }

    // Prefill contact + shipping form from the order.
    const nameParts = String(order.customerName || "").trim().split(/\s+/).filter(Boolean);
    form.firstName = nameParts[0] || "";
    form.lastName = nameParts.slice(1).join(" ") || "";
    form.email = order.customerEmail || "";
    form.address = order.shippingAddress || "";
    form.city = order.shippingCity || "";
    form.country = order.shippingCountry || "";
    form.postalCode = order.shippingPostalCode || "";
    shippingQuote.shopCountryName = order.shippingShopCountry || "";

    // Reload shipping options for the order country, then re-select the
    // same tier the customer originally chose.
    await fetchShippingOptions(form.country);
    const match = shippingOptions.value.find((o) => o.tierName === order.shippingTierName);
    if (match) selectedShippingRateId.value = match.id;

    // Prime the PayPal snapshot from the saved order lines.
    const snapshotItems = (order.items || []).map((item) => ({
      product_id: item.productId,
      name: item.name,
      image: item.image,
      quantity: item.quantity,
      size: item.size,
      color: item.color,
      unit_price: item.price,
      line_total: item.lineTotal,
    }));

    // Orders created before a PayPal id existed need one attached now.
    let paypalOrderId = order.paypalOrderId || "";
    if (!paypalOrderId) {
      const created = await cartStore.createPayPalOrderForExisting(order.orderNumber);
      paypalOrderId = created?.id || "";
      if (!paypalOrderId) throw new Error("Could not initiate PayPal checkout for this order.");
    }

    pendingPayPalOrderId.value = paypalOrderId;
    pendingPayPalTotal.value = Number(order.total) || 0;
    pendingInvoiceEmail.value = order.customerEmail || "";
    pendingInvoiceSent.value = true;
    pendingItemCount.value = snapshotItems.reduce((n, i) => n + (Number(i.quantity) || 0), 0);
    pendingPayloadSnapshot.value = {
      firstName: form.firstName,
      lastName: form.lastName,
      email: form.email,
      address: form.address,
      city: form.city,
      country: form.country,
      postalCode: form.postalCode,
      paymentMethod: "paypal",
      paymentMethodLabel: "PayPal",
      shippingRateId: selectedShippingRateId.value,
      shippingCost: Number(order.shipping) || 0,
      shippingTierName: order.shippingTierName || "",
      shopCountryName: order.shippingShopCountry || "",
      items: snapshotItems,
      subtotal: Number(order.subtotal) || 0,
      discount: Number(order.discount) || 0,
      shipping_cost: Number(order.shipping) || 0,
      total: Number(order.total) || 0,
    };

    if (selectedShippingRateId.value === null && shippingOptions.value.length > 0) {
      errorMessage.value = "Please choose an available shipping option before paying.";
      return;
    }

    selectedPaymentMethod.value = "paypal";
    showPaymentModal.value = true;
  } catch (error) {
    errorMessage.value = error.message || "Could not load your order. Please try again or place a new order.";
  } finally {
    resumeLoading.value = false;
  }
};

watch(showPaymentModal, async (isOpen) => {
  if (isOpen) {
    // Teleported modal mounts async — wait a tick so the PayPal
    // container exists before rendering buttons into it.
    await nextTick();
    destroyPayPalButtons();
    await initPayPalCheckout();
  }
});

watch(selectedPaymentMethod, async (method) => {
  if (!showPaymentModal.value) return;

  if (method !== "paypal") {
    destroyPayPalButtons();
    return;
  }

  await nextTick();
  destroyPayPalButtons();
  await initPayPalCheckout();
});

watch(() => form.country, (country) => { fetchShippingOptions(country); });

// Watch for authentication changes to refresh cart
watch(() => authStore.isAuthenticated, (isAuth) => {
  if (isAuth) {
    cartStore.refreshFromApi();
  }
});

onMounted(async () => {
  fetchShippingOptions(form.country);

  const shouldResumePayment = [
    route.query.resumePayment,
    route.query.resume,
  ].some((value) => value === "1" || value === "true" || value === "yes");

  if (shouldResumePayment) {
    selectedPaymentMethod.value = "paypal";
    paymentErrorMessage.value = "";

    const resumeOrderNumber = String(route.query.order || "").trim();
    if (resumeOrderNumber) {
      // Email pay-link flow: load the order first; the watcher opens +
      // renders PayPal once showPaymentModal flips (no direct init here,
      // otherwise two concurrent renders fight over the container).
      await resumePendingOrder(resumeOrderNumber);
      return;
    }

    showPaymentModal.value = true;
    return;
  }

  initPayPalCheckout();
});
</script>

<style scoped>
.location-panel {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.06), rgba(254, 181, 17, 0.18));
}

.shipping-options-panel {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-md);
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
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.8);
  cursor: pointer;
  transition: border-color 220ms ease, box-shadow 220ms ease;
}

.shipping-option:hover {
  border-color: rgba(77, 16, 24, 0.2);
}

.shipping-option--active {
  border-color: rgba(77, 16, 24, 0.28);
  box-shadow: 0 12px 24px rgba(77, 16, 24, 0.12);
}

.shipping-option__body { flex: 1; }

.paypal-button-container--busy {
  opacity: 0.72;
  pointer-events: none;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  overflow-y: auto;
  overscroll-behavior: contain;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.55), rgba(20, 10, 12, 0.72)),
    radial-gradient(circle at 50% 0%, rgba(254, 181, 17, 0.16), transparent 42%),
    radial-gradient(circle at 85% 100%, rgba(40, 167, 69, 0.1), transparent 40%);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: overlayFade 0.22s ease;
}

@keyframes overlayFade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.payment-overlay {
  padding: clamp(0.75rem, 3vw, 1.5rem);
}

.payment-modal-box {
  width: min(100%, 620px);
  padding: 0;
  overflow: hidden;
  border: 1px solid rgba(212, 175, 55, 0.28);
  border-radius: 1.5rem;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 251, 235, 0.98));
  box-shadow:
    0 32px 80px rgba(20, 10, 12, 0.28),
    0 2px 0 rgba(212, 175, 55, 0.35) inset;
}

.payment-lux {
  position: relative;
  display: flex;
  flex-direction: column;
  max-height: min(90vh, 760px);
  max-height: min(90dvh, 760px);
}

.payment-lux__glow {
  position: absolute;
  inset: 0 0 auto 0;
  height: 120px;
  pointer-events: none;
  background:
    linear-gradient(135deg, rgba(77, 16, 24, 0.1), transparent 45%),
    linear-gradient(315deg, rgba(212, 175, 55, 0.22), transparent 55%);
}

.payment-lux__header {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-shrink: 0;
  padding: 1.4rem 1.4rem 1rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
}

.payment-lux__subtitle {
  margin-top: 0.35rem;
  font-size: 0.82rem;
  letter-spacing: 0.02em;
  color: var(--ink-muted);
}

.payment-lux__subtitle i {
  color: var(--success);
  margin-right: 0.3rem;
}

.payment-lux__close {
  flex-shrink: 0;
  width: 2.4rem;
  height: 2.4rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(77, 16, 24, 0.12);
  background: rgba(255, 255, 255, 0.8);
  transition: transform 180ms ease, box-shadow 180ms ease;
}

.payment-lux__close:hover {
  transform: rotate(90deg);
  box-shadow: 0 8px 20px rgba(77, 16, 24, 0.14);
}

.payment-lux__summary {
  flex-shrink: 0;
  display: grid;
  grid-template-columns: 1.4fr 0.6fr 1fr;
  gap: 0.75rem;
  margin: 1rem 1.4rem 0;
  padding: 0.85rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.04), rgba(254, 181, 17, 0.12));
}

.payment-lux__summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.payment-lux__summary-label {
  font-size: 0.68rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.payment-lux__summary-value {
  font-size: 0.95rem;
  color: var(--ink);
  white-space: nowrap;
}

.payment-lux__summary-value--truncate {
  overflow: hidden;
  text-overflow: ellipsis;
}

.payment-lux__summary-item--total {
  text-align: right;
}

.payment-lux__total {
  font-family: Georgia, serif;
  font-size: 1.25rem;
  letter-spacing: 0.01em;
  background: linear-gradient(135deg, #4d1018, #8a6d1c);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.payment-lux__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 1rem 1.4rem 1.2rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(77, 16, 24, 0.3) transparent;
}

.payment-lux__body::-webkit-scrollbar {
  width: 8px;
}

.payment-lux__body::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(77, 16, 24, 0.35), rgba(212, 175, 55, 0.5));
}

.payment-lux__body::-webkit-scrollbar-track {
  background: transparent;
}

.payment-method-switch {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  padding: 0.4rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(77, 16, 24, 0.03);
}

.payment-method-button {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border: 1px solid transparent;
  border-radius: 0.8rem;
  background: transparent;
  color: var(--ink-soft);
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.02em;
  padding: 0.95rem 1rem;
  transition: all 220ms ease;
}

.payment-method-button i {
  font-size: 1.25rem;
  color: #003087;
}

.payment-method-button.active {
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.07), rgba(254, 181, 17, 0.22));
  border-color: rgba(212, 175, 55, 0.4);
  color: var(--ink);
  box-shadow: 0 8px 20px rgba(77, 16, 24, 0.08);
}

.payment-lux__badge {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  color: #4d1018;
  background: linear-gradient(135deg, #feb511, #f3d27a);
  box-shadow: 0 4px 12px rgba(254, 181, 17, 0.4);
}

.payment-lux__methods {
  margin-bottom: 0.9rem;
}

.payment-lux__secure-note {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.8rem;
  line-height: 1.5;
  color: var(--ink-muted);
  padding: 0 0.35rem 0.15rem;
}

.payment-lux__secure-note i {
  color: var(--success);
  margin-top: 0.15rem;
}

.payment-lux__invoice {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  border-radius: 1rem;
  padding: 0.85rem 1rem;
  font-size: 0.9rem;
  line-height: 1.5;
  border: 1px solid;
}

.payment-lux__invoice i {
  font-size: 1.25rem;
  margin-top: 0.1rem;
}

.payment-lux__invoice--sent {
  color: #1e7e34;
  background: linear-gradient(135deg, #e8f5e9, #f4fbf4);
  border-color: rgba(40, 167, 69, 0.32);
}

.payment-lux__invoice--pending {
  color: #7a5b00;
  background: linear-gradient(135deg, #fff8e1, #fffdf3);
  border-color: rgba(254, 181, 17, 0.45);
}

.payment-lux__paypal {
  min-height: 90px;
}

.payment-lux__skeleton-line {
  height: 0.8rem;
  width: 55%;
  margin: 0 auto 0.9rem;
  border-radius: 999px;
  background: linear-gradient(90deg, #eee 25%, #f7f7f7 50%, #eee 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

.payment-lux__skeleton-btn {
  height: 3rem;
  border-radius: 0.8rem;
  margin-bottom: 0.7rem;
  background: linear-gradient(90deg, #ffc439 25%, #ffd970 50%, #ffc439 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

.payment-lux__skeleton-btn--dark {
  background: linear-gradient(90deg, #2c2e2f 25%, #4a4d4f 50%, #2c2e2f 75%);
  background-size: 200% 100%;
}

@keyframes shimmer {
  from { background-position: 200% 0; }
  to { background-position: -200% 0; }
}

.payment-lux__footer {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  padding: 0.9rem 1.4rem 1.2rem;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  background: rgba(255, 255, 255, 0.7);
}

.payment-lux__assurance {
  font-size: 0.8rem;
  color: var(--ink-muted);
}

.payment-lux__assurance i {
  color: var(--success);
}

.payment-lux__cancel {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ink-soft);
  text-decoration: none;
  padding: 0.3rem 0.4rem;
}

.payment-lux__cancel:hover {
  color: var(--ink);
  text-decoration: underline;
}

.card-payment-panel {
  text-align: left;
}

.modal-dialog-box {
  width: min(100%, 480px);
  padding: 2rem;
  border: 1px solid rgba(40, 167, 69, 0.16);
  border-radius: var(--radius-lg);
  text-align: center;
  animation: modalIn 0.25s ease;
  max-height: min(90vh, 720px);
  max-height: min(90dvh, 720px);
  overflow-y: auto;
  overscroll-behavior: contain;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-icon {
  width: 4.25rem;
  height: 4.25rem;
  margin: 0 auto 1rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  font-size: 1.85rem;
}

.modal-title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.5rem, 2vw, 1.85rem);
}

.modal-message {
  margin: 0 auto;
  max-width: 28rem;
  color: var(--ink-soft);
  line-height: 1.6;
}

.modal-actions {
  margin-top: 1.75rem;
  display: flex;
  justify-content: center;
}

.order-invoice-notice {
  display: flex;
  gap: 0.6rem;
  align-items: flex-start;
  text-align: left;
  background: #e8f5e9;
  border: 1px solid rgba(40, 167, 69, 0.3);
  border-radius: var(--radius-md);
  padding: 0.8rem 1rem;
  color: var(--ink-soft);
  font-size: 0.92rem;
  line-height: 1.5;
}

.order-invoice-notice i {
  color: var(--success);
  font-size: 1.1rem;
  margin-top: 0.1rem;
}

@media (max-width: 575.98px) {
  .modal-overlay,
  .payment-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .payment-modal-box,
  .payment-lux {
    width: 100%;
    max-height: 94vh;
    max-height: 94dvh;
    border-radius: 1.25rem 1.25rem 0 0;
  }

  .payment-lux__header,
  .payment-lux__body,
  .payment-lux__footer {
    padding-left: 1.1rem;
    padding-right: 1.1rem;
  }

  .payment-lux__summary {
    grid-template-columns: 1fr 1fr;
    margin-left: 1.1rem;
    margin-right: 1.1rem;
  }

  .payment-lux__summary-item--total {
    grid-column: 1 / -1;
    text-align: left;
    border-top: 1px dashed rgba(77, 16, 24, 0.15);
    padding-top: 0.6rem;
  }

  .payment-method-switch {
    grid-template-columns: 1fr;
  }

  .payment-lux__footer {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .payment-lux__assurance {
    order: 2;
  }

  .payment-lux__cancel {
    order: 1;
    width: 100%;
    padding: 0.7rem;
    border: 1px solid rgba(77, 16, 24, 0.12);
    border-radius: 0.8rem;
  }

  .modal-dialog-box { padding: 1.5rem; }
  .payment-modal-box { padding: 0; }
  .modal-actions .btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
  .modal-overlay,
  .modal-dialog-box,
  .payment-lux__close,
  .payment-lux__skeleton-line,
  .payment-lux__skeleton-btn {
    animation: none;
    transition: none;
  }
}
</style>