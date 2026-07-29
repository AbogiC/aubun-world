<template>
  <div>
    <div class="surface p-4 p-lg-5 mb-5">
      <div class="section-title">
        <p class="section-kicker">Keep them lasting</p>
        <h2 class="display-6">Care Instructions</h2>
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-muted" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <template v-else>
        <div class="row g-4 mb-5">
          <div v-for="item in careIcons" :key="item.label" class="col-md-3 col-6">
            <div class="care-card text-center p-3">
              <div class="care-icon mx-auto mb-3">
                <i :class="item.icon"></i>
              </div>
              <h6 class="fw-bold mb-1">{{ item.label }}</h6>
              <p class="small text-muted mb-0">{{ item.detail }}</p>
            </div>
          </div>
        </div>

        <div class="border-top pt-4">
          <h6 class="fw-bold mb-3">Fabric-Specific Care</h6>
          <div class="accordion" id="careAccordion">
            <div v-for="(fabric, index) in fabricCares" :key="fabric.name" class="accordion-item">
              <h2 class="accordion-header">
                <button
                  class="accordion-button"
                  :class="{ collapsed: index !== 0 }"
                  type="button"
                  data-bs-toggle="collapse"
                  :data-bs-target="'#fabric' + index"
                >
                  {{ fabric.name }}
                </button>
              </h2>
              <div
                :id="'fabric' + index"
                class="accordion-collapse collapse"
                :class="{ show: index === 0 }"
                data-bs-parent="#careAccordion"
              >
                <div class="accordion-body">
                  <p class="mb-0">{{ fabric.care }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { api } from "../lib/api";

const loading = ref(true);
const records = ref([]);

const careIcons = computed(() =>
  records.value
    .filter((r) => r.content.type === "care_icon" && r.isActive)
    .map((r) => r.content)
);

const fabricCares = computed(() =>
  records.value
    .filter((r) => r.content.type === "fabric_care" && r.isActive)
    .map((r) => r.content)
);

onMounted(async () => {
  try {
    const data = await api.get("/guidelines?type=care_instruction");
    records.value = data.guidelines || [];
  } catch {
    // fallback empty
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.care-card {
  background: rgba(255, 248, 228, 0.6);
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-md);
  height: 100%;
  transition: all var(--transition-base);
}

.care-card:hover {
  border-color: rgba(77, 16, 24, 0.18);
  box-shadow: var(--shadow-md);
  transform: translateY(-4px);
}

.care-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.62));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}
</style>
