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
import Navbar from "./components/Navbar.vue";
import Footer from "./components/Footer.vue";
</script>
