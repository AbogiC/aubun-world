<template>
  <div class="auth-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
          <div class="surface auth-panel p-4 p-md-5">
            <p class="section-kicker mb-3">New Client</p>
            <h1 class="mb-3">Create Account</h1>
            <p class="text-muted mb-4">
              Join Aubun World to save your bag and move straight into checkout.
            </p>

            <div v-if="emailNotice" class="alert alert-success mb-4">
              {{ emailNotice }}
            </div>

            <div v-if="welcomeVoucher" class="alert alert-warning mb-4 welcome-gift">
              <div class="d-flex align-items-start gap-2">
                <i class="bi bi-ticket-perforated-fill fs-5"></i>
                <div>
                  <strong>Welcome gift: {{ welcomeVoucher.discountPercent }}% off!</strong>
                  <div class="small mt-1">
                    Your free voucher code:
                    <code class="voucher-code">{{ welcomeVoucher.code }}</code>
                  </div>
                  <div class="small text-muted">Apply it in your bag at checkout. Valid until {{ formatExpiry(welcomeVoucher.expiresAt) }}.</div>
                </div>
              </div>
            </div>

            <form @submit.prevent="submit">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-lg"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control form-control-lg"
                  required
                />
              </div>

              <div class="mb-4">
                <label class="form-label">Password</label>
                <input
                  v-model="form.password"
                  type="password"
                  class="form-control form-control-lg"
                  minlength="6"
                  required
                />
              </div>

              <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

              <button type="submit" class="btn btn-luxury w-100" :disabled="authStore.loading">
                {{ authStore.loading ? "Creating Account..." : "Create Account" }}
              </button>
            </form>

            <div class="auth-divider"><span>or</span></div>

            <GoogleSignInButton text="signup_with" @credential="submitGoogle" />
            <div v-if="googleError" class="alert alert-danger mt-3">{{ googleError }}</div>

            <div class="text-center mt-4">
              <span class="text-muted">Already have an account?</span>
              <router-link :to="loginLink" class="auth-link ms-2">Sign in</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import GoogleSignInButton from "../components/GoogleSignInButton.vue";

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const errorMessage = ref("");
const googleError = ref("");
const emailNotice = ref("");
const welcomeVoucher = ref(null);
const form = reactive({
  name: "",
  email: "",
  role: "customer",
  password: "",
});

const redirectTarget = computed(() => route.query.redirect || "/");
const loginLink = computed(() => ({
  path: "/login",
  query: route.query.redirect ? { redirect: route.query.redirect } : {},
}));

const submit = async () => {
  errorMessage.value = "";
  emailNotice.value = "";
  welcomeVoucher.value = null;

  try {
    const response = await authStore.register(form);
    const notice = response?.emailNotice || "";
    if (notice) {
      emailNotice.value = notice;
    } else {
      emailNotice.value = "Account created successfully. Please verify your email before continuing.";
    }
    if (response?.welcomeVoucher) {
      welcomeVoucher.value = response.welcomeVoucher;
      // Let the customer see their free voucher code before leaving.
      setTimeout(() => router.push(redirectTarget.value), 4000);
      return;
    }
    router.push(redirectTarget.value);
  } catch (error) {
    errorMessage.value = error.message;
  }
};

const submitGoogle = async (credential) => {
  errorMessage.value = "";
  googleError.value = "";
  emailNotice.value = "";
  welcomeVoucher.value = null;

  try {
    const response = await authStore.loginWithGoogle(credential);
    if (response?.welcomeVoucher) {
      welcomeVoucher.value = response.welcomeVoucher;
      emailNotice.value = "Account created with Google. Your email is already verified.";
      setTimeout(() => router.push(redirectTarget.value), 4000);
      return;
    }
    router.push(redirectTarget.value);
  } catch (error) {
    googleError.value = error.message;
  }
};

const formatExpiry = (value) => {
  if (!value) return "-";
  return new Date(String(value).replace(" ", "T")).toLocaleDateString([], {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
</script>

<style scoped>
.auth-panel {
  border: 1px solid rgba(11, 11, 12, 0.08);
}

.auth-link {
  color: var(--primary-black);
  text-decoration: none;
  font-weight: 600;
}

.welcome-gift {
  border-color: rgba(180, 120, 10, 0.35);
  background: rgba(255, 243, 205, 0.9);
  color: #664d03;
}

.voucher-code {
  font-weight: 700;
  letter-spacing: 0.06em;
  background: rgba(0, 0, 0, 0.06);
  padding: 0.1rem 0.45rem;
  border-radius: 0.35rem;
}

.auth-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 1.25rem 0;
  color: #6c757d;
  font-size: 0.85rem;
}

.auth-divider::before,
.auth-divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: rgba(11, 11, 12, 0.12);
}
</style>