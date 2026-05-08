<template>
  <nav class="navbar navbar-expand-lg luxury-navbar sticky-top">
    <div class="container">
      <router-link to="/" class="navbar-brand">
        <span class="brand-text">AUBUN WORLD</span>
      </router-link>

      <button
        ref="togglerButton"
        class="navbar-toggler border-0"
        type="button"
        aria-controls="navbarNav"
        :aria-expanded="isNavbarOpen ? 'true' : 'false'"
        aria-label="Toggle navigation"
        @click="toggleNavbarMenu"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div ref="collapseElement" class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item">
            <router-link to="/" class="nav-link" @click="closeNavbarMenu">Home</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/products" class="nav-link" @click="closeNavbarMenu"
              >Collection</router-link
            >
          </li>
          <li class="nav-item">
            <router-link to="/about" class="nav-link" @click="closeNavbarMenu">About</router-link>
          </li>
          <li
            v-if="canManageProducts"
            class="nav-item nav-item--dropdown"
            :class="{ show: dashboardMenuOpen }"
          >
            <button
              type="button"
              class="nav-link nav-link--button"
              :class="{ 'router-link-active': isDashboardSection }"
              @click="toggleDashboardMenu"
            >
              Dashboard
              <i
                class="bi bi-chevron-down nav-dropdown-icon"
                :class="{ 'nav-dropdown-icon--open': dashboardMenuOpen }"
              ></i>
            </button>

            <div v-if="dashboardMenuOpen" class="dashboard-dropdown-menu">
              <router-link
                to="/dashboard/products"
                class="dashboard-dropdown-item"
                @click="navigateFromDashboardMenu"
              >
                Products
              </router-link>
              <router-link
                to="/dashboard/shipping"
                class="dashboard-dropdown-item"
                @click="navigateFromDashboardMenu"
              >
                Shipping
              </router-link>
            </div>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-3 mt-1">
          <template v-if="authStore.isAuthenticated">
            <router-link
              to="/profile"
              class="nav-account text-uppercase small text-decoration-none"
              >{{ userLabel }}</router-link
            >
            <button class="btn btn-outline-dark btn-sm px-3" @click="logout">Logout</button>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-outline-dark btn-sm px-3">Login</router-link>
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
import { computed, ref } from "vue";
import { Collapse } from "bootstrap";
import { useRoute, useRouter } from "vue-router";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const collapseElement = ref(null);
const togglerButton = ref(null);
const dashboardMenuOpen = ref(false);
const isNavbarOpen = ref(false);
const userLabel = computed(() => authStore.user?.name?.split(" ")[0] || "Account");
const canManageProducts = computed(() => ["manager", "admin"].includes(authStore.user?.role || ""));
const isDashboardSection = computed(() => route.path.startsWith("/dashboard"));

const syncNavbarOpenState = () => {
  isNavbarOpen.value = collapseElement.value?.classList.contains("show") ?? false;
};

const toggleNavbarMenu = () => {
  if (!collapseElement.value || !togglerButton.value) {
    return;
  }

  const togglerVisible = window.getComputedStyle(togglerButton.value).display !== "none";

  if (!togglerVisible) {
    return;
  }

  const collapse = Collapse.getOrCreateInstance(collapseElement.value);
  collapse.toggle();

  // Bootstrap updates the `show` class asynchronously during transitions.
  window.setTimeout(syncNavbarOpenState, 0);
};

const closeNavbarMenu = () => {
  if (!collapseElement.value || !togglerButton.value) {
    return;
  }

  const togglerVisible = window.getComputedStyle(togglerButton.value).display !== "none";

  if (!togglerVisible || !collapseElement.value.classList.contains("show")) {
    return;
  }

  Collapse.getOrCreateInstance(collapseElement.value).hide();
  isNavbarOpen.value = false;
  dashboardMenuOpen.value = false;
};

const goToBag = () => {
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/cart" } });
    return;
  }

  router.push("/cart");
};

const toggleDashboardMenu = () => {
  dashboardMenuOpen.value = !dashboardMenuOpen.value;
};

const navigateFromDashboardMenu = () => {
  dashboardMenuOpen.value = false;
  closeNavbarMenu();
};

const logout = () => {
  authStore.logout();
  dashboardMenuOpen.value = false;
  router.push("/");
};
</script>

<style scoped>
.luxury-navbar {
  background: rgba(255, 241, 184, 0.82);
  border-bottom: 1px solid rgba(77, 16, 24, 0.12);
  backdrop-filter: blur(18px);
  box-shadow: 0 12px 30px rgba(77, 16, 24, 0.08);
}

.brand-text {
  font-family: Georgia, "Times New Roman", serif;
  font-size: 1.55rem;
  font-weight: 700;
  letter-spacing: 0.28em;
  color: var(--primary-black);
}

.nav-link {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--primary-black) !important;
  margin: 0 10px;
  transition: all 0.3s ease;
  position: relative;
  opacity: 0.82;
}

.nav-link--button {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: transparent;
  border: 0;
}

.nav-link::after {
  content: "";
  position: absolute;
  width: 0;
  height: 1px;
  bottom: 0;
  left: 0;
  background-color: var(--primary-black);
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

.nav-item--dropdown {
  position: relative;
}

.nav-dropdown-icon {
  font-size: 0.8rem;
  transition: transform 0.22s ease;
}

.nav-dropdown-icon--open {
  transform: rotate(180deg);
}

.dashboard-dropdown-menu {
  position: absolute;
  top: calc(100% + 0.75rem);
  left: 0;
  min-width: 220px;
  padding: 0.65rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.96);
  box-shadow: 0 18px 36px rgba(77, 16, 24, 0.12);
  backdrop-filter: blur(14px);
  display: grid;
  gap: 0.35rem;
  z-index: 20;
}

.dashboard-dropdown-item {
  display: block;
  padding: 0.75rem 0.9rem;
  border-radius: 0.85rem;
  color: var(--primary-black);
  text-decoration: none;
  font-size: 0.84rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  transition:
    background-color 0.2s ease,
    opacity 0.2s ease;
  opacity: 0.82;
}

.dashboard-dropdown-item:hover,
.dashboard-dropdown-item.router-link-active {
  background: rgba(77, 16, 24, 0.08);
  opacity: 1;
}

.nav-bag-btn {
  min-width: 3rem;
  height: 3rem;
  border-radius: 999px;
}

.nav-badge {
  font-size: 0.65rem;
  box-shadow: 0 8px 18px rgba(77, 16, 24, 0.24);
}

.nav-account {
  letter-spacing: 0.18em;
  opacity: 0.72;
  color: var(--primary-black);
}

@media (max-width: 991px) {
  .dashboard-dropdown-menu {
    position: static;
    margin: 0.5rem 10px 0;
    min-width: 0;
  }
}
</style>
