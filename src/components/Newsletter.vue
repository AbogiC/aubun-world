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
                required
              />
              <button type="submit" class="btn btn-luxury btn-lg px-5">Subscribe</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";

const email = ref("");

const subscribe = () => {
  alert(`Thank you for subscribing with: ${email.value}`);
  email.value = "";
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
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.45));
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
