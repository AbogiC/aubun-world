<template>
  <div id="app" class="app-shell">
    <LoadingScreen :visible="isLoading" />
    <template v-if="!isLoading">
      <Navbar />
      <main class="app-main">
        <router-view v-slot="{ Component, route }">
          <Transition name="page" mode="out-in">
            <component :is="Component" :key="route.fullPath" />
          </Transition>
        </router-view>
      </main>
      <Footer />
      <PermissionConsent />
      <ToastContainer />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import LoadingScreen from "./components/LoadingScreen.vue";
import PermissionConsent from "./components/PermissionConsent.vue";
import Navbar from "./components/Navbar.vue";
import Footer from "./components/Footer.vue";
import ToastContainer from "./components/ToastContainer.vue";
import { resolveCustomerLocationOnLoad } from "./lib/location";
import { api } from "./lib/api";
import { applyCustomFont } from "./lib/customFont";
import { applyCustomTheme } from "./lib/customTheme";
import { useProductsStore } from "./stores/products";
import { useCartStore } from "./stores/cart";
import { useAuthStore } from "./stores/auth";
import { useNotificationStore } from "./stores/notifications";

const productsStore = useProductsStore();
const cartStore = useCartStore();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const isLoading = ref(true);

onMounted(async () => {
  // Kick off non-blocking tasks in parallel — don't block first paint (LCP + main-thread work)
  const locationPromise = resolveCustomerLocationOnLoad();

  // Allow paint immediately: hide loading screen as soon as critical data is ready
  // Run home-view + products + auth concurrently
  const homeViewPromise = api
    .get("/home-view")
    .then((data) => {
      if (data?.settings) {
        applyCustomFont(data.settings);
        applyCustomTheme(data.settings);
      }
    })
    .catch(() => {});

  const productsPromise = !productsStore.loaded ? productsStore.fetchProducts() : Promise.resolve();
  const authPromise = authStore.initialize();

  // Wait for the minimal critical set (auth + products) before showing shell
  // Location + theme are non-critical and run in background
  await Promise.all([locationPromise, homeViewPromise, productsPromise, authPromise]);

  // Defer non-critical work off main thread
  const idle = window.requestIdleCallback || ((cb) => setTimeout(cb, 1));
  idle(() => {
    cartStore.refreshFromApi();
    if (authStore.isAuthenticated) {
      notificationStore.initialize().then(() => notificationStore.startPolling());
    }
  });

  // Remove artificial 400ms delay — show content as soon as ready (LCP improvement)
  // Keep a minimal delay only if loading was very fast to avoid flash
  isLoading.value = false;
});
</script>
