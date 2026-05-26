<template>
  <section class="placeholder-page py-5">
    <div class="container">
      <div class="placeholder-card surface text-center">
        <p class="section-kicker mb-3">Coming Soon</p>
        <h1>Guidlines</h1>
        <p class="mb-0 text-muted">
          This page is ready in the navigation and can be filled with sizing, styling, or care
          guidance later.
        </p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.placeholder-page {
  min-height: 70vh;
  display: flex;
  align-items: center;
}

.placeholder-card {
  max-width: 48rem;
  margin: 0 auto;
  padding: clamp(2rem, 5vw, 4rem);
  border-radius: 2rem;
}

.placeholder-card h1 {
  margin-bottom: 1rem;
}
</style>
