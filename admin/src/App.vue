<template>
  <div id="app" class="app-shell">
    <LoadingScreen :visible="isLoading" />
    <template v-if="!isLoading">
      <!-- Sidebar -->
      <aside
        class="admin-sidebar"
        :class="{ collapsed: sidebarCollapsed }"
        ref="sidebarRef"
      >
        <div class="admin-sidebar-header">
          <button
            class="admin-sidebar-brand"
            @click="toggleSidebar"
            :aria-expanded="!sidebarCollapsed"
            :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            type="button"
          >
            <i class="bi bi-gem" style="font-size: 1.5rem; color: var(--primary-black);"></i>
            <span class="brand-text">AUBUN ADMIN</span>
          </button>
        </div>

        <nav class="admin-sidebar-nav" aria-label="Admin navigation">
          <div class="nav-section">
            <span class="nav-section-label">Dashboard</span>
            <router-link
              to="/products"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/products') }"
            >
              <i class="bi bi-grid"></i>
              <span class="nav-link-text">Products</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Products</div>
            </router-link>
            <router-link
              to="/orders"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/orders') }"
            >
              <i class="bi bi-box-seam"></i>
              <span class="nav-link-text">Orders</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Orders</div>
            </router-link>
          </div>

          <div class="nav-section">
            <span class="nav-section-label">Settings</span>
            <router-link
              to="/shipping"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/shipping') }"
            >
              <i class="bi bi-truck"></i>
              <span class="nav-link-text">Shipping</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Shipping Settings</div>
            </router-link>
            <router-link
              to="/vouchers"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/vouchers') }"
            >
              <i class="bi bi-ticket-perforated"></i>
              <span class="nav-link-text">Vouchers</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Voucher Discounts</div>
            </router-link>
          </div>

          <div class="nav-section">
            <span class="nav-section-label">Content</span>
            <router-link
              to="/news"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/news') }"
            >
              <i class="bi bi-newspaper"></i>
              <span class="nav-link-text">News</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">News Articles</div>
            </router-link>
            <router-link
              to="/guidelines"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/guidelines') }"
            >
              <i class="bi bi-journal-text"></i>
              <span class="nav-link-text">Guidelines</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Guidelines Management</div>
            </router-link>
            <router-link
              to="/stocklist"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/stocklist') }"
            >
              <i class="bi bi-shop"></i>
              <span class="nav-link-text">Stocklist</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Stockists Management</div>
            </router-link>
            <router-link
              to="/home-customization"
              class="nav-link-item"
              :class="{ 'router-link-active': isActiveRoute('/home-customization') }"
            >
              <i class="bi bi-house-door"></i>
              <span class="nav-link-text">Home Customization</span>
              <div class="nav-tooltip" v-if="sidebarCollapsed">Homepage Settings</div>
            </router-link>
          </div>
        </nav>

        <div class="admin-sidebar-footer">
          <div class="admin-user-info">
            <div class="admin-user-avatar">
              {{ userInitial }}
            </div>
            <div class="admin-user-details">
              <div class="admin-user-name">{{ authStore.user?.name || "Admin" }}</div>
              <div class="admin-user-role">{{ formatRole(authStore.user?.role) }}</div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
          <div class="admin-header-left">
            <h1 class="admin-page-title">{{ pageTitle }}</h1>
          </div>
          <div class="admin-header-right">
            <button class="admin-header-btn" @click="goToStorefront" title="View Storefront">
              <i class="bi bi-shop-window"></i>
            </button>
            <button
              class="admin-header-btn"
              @click="toggleNotifications"
              title="Notifications"
            >
              <i class="bi bi-bell"></i>
              <span
                v-if="notificationStore.unreadCount"
                class="badge"
              >
                {{ notificationStore.unreadCount > 9 ? "9+" : notificationStore.unreadCount }}
              </span>
            </button>
            <div class="dropdown">
              <button
                class="admin-header-btn dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-person-circle" style="font-size: 1.25rem;"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end surface-elevated">
                <li>
                  <router-link to="/profile" class="dropdown-item">
                    <i class="bi bi-gear me-2"></i> Profile
                  </router-link>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                  <button class="dropdown-item text-danger" @click="logout">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </header>

        <!-- Content -->
        <div class="admin-content">
          <router-view v-slot="{ Component, route }">
            <Transition name="page" mode="out-in">
              <component :is="Component" :key="route.fullPath" />
            </Transition>
          </router-view>
        </div>
      </main>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import LoadingScreen from "./components/LoadingScreen.vue";
import { useAuthStore } from "./stores/auth";
import { useNotificationStore } from "./stores/notifications";
import { useProductsStore } from "./stores/products";
import { useCartStore } from "./stores/cart";
import { resolveCustomerLocationOnLoad } from "./lib/location";

const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const productsStore = useProductsStore();
const cartStore = useCartStore();
const router = useRouter();
const route = useRoute();

const isLoading = ref(true);
const sidebarCollapsed = ref(true);
const sidebarRef = ref(null);

const userInitial = computed(() => {
  const name = authStore.user?.name || "A";
  return name.charAt(0).toUpperCase();
});

const pageTitle = computed(() => {
  const titles = {
    "/products": "Products",
    "/orders": "Orders",
    "/shipping": "Shipping Settings",
    "/vouchers": "Vouchers",
    "/news": "News Articles",
    "/guidelines": "Guidelines",
    "/stocklist": "Stocklist",
    "/home-customization": "Home Customization",
    "/profile": "Profile",
  };
  return titles[route.path] || "Dashboard";
});

const formatRole = (role) => {
  if (!role) return "User";
  return role.charAt(0).toUpperCase() + role.slice(1);
};

const isActiveRoute = (path) => route.path === path;

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleNotifications = () => {
  // Notification bell click - could open a dropdown or navigate
  // For now, we'll rely on the dropdown from the user menu
};

const goToStorefront = () => {
  window.open("/", "_blank");
};

const logout = () => {
  authStore.logout();
  router.push("/login");
};

onMounted(async () => {
  await resolveCustomerLocationOnLoad();

  if (!productsStore.loaded) {
    await productsStore.fetchProducts();
  }

  await authStore.initialize();
  cartStore.refreshFromApi();

  if (authStore.isAuthenticated) {
    await notificationStore.initialize();
    notificationStore.startPolling();
  }

  // Restore sidebar state from localStorage
  const savedCollapsed = localStorage.getItem("admin_sidebar_collapsed");
  if (savedCollapsed !== null) {
    sidebarCollapsed.value = savedCollapsed === "true";
  }

  setTimeout(() => {
    isLoading.value = false;
  }, 400);
});

watch(sidebarCollapsed, (value) => {
  localStorage.setItem("admin_sidebar_collapsed", value.toString());
});
</script>

<style scoped>
/* Scoped styles are in style.css global */
</style>