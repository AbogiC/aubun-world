<template>
  <div>
    <div class="surface p-4 p-lg-5 mb-5">
      <div class="section-title">
        <p class="section-kicker">How We Fit</p>
        <h2 class="display-6">Fit Guide</h2>
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-muted" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <template v-else>
        <div class="row g-4 mb-5">
          <div v-for="fit in fitTypes" :key="fit.fitId" class="col-md-4">
            <div class="fit-card text-center p-4">
              <div class="fit-icon mx-auto mb-3">
                <i :class="fit.icon"></i>
              </div>
              <h5 class="fw-bold mb-2">{{ fit.name }}</h5>
              <p class="small text-muted mb-3">{{ fit.description }}</p>
              <span class="badge fit-badge" :class="fit.badgeClass">{{ fit.badge }}</span>
            </div>
          </div>
        </div>

        <div class="border-top pt-4">
          <h6 class="fw-bold mb-3">Fit Notes by Category</h6>
          <div class="row g-3">
            <div v-for="note in fitNotes" :key="note.category" class="col-md-6">
              <div class="d-flex align-items-start gap-2">
                <i class="bi bi-dot text-muted flex-shrink-0" style="font-size: 1.5rem; line-height: 1.4;"></i>
                <div>
                  <strong>{{ note.category }}:</strong>
                  <span class="text-muted">{{ note.note }}</span>
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

const fitTypes = computed(() =>
  records.value
    .filter((r) => r.content.type === "fit_type" && r.isActive)
    .map((r) => r.content)
);

const fitNotes = computed(() =>
  records.value
    .filter((r) => r.content.type === "fit_note" && r.isActive)
    .map((r) => r.content)
);

onMounted(async () => {
  try {
    const data = await api.get("/guidelines?type=fit_guide");
    records.value = data.guidelines || [];
  } catch {
    // fallback empty
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.fit-card {
  background: rgba(255, 248, 228, 0.6);
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-md);
  height: 100%;
  transition: all var(--transition-base);
}

.fit-card:hover {
  border-color: rgba(77, 16, 24, 0.18);
  box-shadow: var(--shadow-md);
  transform: translateY(-4px);
}

.fit-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.62));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.fit-badge {
  font-size: 0.7rem;
  letter-spacing: 0.06em;
  font-weight: 600;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
}
</style>
