<template>
  <nav class="navbar navbar-expand-lg luxury-navbar sticky-top">
    <div class="container">
      <router-link to="/" class="navbar-brand">
        <span class="brand-text">NOIR ELEGANCE</span>
      </router-link>

      <button
        class="navbar-toggler border-0"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item">
            <router-link to="/" class="nav-link">Home</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/products" class="nav-link">Collection</router-link>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              Categories
            </a>
            <ul class="dropdown-menu">
              <li><router-link to="/products" class="dropdown-item">All Products</router-link></li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <router-link to="/products?category=Dresses" class="dropdown-item">
                  Dresses
                </router-link>
              </li>
              <li>
                <router-link to="/products?category=Outerwear" class="dropdown-item">
                  Outerwear
                </router-link>
              </li>
              <li>
                <router-link to="/products?category=Pants" class="dropdown-item">Pants</router-link>
              </li>
              <li>
                <router-link to="/products?category=Shirts" class="dropdown-item">Shirts</router-link>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <router-link to="/about" class="nav-link">About</router-link>
          </li>
          <li v-if="canManageProducts" class="nav-item">
            <router-link to="/dashboard" class="nav-link">Dashboard</router-link>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-3">
          <template v-if="authStore.isAuthenticated">
            <div class="nav-account text-uppercase small">{{ userLabel }}</div>
            <button class="btn btn-outline-dark btn-sm px-3" @click="logout">Logout</button>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-outline-dark btn-sm px-3">Login</router-link>
            <router-link to="/register" class="btn btn-luxury btn-sm px-3">Register</router-link>
          </template>
          <button
            class="btn btn-luxury btn-sm d-flex align-items-center justify-content-center position-relative nav-bag-btn"
            @click="goToBag"
          >
            <i class="bi bi-bag fs-5"></i>
            <span
              v-if="cartStore.totalItems"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark nav-badge"
            >
              {{ cartStore.totalItems }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();
const userLabel = computed(() => authStore.user?.name?.split(" ")[0] || "Account");
const canManageProducts = computed(() =>
  ["manager", "admin"].includes(authStore.user?.role || ""),
);

const goToBag = () => {
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/cart" } });
    return;
  }

  router.push("/cart");
};

const logout = () => {
  authStore.logout();
  router.push("/");
};
</script>

<style scoped>
.luxury-navbar {
  background: rgba(255, 255, 255, 0.82);
  border-bottom: 1px solid rgba(11, 11, 12, 0.08);
  backdrop-filter: blur(18px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
}

.brand-text {
  font-family:
    Georgia,
    "Times New Roman",
    serif;
  font-size: 1.55rem;
  font-weight: 700;
  letter-spacing: 0.28em;
  color: #1a1a1a;
}

.nav-link {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: #1a1a1a !important;
  margin: 0 10px;
  transition: all 0.3s ease;
  position: relative;
  opacity: 0.82;
}

.nav-link::after {
  content: "";
  position: absolute;
  width: 0;
  height: 1px;
  bottom: 0;
  left: 0;
  background-color: #1a1a1a;
  transition: width 0.3s ease;
}

.nav-link:hover::after,
.nav-link.router-link-active::after {
  width: 100%;
}

.nav-link:hover,
.nav-link.router-link-active {
  opacity: 1;
}

.nav-bag-btn {
  min-width: 3rem;
  height: 3rem;
  border-radius: 999px;
}

.nav-badge {
  font-size: 0.65rem;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
}

.nav-account {
  letter-spacing: 0.18em;
  opacity: 0.72;
}
</style>
