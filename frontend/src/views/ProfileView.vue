<template>
  <div class="profile-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="surface p-4 p-md-5">
            <p class="section-kicker mb-3">My Account</p>
            <h1 class="mb-4">Profile Settings</h1>

            <!-- Profile Information Section -->
            <section class="profile-section mb-5">
              <h2 class="h4 mb-3 pb-2 border-bottom">Profile Information</h2>

              <!-- Email Verification Alert -->
              <div v-if="!authStore.user?.emailVerified" class="alert alert-warning mb-4">
                <div class="d-flex align-items-start gap-3">
                  <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                  <div class="flex-grow-1">
                    <strong>Email not verified</strong>
                    <p class="mb-2 small">Please verify your email address to receive order confirmations and updates.</p>
                    <button
                      type="button"
                      class="btn btn-outline-dark btn-sm"
                      :disabled="authStore.loading || resendCooldown > 0"
                      @click="resendVerification"
                    >
                      {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Verification Email' }}
                    </button>
                    <span v-if="verificationSuccess" class="text-success small ms-2">
                      <i class="bi bi-check-circle"></i> Verification email sent
                    </span>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updateProfile">
                <div class="mb-3">
                  <label class="form-label">Full Name</label>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    class="form-control form-control-lg"
                    required
                    :disabled="profileLoading"
                  />
                </div>

                <div class="mb-4">
                  <label class="form-label">Email Address</label>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    class="form-control form-control-lg"
                    required
                    :disabled="profileLoading || authStore.user?.emailVerified"
                  />
                  <div v-if="authStore.user?.emailVerified" class="form-text text-success">
                    <i class="bi bi-check-circle-fill"></i> Verified
                    <span class="text-muted ms-1">Email address cannot be changed after verification.</span>
                  </div>
                  <div v-else class="form-text text-muted">
                    <i class="bi bi-exclamation-circle"></i> Not verified. Click "Resend Verification Email" above.
                  </div>
                </div>

                <div v-if="profileError" class="alert alert-danger mb-3">{{ profileError }}</div>

                <button type="submit" class="btn btn-luxury" :disabled="profileLoading">
                  {{ profileLoading ? "Saving..." : "Save Changes" }}
                </button>
              </form>
            </section>

            <!-- Change Password Section -->
            <section class="profile-section mb-5">
              <h2 class="h4 mb-3 pb-2 border-bottom">Change Password</h2>

              <form @submit.prevent="changePassword">
                <div class="mb-3">
                  <label class="form-label">Current Password</label>
                  <input
                    v-model="passwordForm.currentPassword"
                    type="password"
                    class="form-control form-control-lg"
                    required
                    :disabled="passwordLoading"
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label">New Password</label>
                  <input
                    v-model="passwordForm.newPassword"
                    type="password"
                    class="form-control form-control-lg"
                    minlength="6"
                    required
                    :disabled="passwordLoading"
                  />
                  <div class="form-text">Minimum 6 characters</div>
                </div>

                <div class="mb-4">
                  <label class="form-label">Confirm New Password</label>
                  <input
                    v-model="passwordForm.confirmPassword"
                    type="password"
                    class="form-control form-control-lg"
                    minlength="6"
                    required
                    :disabled="passwordLoading"
                  />
                </div>

                <div v-if="passwordError" class="alert alert-danger mb-3">{{ passwordError }}</div>
                <div v-if="passwordSuccess" class="alert alert-success mb-3">{{ passwordSuccess }}</div>

                <button type="submit" class="btn btn-luxury" :disabled="passwordLoading">
                  {{ passwordLoading ? "Updating..." : "Update Password" }}
                </button>
              </form>
            </section>

            <!-- Notification Preferences Section -->
            <section class="profile-section mb-5">
              <h2 class="h4 mb-3 pb-2 border-bottom">Notification Preferences</h2>
              <p class="text-muted small mb-4">
                Choose which updates you'd like to receive. Changes are saved automatically.
              </p>

              <div v-if="notifError" class="alert alert-danger mb-3">{{ notifError }}</div>

              <div class="notif-preferences">
                <label class="notif-pref-item" :class="{ 'notif-pref-item--disabled': notifSaving }">
                  <div class="notif-pref-info">
                    <span class="notif-pref-label">New Collection Drops</span>
                    <span class="notif-pref-desc">When new fashion costumes are added to the collection</span>
                  </div>
                  <div class="form-check form-switch notif-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :checked="notifPrefs.new_collection"
                      :disabled="notifSaving"
                      @change="togglePref('new_collection')"
                    />
                  </div>
                </label>

                <label class="notif-pref-item" :class="{ 'notif-pref-item--disabled': notifSaving }">
                  <div class="notif-pref-info">
                    <span class="notif-pref-label">New Articles & News</span>
                    <span class="notif-pref-desc">When new articles are published</span>
                  </div>
                  <div class="form-check form-switch notif-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :checked="notifPrefs.new_article"
                      :disabled="notifSaving"
                      @change="togglePref('new_article')"
                    />
                  </div>
                </label>

                <label class="notif-pref-item" :class="{ 'notif-pref-item--disabled': notifSaving }">
                  <div class="notif-pref-info">
                    <span class="notif-pref-label">Guideline Updates</span>
                    <span class="notif-pref-desc">When size guides, fit guides, or care instructions are updated</span>
                  </div>
                  <div class="form-check form-switch notif-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :checked="notifPrefs.guideline_update"
                      :disabled="notifSaving"
                      @change="togglePref('guideline_update')"
                    />
                  </div>
                </label>
              </div>
            </section>

            <!-- Shipping Address Section -->
            <section class="profile-section">
              <h2 class="h4 mb-3 pb-2 border-bottom">Shipping Address</h2>
              <p class="text-muted small mb-4">
                This address will be pre-filled during checkout for faster ordering.
              </p>

              <form @submit.prevent="updateShippingAddress">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Address</label>
                    <input
                      v-model="addressForm.address"
                      type="text"
                      class="form-control form-control-lg"
                      required
                      placeholder="Street address, apartment, suite, etc."
                      :disabled="addressLoading"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input
                      v-model="addressForm.city"
                      type="text"
                      class="form-control form-control-lg"
                      required
                      :disabled="addressLoading"
                    />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <input
                      v-model="addressForm.country"
                      type="text"
                      class="form-control form-control-lg"
                      required
                      :disabled="addressLoading"
                    />
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Postal Code</label>
                    <input
                      v-model="addressForm.postalCode"
                      type="text"
                      class="form-control form-control-lg"
                      required
                      :disabled="addressLoading"
                    />
                  </div>
                </div>

                <div v-if="addressError" class="alert alert-danger mt-3 mb-3">{{ addressError }}</div>
                <div v-if="addressSuccess" class="alert alert-success mt-3 mb-3">{{ addressSuccess }}</div>

                <button type="submit" class="btn btn-luxury mt-3" :disabled="addressLoading">
                  {{ addressLoading ? "Saving..." : "Save Address" }}
                </button>
              </form>
            </section>

            <!-- My Vouchers Section -->
            <section class="profile-section mt-5">
              <h2 class="h4 mb-3 pb-2 border-bottom">My Vouchers</h2>
              <div v-if="vouchersLoading" class="text-muted">Loading vouchers...</div>
              <div v-else-if="myVouchers.length === 0" class="text-muted small">
                No vouchers yet. New accounts automatically receive 1 free welcome voucher.
              </div>
              <div v-else class="voucher-list">
                <div v-for="voucher in myVouchers" :key="voucher.voucherId" class="voucher-card">
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                      <div class="voucher-code-large">{{ voucher.code }}</div>
                      <div class="fw-semibold mt-1">{{ voucher.discountPercent }}% off your order</div>
                      <div class="text-muted small">
                        <span v-if="voucher.isUsed">Already used</span>
                        <span v-else-if="voucher.isExpired">Expired on {{ formatDate(voucher.expiresAt) }}</span>
                        <span v-else>Valid until {{ formatDate(voucher.expiresAt) }}</span>
                      </div>
                    </div>
                    <span
                      class="badge"
                      :class="voucher.isValid ? 'text-bg-success' : 'text-bg-secondary'"
                    >
                      {{ voucher.isValid ? "Active" : voucher.isUsed ? "Used" : "Expired" }}
                    </span>
                  </div>
                  <div class="d-flex gap-2 mt-3">
                    <button
                      type="button"
                      class="btn btn-outline-dark btn-sm"
                      :disabled="!voucher.isValid"
                      @click="copyVoucher(voucher.code)"
                    >
                      <i class="bi bi-clipboard"></i> {{ copiedCode === voucher.code ? "Copied!" : "Copy Code" }}
                    </button>
                    <router-link to="/cart" class="btn btn-dark btn-sm" :class="{ disabled: !voucher.isValid }">
                      Use in Bag
                    </router-link>
                  </div>
                  <div class="text-muted small mt-2">Enter this code in your Shopping Bag to apply the discount (one-time use).</div>
                </div>
              </div>
            </section>

            <!-- My Orders Section -->
            <section class="profile-section mt-5" v-if="authStore.isAuthenticated">
              <h2 class="h4 mb-3 pb-2 border-bottom">My Orders</h2>
              <div v-if="ordersLoading" class="text-muted">Loading orders...</div>
              <div v-else-if="ordersError" class="alert alert-danger">
                {{ ordersError }}
                <button type="button" class="btn btn-outline-dark btn-sm ms-2" @click="fetchOrders">
                  Retry
                </button>
              </div>
              <div v-else-if="orders.length === 0" class="text-muted">No orders found.</div>
              <div v-else class="order-list">
                <div v-for="order in orders" :key="order.id" class="order-card surface-elevated p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <span class="fw-semibold">{{ order.orderNumber || `#${order.id}` }}</span>
                      <span class="text-muted small ms-2">{{ formatDate(order.createdAt) }}</span>
                    </div>
                    <span class="fw-semibold">${{ formatCurrency(order.total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-muted small">{{ order.customerName || "-" }}</span>
                    <span class="text-muted small">{{ order.shippingCity || "-" }}, {{ order.shippingCountry || "-" }}</span>
                  </div>
                  <div class="small text-muted mt-1">{{ (order.items || []).length }} item(s)</div>
                  <div class="order-status-line mt-2">
                    <span class="badge text-bg-dark">{{ formatOrderStatus(order.status) }}</span>
                    <span class="text-muted small ms-2">{{ orderStatusMeaning(order.status) }}</span>
                  </div>
                  <div v-if="order.courier || order.trackingNumber" class="order-tracking-line mt-2">
                    <span class="small"><strong>Courier:</strong> {{ order.courier || "-" }}</span>
                    <span class="small ms-3"><strong>Tracking ID:</strong> {{ order.trackingNumber || "-" }}</span>
                    <div class="text-muted small mt-1">Use the tracking ID on the courier website to track your parcel.</div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { api } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { useNotificationStore } from "../stores/notifications";

const authStore = useAuthStore();
const route = useRoute();
const notificationStore = useNotificationStore();

// Notification preferences
const notifPrefs = reactive({
  new_collection: true,
  new_article: true,
  guideline_update: true,
});
const notifSaving = ref(false);
const notifError = ref("");

const togglePref = async (type) => {
  notifPrefs[type] = !notifPrefs[type];
  notifSaving.value = true;
  notifError.value = "";
  try {
    await notificationStore.updateSubscriptions({ ...notifPrefs });
  } catch (error) {
    notifError.value = error.message || "Failed to update preferences.";
    notifPrefs[type] = !notifPrefs[type];
  } finally {
    notifSaving.value = false;
  }
};

const loadNotificationPrefs = async () => {
  const subs = await notificationStore.fetchSubscriptions();
  if (subs) {
    Object.assign(notifPrefs, subs);
  }
};

// Profile form
const profileForm = reactive({
  name: "",
  email: "",
});
const profileLoading = ref(false);
const profileError = ref("");

// Password form
const passwordForm = reactive({
  currentPassword: "",
  newPassword: "",
  confirmPassword: "",
});
const passwordLoading = ref(false);
const passwordError = ref("");
const passwordSuccess = ref("");

// Address form
const addressForm = reactive({
  address: "",
  city: "",
  country: "",
  postalCode: "",
});
const addressLoading = ref(false);
const addressError = ref("");
const addressSuccess = ref("");

// Email verification
const resendCooldown = ref(0);
const verificationSuccess = ref(false);

// Orders
const orders = ref([]);
const ordersLoading = ref(false);
const ordersError = ref("");

// My vouchers (free welcome gift for new accounts)
const myVouchers = ref([]);
const vouchersLoading = ref(false);
const copiedCode = ref("");

const fetchMyVouchers = async () => {
  vouchersLoading.value = true;
  try {
    const payload = await api.get("/my-vouchers");
    myVouchers.value = Array.isArray(payload.vouchers) ? payload.vouchers : [];
  } catch {
    myVouchers.value = [];
  } finally {
    vouchersLoading.value = false;
  }
};

const copyVoucher = async (code) => {
  try {
    await navigator.clipboard.writeText(code);
  } catch {
    const input = document.createElement("input");
    input.value = code;
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    document.body.removeChild(input);
  }
  copiedCode.value = code;
  setTimeout(() => { copiedCode.value = ""; }, 2000);
};

const fetchOrders = async () => {
  ordersLoading.value = true;
  ordersError.value = "";
  try {
    const payload = await api.get("/orders");
    const rawOrders = payload.orders || payload.data || [];
    // Normalize so one malformed order can never blank the whole list.
    orders.value = (Array.isArray(rawOrders) ? rawOrders : []).map((order) => ({
      ...order,
      items: Array.isArray(order.items) ? order.items : [],
      total: Number(order.total ?? 0),
    }));
  } catch (error) {
    orders.value = [];
    ordersError.value = error.message || "Unable to load orders.";
  } finally {
    ordersLoading.value = false;
  }
};

const formatCurrency = (value) => Number(value || 0).toLocaleString();

const formatDate = (value) => {
  if (!value) return "-";
  return new Date(value).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

// Customer-facing order meanings:
// paid = customer already paid the bill · processing = admin confirmed the products ·
// packed = admin already packed the product · shipped = product already in courier ·
// delivered = parcel already arrived to customer.
const ORDER_STATUS_MEANINGS = {
  pending: "Awaiting payment.",
  paid: "You already paid the bill — waiting for admin confirmation.",
  processing: "Admin confirmed the products — preparing your parcel.",
  packed: "Admin already packed your product.",
  shipped: "Out for delivery — your product is already in the courier.",
  delivered: "Delivered — your parcel already arrived.",
  cancelled: "This order was cancelled.",
};

const formatOrderStatus = (status) => {
  const key = String(status || "").toLowerCase();
  if (key === "shipped") return "Out for delivery";
  if (!key) return "Unknown";
  return key.charAt(0).toUpperCase() + key.slice(1);
};

const orderStatusMeaning = (status) => ORDER_STATUS_MEANINGS[String(status || "").toLowerCase()] || "";

// Initialize form values from user data
const initializeForms = () => {
  if (authStore.user) {
    const user = authStore.user;
    profileForm.name = user.name || "";
    profileForm.email = user.email || "";

    if (user.shippingAddress) {
      addressForm.address = user.shippingAddress.address || "";
      addressForm.city = user.shippingAddress.city || "";
      addressForm.country = user.shippingAddress.country || "";
      addressForm.postalCode = user.shippingAddress.postalCode || "";
    }
  }
};

// Watch for user changes to update forms
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    initializeForms();
  }
}, { immediate: true });

// Update profile
const updateProfile = async () => {
  profileError.value = "";
  profileLoading.value = true;

  try {
    await authStore.updateProfile({
      name: profileForm.name,
      email: profileForm.email,
    });
  } catch (error) {
    profileError.value = error.message || "Failed to update profile.";
  } finally {
    profileLoading.value = false;
  }
};

// Change password
const changePassword = async () => {
  passwordError.value = "";
  passwordSuccess.value = "";

  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    passwordError.value = "New passwords do not match.";
    return;
  }

  if (passwordForm.newPassword.length < 6) {
    passwordError.value = "Password must be at least 6 characters.";
    return;
  }

  passwordLoading.value = true;

  try {
    await authStore.changePassword({
      current_password: passwordForm.currentPassword,
      new_password: passwordForm.newPassword,
    });
    passwordSuccess.value = "Password changed successfully!";
    passwordForm.currentPassword = "";
    passwordForm.newPassword = "";
    passwordForm.confirmPassword = "";
  } catch (error) {
    passwordError.value = error.message || "Failed to change password.";
  } finally {
    passwordLoading.value = false;
  }
};

// Update shipping address
const updateShippingAddress = async () => {
  addressError.value = "";
  addressSuccess.value = "";
  addressLoading.value = true;

  try {
    await authStore.updateShippingAddress({
      address: addressForm.address,
      city: addressForm.city,
      country: addressForm.country,
      postal_code: addressForm.postalCode,
    });
    addressSuccess.value = "Shipping address saved successfully!";
  } catch (error) {
    addressError.value = error.message || "Failed to save address.";
  } finally {
    addressLoading.value = false;
  }
};

// Resend verification email
const resendVerification = async () => {
  verificationSuccess.value = "";
  try {
    await authStore.resendVerificationEmail();
    verificationSuccess.value = true;
    startCooldown();
  } catch (error) {
    profileError.value = error.message || "Failed to send verification email.";
  }
};

const startCooldown = () => {
  resendCooldown.value = 60;
  const interval = setInterval(() => {
    resendCooldown.value--;
    if (resendCooldown.value <= 0) {
      clearInterval(interval);
    }
  }, 1000);
};

onMounted(async () => {
  try {
    await authStore.refreshUser();
  } catch {
    // Auth refresh failure must never block the rest of the page.
  }
  initializeForms();
  // Each loader is independent: a notification failure must never
  // prevent the order history from loading (and vice versa).
  await Promise.allSettled([loadNotificationPrefs(), fetchOrders(), fetchMyVouchers()]);
});
</script>

<style scoped>
.profile-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.16), transparent 32%),
    linear-gradient(180deg, rgba(255, 241, 184, 1), rgba(254, 181, 17, 0.34));
  min-height: 60vh;
}

.profile-section {
  padding-bottom: 1rem;
}

.profile-section:not(:last-child) {
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  margin-bottom: 2rem;
}

.alert-success {
  border-color: rgba(40, 167, 69, 0.2);
  background: rgba(40, 167, 69, 0.06);
  color: #1a7f4d;
}

.alert-warning {
  border-color: rgba(255, 193, 7, 0.3);
  background: rgba(255, 193, 7, 0.08);
  color: #856404;
}

.alert-danger {
  border-color: rgba(220, 53, 69, 0.2);
  background: rgba(220, 53, 69, 0.06);
}

.form-text {
  font-size: 0.85rem;
  color: var(--ink-muted);
}

.form-check-input:checked {
  background-color: var(--primary-black);
  border-color: var(--primary-black);
}

.notif-preferences {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.notif-pref-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.notif-pref-item:hover {
  border-color: rgba(77, 16, 24, 0.15);
  background: rgba(77, 16, 24, 0.02);
}

.notif-pref-item--disabled {
  opacity: 0.6;
  pointer-events: none;
}

.notif-pref-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.notif-pref-label {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--primary-black);
}

.notif-pref-desc {
  font-size: 0.78rem;
  color: var(--ink-muted);
}

.notif-switch {
  flex-shrink: 0;
  margin: 0;
}

.order-card {
  padding: 1rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.5);
}

.voucher-list {
  display: grid;
  gap: 1rem;
}

.voucher-card {
  padding: 1.1rem 1.2rem;
  border: 1px dashed rgba(77, 16, 24, 0.3);
  border-radius: var(--radius-md);
  background: linear-gradient(145deg, rgba(255, 248, 228, 0.95), rgba(255, 241, 184, 0.6));
}

.voucher-code-large {
  font-weight: 800;
  letter-spacing: 0.1em;
  font-size: 1.1rem;
}

.order-status-line {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.25rem;
}

.order-tracking-line {
  padding: 0.65rem 0.8rem;
  border: 1px dashed rgba(77, 16, 24, 0.2);
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.6);
}
</style>
