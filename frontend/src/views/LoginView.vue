<template>
  <div class="auth-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
          <div class="surface auth-panel p-4 p-md-5">
            <p class="section-kicker mb-3">Member Access</p>
            <h1 class="mb-3">Sign In</h1>
            <p class="text-muted mb-4">
              Sign in to continue shopping and complete your checkout.
            </p>

            <div v-if="route.query.redirect" class="auth-note mb-4">
              Please sign in to continue with your shopping bag.
            </div>

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

              <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

              <button type="submit" class="btn btn-luxury w-100" :disabled="authStore.loading">
                {{ authStore.loading ? "Signing In..." : "Sign In" }}
              </button>
            </form>

            <div class="text-center mt-4">
              <span class="text-muted">New to Aubun World?</span>
              <router-link :to="registerLink" class="auth-link ms-2">Create account</router-link>
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

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const errorMessage = ref("");
const form = reactive({
  email: "",
  password: "",
});

const redirectTarget = computed(() => route.query.redirect || "/");
const registerLink = computed(() => ({
  path: "/register",
  query: route.query.redirect ? { redirect: route.query.redirect } : {},
}));

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
.auth-panel {
  border: 1px solid rgba(11, 11, 12, 0.08);
}

.auth-note {
  padding: 0.9rem 1rem;
  border-radius: 0.9rem;
  background: rgba(11, 11, 12, 0.06);
  color: var(--primary-black);
  font-size: 0.95rem;
}

.auth-link {
  color: var(--primary-black);
  text-decoration: none;
  font-weight: 600;
}
</style>
