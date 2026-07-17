<template>
  <footer class="luxury-footer py-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 mb-4">
          <h3 class="brand-text mb-3">AUBUN WORLD</h3>
          <p class="opacity-60 small mb-3" style="color: rgba(252, 250, 246, 0.6); line-height: 1.7;">
            Refined essentials and elevated layering for the modern wardrobe.
          </p>
          <div class="d-flex gap-3 mt-3">
            <a href="https://www.instagram.com/aubunworld?igsh=bWd1N2tzZzEzdXVz" class="social-link" aria-label="Instagram">
              <i class="bi bi-instagram fs-5"></i>
            </a>
            <a href="https://www.tiktok.com/@aubunworld?_r=1&_t=ZS-966Xm9zhcuI" class="social-link" aria-label="TikTok">
              <i class="bi bi-tiktok fs-5"></i>
            </a>
            <a href="https://www.threads.com/@aubunworld" class="social-link" aria-label="Threads">
              <i class="bi bi-threads fs-5"></i>
            </a>
            <a
              href="https://x.com/aubunworld?s=21&t=7AODALjFtnvF_lIj8QCQ0Q&fbclid=PAb21jcARmnmRleHRuA2FlbQIxMQBzcnRjBmFwcF9pZA81NjcwNjczNDMzNTI0MjcAAad4FmRRVUkBiKQpI78ZOUzpDsNKu9BnRD47YgyVGdT_S-MwevTg4ECmHfH4Fw_aem_XuhvQes5mEef4y23U8nKrA"
              class="social-link" aria-label="X (Twitter)"
            >
              <i class="bi bi-twitter-x fs-5"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3 footer-heading">Shop</h5>
          <ul class="list-unstyled footer-links">
            <li class="mb-2">
              <a href="/products" class="footer-link">New Arrivals</a>
            </li>
            <li class="mb-2">
              <a href="/products" class="footer-link">Pants, Outers, T-Shirts</a>
            </li>
            <li class="mb-2">
              <a href="/products" class="footer-link">Collection</a>
            </li>
            <li class="mb-2">
              <a href="/mix-match" class="footer-link">Mix & Match</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3 footer-heading">About</h5>
          <ul class="list-unstyled footer-links">
            <li class="mb-2">
              <a href="/about" class="footer-link">Our Story</a>
            </li>
            <li class="mb-2">
              <a href="/about" class="footer-link">Craft Heritage</a>
            </li>
            <li class="mb-2">
              <a href="/about" class="footer-link">Stocklist</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3 footer-heading">Help</h5>
          <ul class="list-unstyled footer-links">
            <li class="mb-2">
              <a href="#" class="footer-link">Contact Us</a>
            </li>
            <li class="mb-2">
              <a href="#" class="footer-link">FAQ</a>
            </li>
            <li class="mb-2">
              <a href="#" class="footer-link">Shipping Policy</a>
            </li>
            <li class="mb-2">
              <a href="#" class="footer-link">Returns</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-3 mb-4">
          <h5 class="text-uppercase mb-3 footer-heading">Subscribe</h5>
          <p class="opacity-60 small mb-3" style="color: rgba(252, 250, 246, 0.6);">
            Get updates on new drops, featured looks, and upcoming collections.
          </p>
          <form class="footer-subscribe-form" @submit.prevent="subscribe">
            <div class="footer-input-group">
              <input
                v-model="email"
                type="email"
                class="form-control"
                placeholder="Enter your email"
                :disabled="isSubmitting"
                required
              />
              <button type="submit" class="btn btn-luxury footer-subscribe-btn" :disabled="isSubmitting">
                {{ isSubmitting ? "Sending..." : "Subscribe" }}
              </button>
            </div>
          </form>
          <p v-if="successMessage" class="footer-feedback footer-feedback--success mb-0 mt-3">
            {{ successMessage }}
          </p>
          <p v-if="errorMessage" class="footer-feedback footer-feedback--error mb-0 mt-3">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <hr class="my-4" style="border-color: rgba(252, 250, 246, 0.1);" />

      <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start">
          <p class="mb-0 small" style="color: rgba(252, 250, 246, 0.4);">&copy; 2026 Aubun World. All rights reserved.</p>
        </div>
        <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
          <span style="color: rgba(252, 250, 246, 0.3); font-size: 1.1rem;">
            <i class="bi bi-credit-card me-2"></i>
            <i class="bi bi-paypal"></i>
          </span>
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
    radial-gradient(ellipse at top left, rgba(254, 181, 17, 0.1), transparent 40%),
    linear-gradient(180deg, #5a141f 0%, #3d0d13 100%);
  border-top: 1px solid rgba(254, 181, 17, 0.08);
}

.brand-text {
  font-family: "Playfair Display", Georgia, serif;
  letter-spacing: 0.2em;
  color: var(--gold-light);
  font-size: 1.3rem;
}

.footer-heading {
  font-size: 0.75rem;
  letter-spacing: 0.24em;
  color: rgba(252, 250, 246, 0.5);
  font-weight: 600;
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.footer-link {
  color: rgba(252, 250, 246, 0.55);
  text-decoration: none;
  font-size: 0.88rem;
  transition: color 220ms ease, padding-left 220ms ease;
  display: inline-block;
}

.footer-link:hover {
  color: var(--gold-light);
  padding-left: 4px;
}

.social-link {
  color: rgba(252, 250, 246, 0.5);
  text-decoration: none;
  transition: color 220ms ease, transform 220ms ease;
  display: inline-flex;
}

.social-link:hover {
  color: var(--gold-light);
  transform: translateY(-2px);
}

.footer-subscribe-form {
  width: 100%;
}

.footer-input-group {
  display: flex;
  gap: 0.5rem;
}

.footer-input-group .form-control {
  flex: 1;
  background: rgba(255, 248, 228, 0.08);
  border: 1px solid rgba(255, 248, 228, 0.12);
  color: var(--white);
  font-size: 0.85rem;
  padding: 0.7rem 0.9rem;
}

.footer-input-group .form-control::placeholder {
  color: rgba(252, 250, 246, 0.3);
}

.footer-input-group .form-control:focus {
  background: rgba(255, 248, 228, 0.12);
  border-color: rgba(254, 181, 17, 0.3);
}

.footer-subscribe-btn {
  padding: 0.7rem 1.2rem;
  font-size: 0.7rem;
  white-space: nowrap;
  flex-shrink: 0;
}

.footer-feedback {
  font-size: 0.8rem;
  line-height: 1.5;
}

.footer-feedback--success {
  color: #8cd4a8;
}

.footer-feedback--error {
  color: #f5a0a0;
}

@media (max-width: 575.98px) {
  .footer-input-group {
    flex-direction: column;
  }

  .footer-subscribe-btn {
    width: 100%;
  }
}
</style>