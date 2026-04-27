<template>
  <div id="app" class="app-shell">
    <Navbar />
    <main class="app-main">
      <router-view v-slot="{ Component, route }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </Transition>
      </router-view>
    </main>
    <Footer />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import Navbar from "./components/Navbar.vue";
import Footer from "./components/Footer.vue";
import { useProductsStore } from "./stores/products";
import { useCartStore } from "./stores/cart";
import { useAuthStore } from "./stores/auth";

const productsStore = useProductsStore();
const cartStore = useCartStore();
const authStore = useAuthStore();

onMounted(async () => {
  if (!productsStore.loaded) {
    productsStore.fetchProducts();
  }

  await authStore.initialize();
  cartStore.refreshFromApi();
});
</script>
