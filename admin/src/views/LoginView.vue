<template>
  <div class="auth-layout">
    <div class="auth-card surface">
      <div class="auth-brand">
        <router-link to="/products" class="navbar-brand">
          <span class="brand-text">AUBUN ADMIN</span>
        </router-link>
      </div>

      <h2 class="auth-title">Sign In</h2>
      <p class="auth-subtitle">Access the admin dashboard to manage your store</p>

      <form @submit.prevent="submit">
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
            required
          />
        </div>

        <div v-if="errorMessage" class="alert alert-danger mb-4">{{ errorMessage }}</div>

        <button type="submit" class="btn btn-luxury w-100" :disabled="authStore.loading">
          {{ authStore.loading ? "Signing In..." : "Sign In" }}
        </button>
      </form>

      <div class="auth-footer">
        <p class="text-muted mb-0">
          Only manager and admin accounts can access this dashboard.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const errorMessage = ref("");
const form = reactive({
  email: "",
  password: "",
});

const redirectTarget = computed(() => route.query.redirect || "/products");

const submit = async () => {
  errorMessage.value = "";

  try {
    await authStore.login(form);
    router.push(redirectTarget.value);
  } catch (error) {
    errorMessage.value = error.message;
  }
};
</script>

<style scoped>
.auth-card {
  width: 100%;
  max-width: 420px;
  padding: 2.5rem;
}

.auth-brand {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-brand .brand-text {
  font-size: 1.5rem;
  letter-spacing: 0.2em;
}

.auth-title {
  text-align: center;
  margin-bottom: 0.5rem;
}

.auth-subtitle {
  text-align: center;
  color: var(--ink-muted);
  margin-bottom: 2rem;
}

.auth-footer {
  text-align: center;
  margin-top: 1.5rem;
  color: var(--ink-muted);
  font-size: 0.85rem;
}
</style>