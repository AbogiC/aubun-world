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
                    :disabled="profileLoading"
                  />
                  <div v-if="authStore.user?.emailVerified" class="form-text text-success">
                    <i class="bi bi-check-circle-fill"></i> Verified
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
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";

const authStore = useAuthStore();
const route = useRoute();

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

onMounted(() => {
  initializeForms();
});
</script>

<style scoped>
.profile-page {
  background:
    radial-gradient(circle at top center, rgba(201, 168, 106, 0.06), transparent 32%),
    linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(249, 247, 243, 0.94));
  min-height: 60vh;
}

.profile-section {
  padding-bottom: 1rem;
}

.profile-section:not(:last-child) {
  border-bottom: 1px solid rgba(11, 11, 12, 0.08);
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
</style>
