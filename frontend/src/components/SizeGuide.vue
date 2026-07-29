<template>
  <div>
    <div class="surface p-4 p-lg-5 mb-5">
      <div class="section-title">
        <p class="section-kicker">Measurements</p>
        <h2 class="display-6">Size Guide</h2>
      </div>

      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <div class="d-flex align-items-start gap-3">
            <div class="size-icon">
              <i class="bi bi-bounding-box-circles"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Bust</h6>
              <p class="small text-muted mb-0">Measure around the fullest part of your bust, keeping the tape horizontal.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="d-flex align-items-start gap-3">
            <div class="size-icon">
              <i class="bi bi-arrows-vertical"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Waist</h6>
              <p class="small text-muted mb-0">Measure around your natural waistline, just above your belly button.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="d-flex align-items-start gap-3">
            <div class="size-icon">
              <i class="bi bi-arrows-expand"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Hips</h6>
              <p class="small text-muted mb-0">Measure around the fullest part of your hips, keeping the tape flat.</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-muted" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else class="mb-5">
        <ul class="nav nav-pills justify-content-center gap-2 mb-4" role="tablist">
          <li class="nav-item" role="presentation" v-for="cat in categories" :key="cat.key">
            <button
              class="nav-link"
              :class="{ active: activeCategory === cat.key }"
              @click="activeCategory = cat.key"
              type="button"
              role="tab"
            >
              {{ cat.label }}
            </button>
          </li>
        </ul>

        <div v-if="currentRows && currentRows.length" class="table-responsive">
          <table class="table size-table">
            <thead>
              <tr>
                <th>Size</th>
                <th>US</th>
                <th>UK</th>
                <th>EU</th>
                <th v-if="showBust">Bust (in)</th>
                <th>Waist (in)</th>
                <th v-if="showHips">Hips (in)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in currentRows" :key="row.size">
                <td class="fw-bold">{{ row.size }}</td>
                <td>{{ row.us }}</td>
                <td>{{ row.uk }}</td>
                <td>{{ row.eu }}</td>
                <td v-if="showBust">{{ row.bust }}</td>
                <td>{{ row.waist }}</td>
                <td v-if="showHips">{{ row.hips }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-center border-top pt-4">
        <p class="small text-muted mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Not sure about your size?
          <a href="#" class="text-decoration-underline" @click.prevent="$emit('switchTab', 'fit')">Check the Fit Guide</a>
          or contact our support team.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { api } from "../lib/api";

defineEmits(["switchTab"]);

const loading = ref(true);
const records = ref([]);
const activeCategory = ref("tops");

const categories = [
  { key: "tops", label: "Tops & Shirts" },
  { key: "bottoms", label: "Bottoms" },
  { key: "dresses", label: "Dresses" },
];

const showBust = computed(() => activeCategory.value !== "bottoms");
const showHips = computed(() => activeCategory.value === "dresses");

const currentRows = computed(() => {
  const rec = records.value.find((r) => r.content.category === activeCategory.value);
  return rec?.content?.rows || [];
});

onMounted(async () => {
  try {
    const data = await api.get("/guidelines?type=size_guide");
    records.value = (data.guidelines || []).filter((g) => g.isActive);
  } catch {
    // fallback empty
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.size-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.62));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.nav-pills .nav-link {
  background: rgba(255, 248, 228, 0.88);
  border: 1px solid rgba(77, 16, 24, 0.14);
  border-radius: var(--radius-sm);
  padding: 0.6rem 1.25rem;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: var(--primary-black);
  transition: all var(--transition-base);
}

.nav-pills .nav-link:hover {
  background: rgba(255, 248, 228, 0.96);
  border-color: rgba(77, 16, 24, 0.28);
  transform: translateY(-1px);
}

.nav-pills .nav-link.active {
  background: var(--primary-black) !important;
  border-color: var(--primary-black) !important;
  color: var(--gold) !important;
}

.size-table {
  margin-bottom: 0;
}

.size-table thead th {
  background: rgba(77, 16, 24, 0.06);
  border-bottom: 2px solid rgba(77, 16, 24, 0.14);
  font-size: 0.8rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  font-weight: 600;
  padding: 0.85rem 1rem;
  white-space: nowrap;
}

.size-table tbody td {
  padding: 0.75rem 1rem;
  font-size: 0.9rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.06);
  white-space: nowrap;
}

.size-table tbody tr:hover {
  background: rgba(255, 241, 184, 0.4);
}
</style>
