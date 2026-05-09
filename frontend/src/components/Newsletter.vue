<template>
  <section class="newsletter-section py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-7">
          <div class="newsletter-panel surface p-5 text-center">
            <h2 class="section-title mb-4">Join Our Exclusive Circle</h2>
            <p class="text-muted mb-4">
              Subscribe to receive early access to new collections, private events, and tailored
              styling notes.
            </p>
            <form @submit.prevent="subscribe" class="newsletter-form d-flex gap-2">
              <input
                v-model="email"
                type="email"
                class="form-control form-control-lg"
                placeholder="Enter your email"
                :disabled="isSubmitting"
                required
              />
              <button type="submit" class="btn btn-luxury btn-lg px-5" :disabled="isSubmitting">
                {{ isSubmitting ? "Subscribing..." : "Subscribe" }}
              </button>
            </form>
            <p v-if="successMessage" class="mt-3 mb-0 text-success">{{ successMessage }}</p>
            <p v-if="errorMessage" class="mt-3 mb-0 text-danger">{{ errorMessage }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { api } from "../lib/api";

const email = ref("");
const isSubmitting = ref(false);
const successMessage = ref("");
const errorMessage = ref("");

const subscribe = async () => {
  isSubmitting.value = true;
  successMessage.value = "";
  errorMessage.value = "";

  try {
    const { message } = await api.post("/auth/newsletter/subscribe", {
      email: email.value,
    });

    successMessage.value = message;
    email.value = "";
  } catch (error) {
    errorMessage.value = error?.message || "Failed to subscribe to the newsletter.";
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.newsletter-panel {
  position: relative;
  overflow: hidden;
}

.newsletter-panel::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 241, 184, 0.5), rgba(77, 16, 24, 0.08));
  pointer-events: none;
}

.newsletter-panel > * {
  position: relative;
  z-index: 1;
}

.newsletter-form {
  position: relative;
}

@media (max-width: 767.98px) {
  .newsletter-form {
    flex-direction: column;
  }
}
</style>
