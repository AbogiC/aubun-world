<template>
  <div class="auth-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
          <div class="surface auth-panel p-4 p-md-5">
            <p class="section-kicker mb-3">New Client</p>
            <h1 class="mb-3">Create Account</h1>
            <p class="text-muted mb-4">
              Join Noir Elegance to save your bag and move straight into checkout.
            </p>

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

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const errorMessage = ref("");
const form = reactive({
  name: "",
  email: "",
  role: "customer",
  password: "",
});

const redirectTarget = computed(() => route.query.redirect || "/cart");
const loginLink = computed(() => ({
  path: "/login",
  query: route.query.redirect ? { redirect: route.query.redirect } : {},
}));

const submit = async () => {
  errorMessage.value = "";

  try {
    await authStore.register(form);
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

.auth-link {
  color: var(--primary-black);
  text-decoration: none;
  font-weight: 600;
}
</style>
