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
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import LoadingScreen from "./components/LoadingScreen.vue";
import PermissionConsent from "./components/PermissionConsent.vue";
import Navbar from "./components/Navbar.vue";
import Footer from "./components/Footer.vue";
import { resolveCustomerLocationOnLoad } from "./lib/location";
import { useProductsStore } from "./stores/products";
import { useCartStore } from "./stores/cart";
import { useAuthStore } from "./stores/auth";

const productsStore = useProductsStore();
const cartStore = useCartStore();
const authStore = useAuthStore();

const isLoading = ref(true);

onMounted(async () => {
  await resolveCustomerLocationOnLoad();

  if (!productsStore.loaded) {
    await productsStore.fetchProducts();
  }

  await authStore.initialize();
  cartStore.refreshFromApi();

  setTimeout(() => {
    isLoading.value = false;
  }, 400);
});
</script>
