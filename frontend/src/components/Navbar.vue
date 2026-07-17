<template>
  <nav class="navbar navbar-expand-lg luxury-navbar fixed-top" :class="{ 'navbar--scrolled': isScrolled }">
    <div class="w-100">
      <!-- Desktop: two-row layout -->
      <div class="navbar-desktop d-none d-lg-block">
        <div class="navbar-brand-row">
          <div class="container text-center">
            <router-link to="/" class="navbar-brand">
              <span class="brand-text">AUBUN WORLD</span>
            </router-link>
          </div>
        </div>

        <div class="navbar-menu-row">
          <div class="container nav-row-shell">
            <div ref="collapseElement" class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav nav-links-center">
                <li class="nav-item">
                  <router-link to="/" class="nav-link">Home</router-link>
                </li>
                <li class="nav-item">
                  <router-link to="/products" class="nav-link">Collection</router-link>
                </li>
                <li class="nav-item">
                  <router-link to="/guidlines" class="nav-link">Guidelines</router-link>
                </li>
                <li class="nav-item">
                  <router-link to="/mix-match" class="nav-link">Mix & Match</router-link>
                </li>
                <li class="nav-item">
                  <router-link to="/about" class="nav-link">About Us</router-link>
                </li>
              </ul>

              <div class="d-flex align-items-center gap-3 nav-actions">
                <template v-if="authStore.isAuthenticated">
                  <div class="nav-item nav-item--dropdown" :class="{ show: accountMenuOpen }">
                    <button
                      type="button"
                      class="nav-account nav-link nav-link--button text-uppercase small"
                      :class="{ 'router-link-active': isAccountSection }"
                      @click="toggleAccountMenu"
                    >
                      <i class="bi bi-person"></i>
                      <i
                        class="bi bi-chevron-down nav-dropdown-icon"
                        :class="{ 'nav-dropdown-icon--open': accountMenuOpen }"
                      ></i>
                    </button>

                    <div v-if="accountMenuOpen" class="dropdown-menu-custom dropdown-menu-custom--account">
                      <router-link to="/profile" class="dropdown-item-custom" @click="navigateFromAccountMenu">Profile</router-link>
                      <router-link v-if="canViewAllOrders" to="/orders" class="dropdown-item-custom" @click="closeNavbarMenu">All Orders</router-link>
                      <router-link to="/dashboard/products" class="dropdown-item-custom" @click="navigateFromDashboardMenu">Products</router-link>
                      <router-link to="/dashboard/shipping" class="dropdown-item-custom" @click="navigateFromDashboardMenu">Shipping</router-link>
                      <router-link to="/dashboard/vouchers" class="dropdown-item-custom" @click="navigateFromDashboardMenu">Vouchers</router-link>
                      <router-link v-if="!canViewAllOrders" to="/orders" class="dropdown-item-custom" @click="navigateFromAccountMenu">Orders</router-link>
                    </div>
                  </div>
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
        </div>
      </div>

      <!-- Mobile: single row with brand + toggler + bag -->
      <div class="navbar-mobile d-lg-none">
        <div class="container-fluid px-3">
          <div class="mobile-nav-row">
            <router-link to="/" class="mobile-brand">
              <span class="brand-text">AUBUN WORLD</span>
            </router-link>

            <div class="mobile-actions">
              <button
                class="btn btn-luxury btn-sm nav-bag-btn-mobile"
                @click="goToBag"
              >
                <i class="bi bi-bag"></i>
                <span
                  v-if="cartStore.totalItems"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark nav-badge"
                >
                  {{ cartStore.totalItems }}
                </span>
              </button>

              <button
                ref="togglerButton"
                class="hamburger"
                :class="{ 'hamburger--active': isNavbarOpen }"
                type="button"
                aria-controls="mobileNav"
                :aria-expanded="isNavbarOpen ? 'true' : 'false'"
                aria-label="Toggle navigation"
                @click="toggleNavbarMenu"
              >
                <span class="hamburger-line hamburger-line--top"></span>
                <span class="hamburger-line hamburger-line--mid"></span>
                <span class="hamburger-line hamburger-line--bot"></span>
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile menu panel -->
        <transition name="mobile-menu">
          <div v-if="isNavbarOpen" ref="mobilePanelRef" class="mobile-panel" id="mobileNav">
            <div class="container-fluid px-3">
              <div class="mobile-nav-links">
                <router-link to="/" class="mobile-nav-link" @click="closeNavbarMenu">Home</router-link>
                <router-link to="/products" class="mobile-nav-link" @click="closeNavbarMenu">Collection</router-link>
                <router-link to="/guidlines" class="mobile-nav-link" @click="closeNavbarMenu">Guidelines</router-link>
                <router-link to="/mix-match" class="mobile-nav-link" @click="closeNavbarMenu">Mix & Match</router-link>
                <router-link to="/about" class="mobile-nav-link" @click="closeNavbarMenu">About Us</router-link>
              </div>

              <div class="mobile-auth-section">
                <template v-if="authStore.isAuthenticated">
                  <div class="mobile-user-header">
                    <i class="bi bi-person-circle"></i>
                    <span>{{ authStore.user?.name || "Account" }}</span>
                  </div>
                  <router-link to="/profile" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-gear"></i> Profile
                  </router-link>
                  <router-link v-if="canViewAllOrders" to="/orders" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-box"></i> All Orders
                  </router-link>
                  <router-link v-else to="/orders" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-box"></i> My Orders
                  </router-link>
                  <router-link to="/dashboard/products" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-grid"></i> Products
                  </router-link>
                  <router-link to="/dashboard/shipping" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-truck"></i> Shipping
                  </router-link>
                  <router-link to="/dashboard/vouchers" class="mobile-auth-link" @click="closeNavbarMenu">
                    <i class="bi bi-ticket"></i> Vouchers
                  </router-link>
                  <button class="mobile-logout-btn" @click="logout">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                  </button>
                </template>
                <template v-else>
                  <p class="mobile-guest-text">Sign in to access your bag and orders.</p>
                  <router-link to="/login" class="btn btn-luxury w-100 mb-2" @click="closeNavbarMenu">Sign In</router-link>
                  <router-link to="/register" class="btn btn-outline-luxury w-100" @click="closeNavbarMenu">Create Account</router-link>
                </template>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- Overlay when mobile menu is open -->
      <div
        v-if="isNavbarOpen"
        class="mobile-overlay d-lg-none"
        @click="closeNavbarMenu"
      ></div>
    </div>
  </nav>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const togglerButton = ref(null);
const mobilePanelRef = ref(null);
const isNavbarOpen = ref(false);
const isScrolled = ref(false);
const accountMenuOpen = ref(false);
const dashboardMenuOpen = ref(false);
const canManageProducts = computed(() => ["manager", "admin"].includes(authStore.user?.role || ""));
const canViewAllOrders = computed(() => ["manager", "admin"].includes(authStore.user?.role || ""));
const isAccountSection = computed(() => ["/profile", "/orders"].includes(route.path));

let scrollObserver;

onMounted(() => {
  scrollObserver = new IntersectionObserver(
    ([entry]) => { isScrolled.value = !entry.isIntersecting; },
    { threshold: 0, rootMargin: "-1px 0px 0px 0px" },
  );
  const sentinel = document.createElement("div");
  sentinel.style.position = "absolute";
  sentinel.style.top = "0";
  sentinel.style.left = "0";
  sentinel.style.width = "1px";
  sentinel.style.height = "1px";
  sentinel.style.pointerEvents = "none";
  document.body.prepend(sentinel);
  scrollObserver.observe(sentinel);
});

onBeforeUnmount(() => {
  scrollObserver?.disconnect();
});

const toggleNavbarMenu = () => {
  isNavbarOpen.value = !isNavbarOpen.value;
  document.body.style.overflow = isNavbarOpen.value ? "hidden" : "";
};

const closeNavbarMenu = () => {
  if (!isNavbarOpen.value) return;
  isNavbarOpen.value = false;
  document.body.style.overflow = "";
  accountMenuOpen.value = false;
  dashboardMenuOpen.value = false;
};

const goToBag = () => {
  if (!authStore.isAuthenticated) {
    router.push({ path: "/login", query: { redirect: "/cart" } });
    return;
  }
  router.push("/cart");
};

const toggleAccountMenu = () => {
  dashboardMenuOpen.value = false;
  accountMenuOpen.value = !accountMenuOpen.value;
};

const navigateFromAccountMenu = () => {
  accountMenuOpen.value = false;
  closeNavbarMenu();
};

const navigateFromDashboardMenu = () => {
  dashboardMenuOpen.value = false;
  closeNavbarMenu();
};

const logout = () => {
  authStore.logout();
  accountMenuOpen.value = false;
  dashboardMenuOpen.value = false;
  closeNavbarMenu();
  router.push("/");
};

watch(
  () => route.fullPath,
  () => {
    accountMenuOpen.value = false;
    dashboardMenuOpen.value = false;
  },
);
</script>

<style scoped>
/* ========== BASE ========== */
.luxury-navbar {
  background: rgba(255, 248, 228, 0.88);
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 24px rgba(77, 16, 24, 0.06);
  padding: 0;
  transition: box-shadow var(--transition-base), background var(--transition-base);
}

.luxury-navbar.navbar--scrolled {
  background: rgba(255, 248, 228, 0.96);
  box-shadow: 0 12px 30px rgba(77, 16, 24, 0.1);
}

.brand-text {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 1.4rem;
  font-weight: 700;
  letter-spacing: 0.3em;
  color: var(--primary-black);
  transition: letter-spacing var(--transition-base);
}

.brand-text:hover { letter-spacing: 0.35em; }

/* ========== DESKTOP LAYOUT ========== */
.navbar-brand-row {
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  padding: 0.75rem 0 0.1rem;
}

.navbar-menu-row { padding: 0.7rem 0; }

.nav-row-shell {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.navbar-collapse {
  align-items: center;
  justify-content: center;
}

.nav-link {
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--primary-black) !important;
  margin: 0 10px;
  transition: all 0.3s ease;
  position: relative;
  opacity: 0.78;
  padding: 0.4rem 0.1rem;
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
.nav-link.router-link-active::after { width: 100%; }

.nav-link:hover,
.nav-link.router-link-active { opacity: 1; }

.nav-item--dropdown { position: relative; }

.nav-dropdown-icon {
  font-size: 0.8rem;
  transition: transform 0.22s ease;
}

.nav-dropdown-icon--open { transform: rotate(180deg); }

.dropdown-menu-custom {
  position: absolute;
  top: calc(100% + 0.75rem);
  left: 0;
  min-width: 220px;
  padding: 0.65rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.98);
  box-shadow: 0 18px 36px rgba(77, 16, 24, 0.12);
  backdrop-filter: blur(14px);
  display: grid;
  gap: 0.35rem;
  z-index: 20;
  animation: dropdownIn 0.2s ease;
}

@keyframes dropdownIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.dropdown-item-custom {
  display: block;
  padding: 0.7rem 0.9rem;
  border-radius: var(--radius-sm);
  color: var(--primary-black);
  text-decoration: none;
  font-size: 0.82rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  transition: background-color 0.2s ease, opacity 0.2s ease;
  opacity: 0.8;
}

.dropdown-item-custom:hover,
.dropdown-item-custom.router-link-active {
  background: rgba(77, 16, 24, 0.06);
  opacity: 1;
}

.dropdown-menu-custom--account { left: auto; right: 0; }

.nav-links-center { margin: 0 auto; }

.nav-actions {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
}

.nav-bag-btn {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 999px;
  padding: 0;
}

.nav-badge {
  font-size: 0.6rem;
  box-shadow: 0 8px 18px rgba(77, 16, 24, 0.24);
}

.nav-account {
  letter-spacing: 0.18em;
  opacity: 0.72;
  color: var(--primary-black);
  margin: 0;
  padding: 0;
}

/* ========== MOBILE LAYOUT ========== */
.navbar-mobile {
  padding: 0.5rem 0;
  position: relative;
  width: 100%;
}

.mobile-nav-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
}

.mobile-brand {
  text-decoration: none;
  flex-shrink: 0;
}

.mobile-brand .brand-text {
  font-size: 1.15rem;
}

.mobile-actions {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

.nav-bag-btn-mobile {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 999px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  font-size: 1.1rem;
}

/* Custom animated hamburger */
.hamburger {
  width: 2.6rem;
  height: 2.6rem;
  border: 1px solid rgba(77, 16, 24, 0.15);
  border-radius: var(--radius-sm);
  background: transparent;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 0;
  transition: border-color 0.2s ease, background 0.2s ease;
  position: relative;
}

.hamburger:hover {
  border-color: rgba(77, 16, 24, 0.3);
  background: rgba(77, 16, 24, 0.04);
}

.hamburger-line {
  display: block;
  width: 18px;
  height: 2px;
  background: var(--primary-black);
  border-radius: 2px;
  transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.2s ease;
  transform-origin: center;
}

.hamburger--active .hamburger-line--top {
  transform: translateY(7px) rotate(45deg);
}

.hamburger--active .hamburger-line--mid {
  opacity: 0;
  transform: scaleX(0);
}

.hamburger--active .hamburger-line--bot {
  transform: translateY(-7px) rotate(-45deg);
}

/* Mobile menu panel */
.mobile-panel {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  width: 100%;
  background: rgba(255, 248, 228, 0.98);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  box-shadow: 0 20px 48px rgba(77, 16, 24, 0.12);
  z-index: 30;
  max-height: calc(100dvh - 4rem);
  overflow-y: auto;
  padding: 1rem 0 1.5rem;
}

.mobile-menu-enter-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.mobile-menu-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.mobile-nav-links {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  padding-bottom: 1rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
}

.mobile-nav-link {
  display: block;
  padding: 0.85rem 0;
  color: var(--primary-black);
  text-decoration: none;
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  opacity: 0.8;
  transition: opacity 0.2s ease, padding-left 0.2s ease;
  border-radius: var(--radius-sm);
}

.mobile-nav-link:hover,
.mobile-nav-link.router-link-active {
  opacity: 1;
  padding-left: 6px;
}

.mobile-auth-section {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.mobile-user-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0;
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--primary-black);
}

.mobile-user-header i {
  font-size: 1.4rem;
  opacity: 0.6;
}

.mobile-auth-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0;
  color: var(--primary-black);
  text-decoration: none;
  font-size: 0.88rem;
  opacity: 0.7;
  transition: opacity 0.2s ease, padding-left 0.2s ease;
  border-radius: var(--radius-sm);
}

.mobile-auth-link:hover {
  opacity: 1;
  padding-left: 6px;
}

.mobile-auth-link i {
  font-size: 1.05rem;
  width: 1.4rem;
  text-align: center;
  opacity: 0.6;
}

.mobile-logout-btn {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0;
  background: none;
  border: none;
  color: var(--error);
  font-size: 0.88rem;
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s ease, padding-left 0.2s ease;
  margin-top: 0.3rem;
  width: 100%;
  text-align: left;
}

.mobile-logout-btn:hover {
  opacity: 1;
  padding-left: 6px;
}

.mobile-logout-btn i {
  font-size: 1.05rem;
  width: 1.4rem;
  text-align: center;
}

.mobile-guest-text {
  color: var(--ink-soft);
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

/* Overlay */
.mobile-overlay {
  position: fixed;
  inset: 0;
  top: 0;
  z-index: 25;
  background: rgba(20, 10, 12, 0.35);
  backdrop-filter: blur(2px);
}
</style>