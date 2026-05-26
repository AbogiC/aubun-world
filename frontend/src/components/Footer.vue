<template>
  <footer class="luxury-footer text-white py-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 mb-4">
          <h3 class="brand-text mb-3">AUBUN WORLD</h3>
          <div class="d-flex gap-3 mt-3">
            <a href="https://www.instagram.com/aubunworld?igsh=bWd1N2tzZzEzdXVz" class="text-white"
              ><i class="bi bi-instagram fs-5"></i
            ></a>
            <a href="https://www.tiktok.com/@aubunworld?_r=1&_t=ZS-966Xm9zhcuI" class="text-white"
              ><i class="bi bi-tiktok fs-5"></i
            ></a>
            <a href="https://www.threads.com/@aubunworld" class="text-white"
              ><i class="bi bi-threads fs-5"></i
            ></a>
            <a
              href="https://x.com/aubunworld?s=21&t=7AODALjFtnvF_lIj8QCQ0Q&fbclid=PAb21jcARmnmRleHRuA2FlbQIxMQBzcnRjBmFwcF9pZA81NjcwNjczNDMzNTI0MjcAAad4FmRRVUkBiKQpI78ZOUzpDsNKu9BnRD47YgyVGdT_S-MwevTg4ECmHfH4Fw_aem_XuhvQes5mEef4y23U8nKrA"
              class="text-white"
              ><i class="bi bi-twitter-x fs-5"></i
            ></a>
          </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">Shop</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">New Arrivals</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Pants, Outer, T-Shirts</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Sales</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Mix & Match</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">About</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Stocklist</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Location</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">Help</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Contact Us</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">FAQ</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Shipping Policy</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-3 mb-4">
          <h5 class="text-uppercase mb-3">Subscribe</h5>
          <p class="opacity-75 small mb-3">
            Get updates on new drops, featured looks, and upcoming collections.
          </p>
          <form class="footer-subscribe-form" @submit.prevent="subscribe">
            <input
              v-model="email"
              type="email"
              class="form-control"
              placeholder="Enter your email"
              :disabled="isSubmitting"
              required
            />
            <button type="submit" class="btn btn-luxury footer-subscribe-btn" :disabled="isSubmitting">
              {{ isSubmitting ? "Subscribing..." : "Subscribe" }}
            </button>
          </form>
          <p v-if="successMessage" class="footer-feedback footer-feedback--success mb-0 mt-3">
            {{ successMessage }}
          </p>
          <p v-if="errorMessage" class="footer-feedback footer-feedback--error mb-0 mt-3">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <hr class="my-4 opacity-25" />

      <div class="row">
        <div class="col-md-6 text-center text-md-start">
          <p class="mb-0 small opacity-50">&copy; 2026 Aubun World. All rights reserved.</p>
        </div>
        <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
          <i class="bi bi-credit-card fs-5 me-2 opacity-50"></i>
          <i class="bi bi-paypal fs-5 me-2 opacity-50"></i>
        </div>
      </div>
    </div>
  </footer>
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
.luxury-footer {
  position: relative;
  background:
    radial-gradient(circle at top left, rgba(254, 181, 17, 0.16), transparent 35%),
    linear-gradient(180deg, #6c1823 0%, #4d1018 100%);
  border-top: 1px solid rgba(254, 181, 17, 0.16);
}

.brand-text {
  font-family: Georgia, "Times New Roman", serif;
  letter-spacing: 0.2em;
}

.luxury-footer a {
  transition:
    opacity 220ms ease,
    transform 220ms ease;
}

.luxury-footer a:hover {
  opacity: 1 !important;
  transform: translateY(-1px);
}

.footer-subscribe-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-subscribe-btn {
  width: 100%;
  padding-inline: 1rem;
}

.footer-feedback {
  font-size: 0.82rem;
  line-height: 1.5;
}

.footer-feedback--success {
  color: #b6f2a7;
}

.footer-feedback--error {
  color: #ffb3b3;
}
</style>
