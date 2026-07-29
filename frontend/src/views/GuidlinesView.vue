<template>
  <div class="guidelines-page">
    <section class="guidelines-hero py-5">
      <div class="container text-center py-5">
        <p class="section-kicker" style="color: rgba(254, 181, 17, 0.7);">Guidelines</p>
        <h1 class="display-3 mb-3" style="color: var(--gold-light);">Everything You Need to Know</h1>
        <p class="lead opacity-75" style="color: var(--white);">Size, fit, care & product guidance</p>
      </div>
    </section>

    <section class="py-5">
      <div class="container">
        <ul class="nav nav-tabs guidelines-tabs justify-content-center border-0 mb-5 gap-2" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link guidelines-tab"
              :class="{ active: activeTab === 'size' }"
              @click="activeTab = 'size'"
              type="button"
              role="tab"
            >
              <i class="bi bi-rulers me-2"></i>Size Guide
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link guidelines-tab"
              :class="{ active: activeTab === 'fit' }"
              @click="activeTab = 'fit'"
              type="button"
              role="tab"
            >
              <i class="bi bi-person-standing me-2"></i>Fit Guide
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link guidelines-tab"
              :class="{ active: activeTab === 'care' }"
              @click="activeTab = 'care'"
              type="button"
              role="tab"
            >
              <i class="bi bi-droplet me-2"></i>Care Instructions
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link guidelines-tab"
              :class="{ active: activeTab === 'product' }"
              @click="activeTab = 'product'"
              type="button"
              role="tab"
            >
              <i class="bi bi-box-seam me-2"></i>Product Guide
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div v-show="activeTab === 'size'" class="tab-pane fade show active" role="tabpanel">
            <SizeGuide />
          </div>
          <div v-show="activeTab === 'fit'" class="tab-pane fade show active" role="tabpanel">
            <FitGuide />
          </div>
          <div v-show="activeTab === 'care'" class="tab-pane fade show active" role="tabpanel">
            <CareInstructions />
          </div>
          <div v-show="activeTab === 'product'" class="tab-pane fade show active" role="tabpanel">
            <ProductGuide />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from "vue";
import SizeGuide from "../components/SizeGuide.vue";
import FitGuide from "../components/FitGuide.vue";
import CareInstructions from "../components/CareInstructions.vue";
import ProductGuide from "../components/ProductGuide.vue";

const activeTab = ref("size");
</script>

<style scoped>
.guidelines-hero {
  background:
    linear-gradient(180deg, rgba(77, 16, 24, 0.98), rgba(108, 24, 35, 0.94)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.12), transparent 45%);
}

.guidelines-tabs {
  flex-wrap: wrap;
}

.guidelines-tab {
  background: rgba(255, 248, 228, 0.88);
  border: 1px solid rgba(77, 16, 24, 0.18);
  border-radius: var(--radius-md) !important;
  padding: 0.85rem 1.75rem;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: var(--primary-black);
  transition: all var(--transition-base);
  white-space: nowrap;
}

.guidelines-tab:hover {
  background: rgba(255, 248, 228, 0.96);
  border-color: rgba(77, 16, 24, 0.32);
  transform: translateY(-2px);
}

.guidelines-tab.active {
  background: var(--primary-black) !important;
  border-color: var(--primary-black) !important;
  color: var(--gold) !important;
}
</style>
