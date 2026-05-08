<template>
  <div class="verify-email-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
          <div class="surface p-4 p-md-5 text-center">
            <!-- Loading State -->
            <div v-if="status === 'loading'" class="verify-content">
              <div class="spinner-border text-dark mb-4" role="status">
                <span class="visually-hidden">Verifying...</span>
              </div>
              <h2>Verifying your email</h2>
              <p class="text-muted">Please wait while we confirm your email address...</p>
            </div>

            <!-- Success State -->
            <div v-else-if="status === 'success'" class="verify-content">
              <div class="success-icon mb-4">
                <i class="bi bi-check-circle-fill text-success"></i>
              </div>
              <h2 class="text-success">Email Verified!</h2>
              <p class="text-muted mb-4">
                Your email address has been successfully verified. You can now sign in to your account.
              </p>
              <div class="d-grid gap-2">
                <router-link to="/login" class="btn btn-luxury">Sign In</router-link>
                <router-link to="/" class="btn btn-outline-luxury">Go to Homepage</router-link>
              </div>
            </div>

            <!-- Error State -->
            <div v-else-if="status === 'error'" class="verify-content">
              <div class="error-icon mb-4">
                <i class="bi bi-x-circle-fill text-danger"></i>
              </div>
              <h2 class="text-danger">Verification Failed</h2>
              <p class="text-muted mb-4">
                {{ errorMessage || 'The verification link is invalid or has expired. Please request a new verification email.' }}
              </p>
              <div class="d-grid gap-2">
                <router-link to="/login" class="btn btn-luxury">Sign In</router-link>
                <button
                  v-if="canResend"
                  type="button"
                  class="btn btn-outline-luxury"
                  @click="resendVerification"
                  :disabled="resending"
                >
                  {{ resending ? 'Sending...' : 'Resend Verification Email' }}
                </button>
                <span v-if="resendSuccess" class="text-success small mt-2">
                  <i class="bi bi-check-circle"></i> Verification email sent!
                </span>
              </div>
            </div>

            <!-- No Token State -->
            <div v-else class="verify-content">
              <div class="error-icon mb-4">
                <i class="bi bi-question-circle-fill text-warning"></i>
              </div>
              <h2>Invalid Request</h2>
              <p class="text-muted mb-4">
                No verification token was provided. Please check the link in your email or request a new verification email.
              </p>
              <div class="d-grid gap-2">
                <router-link to="/login" class="btn btn-luxury">Sign In</router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api } from "../lib/api";
import { useAuthStore } from "../stores/auth";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const token = computed(() => typeof route.query.token === "string" ? route.query.token : undefined);
const status = ref("loading"); // loading, success, error, none
const errorMessage = ref("");
const resending = ref(false);
const resendSuccess = ref(false);
const canResend = ref(true);

const verifyEmail = async () => {
  if (!token.value) {
    status.value = "none";
    return;
  }

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL || '/api'}/auth/verify-email?token=${encodeURIComponent(token.value)}`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      }
    );

    const data = await response.json();

    if (response.ok) {
      status.value = "success";
      // Update auth store user if logged in with same email
      if (authStore.user) {
        authStore.user.emailVerified = true;
        authStore.user.email_verified = true;
      }
    } else {
      status.value = "error";
      errorMessage.value = data.message || "Verification failed.";
    }
  } catch (error) {
    status.value = "error";
    errorMessage.value = "Unable to connect to server. Please try again later.";
  }
};

const resendVerification = async () => {
  if (!authStore.isAuthenticated) {
    router.push("/login");
    return;
  }

  resending.value = true;
  resendSuccess.value = false;

  try {
    await authStore.resendVerificationEmail();
    resendSuccess.value = true;
    canResend.value = false;

    // Cooldown
    let cooldown = 60;
    const interval = setInterval(() => {
      cooldown--;
      if (cooldown <= 0) {
        clearInterval(interval);
        canResend.value = true;
      }
    }, 1000);
  } catch (error) {
    errorMessage.value = error.message || "Failed to resend verification email.";
  } finally {
    resending.value = false;
  }
};

onMounted(() => {
  verifyEmail();
});
</script>

<style scoped>
.verify-email-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.16), transparent 32%),
    linear-gradient(180deg, rgba(255, 241, 184, 1), rgba(254, 181, 17, 0.38));
  min-height: 60vh;
  display: flex;
  align-items: center;
}

.verify-content {
  padding: 2rem 0;
}

.success-icon i,
.error-icon i {
  font-size: 4.5rem;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
}

.btn {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.78rem;
  padding: 0.95rem 2rem;
}

.btn-luxury {
  background: linear-gradient(180deg, #6c1823, #4d1018);
  color: #feb511;
  border: 1px solid rgba(254, 181, 17, 0.16);
  box-shadow: 0 16px 32px rgba(77, 16, 24, 0.2);
}
.btn-luxury:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 38px rgba(77, 16, 24, 0.28);
  color: #feb511;
}

.btn-outline-luxury {
  background-color: rgba(255, 241, 184, 0.72);
  color: var(--primary-black);
  border: 1px solid rgba(77, 16, 24, 0.22);
  backdrop-filter: blur(12px);
}
.btn-outline-luxury:hover {
  transform: translateY(-2px);
  background: var(--primary-black);
  color: #feb511;
  border-color: var(--primary-black);
  box-shadow: 0 18px 32px rgba(77, 16, 24, 0.18);
}

.d-grid {
  display: grid;
  gap: 1rem;
  max-width: 280px;
  margin: 0 auto;
}

@media (max-width: 767.98px) {
  .verify-email-page {
    padding: 2rem 0;
  }
  
  .success-icon i,
  .error-icon i {
    font-size: 3.5rem;
  }
}
</style>
