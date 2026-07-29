<template>
  <div>
    <div class="surface p-4 p-lg-5 mb-4">
      <div class="section-title">
        <p class="section-kicker">All-in-One</p>
        <h2 class="display-6">Universal Product Guide</h2>
      </div>
      <p class="text-center text-muted mb-4">
        Everything you need to know about sizing, fit, and care — all in one place.
      </p>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-muted" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <template v-else>
        <div class="accordion" id="universalAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button
                class="accordion-button"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#uniSize"
              >
                <i class="bi bi-rulers me-2"></i> Size Guide
              </button>
            </h2>
            <div id="uniSize" class="accordion-collapse collapse show" data-bs-parent="#universalAccordion">
              <div class="accordion-body">
                <div v-if="sizeRecords.length" class="mb-3">
                  <select v-model="uniSizeCategory" class="form-select form-select-sm w-auto">
                    <option v-for="r in sizeRecords" :key="r.content.category" :value="r.content.category">
                      {{ r.title }}
                    </option>
                  </select>
                </div>
                <div v-if="uniSizeRows.length" class="table-responsive">
                  <table class="table size-table">
                    <thead>
                      <tr>
                        <th>Size</th>
                        <th>US</th>
                        <th>UK</th>
                        <th>EU</th>
                        <th v-if="uniSizeCategory !== 'bottoms'">Bust (in)</th>
                        <th>Waist (in)</th>
                        <th v-if="uniSizeCategory === 'dresses'">Hips (in)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in uniSizeRows" :key="row.size">
                        <td class="fw-bold">{{ row.size }}</td>
                        <td>{{ row.us }}</td>
                        <td>{{ row.uk }}</td>
                        <td>{{ row.eu }}</td>
                        <td v-if="uniSizeCategory !== 'bottoms'">{{ row.bust }}</td>
                        <td>{{ row.waist }}</td>
                        <td v-if="uniSizeCategory === 'dresses'">{{ row.hips }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button
                class="accordion-button collapsed"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#uniFit"
              >
                <i class="bi bi-person-standing me-2"></i> Fit Guide
              </button>
            </h2>
            <div id="uniFit" class="accordion-collapse collapse" data-bs-parent="#universalAccordion">
              <div class="accordion-body">
                <div class="row g-3">
                  <div v-for="fit in fitTypes" :key="fit.fitId" class="col-md-4">
                    <div class="uni-fit-card p-3 text-center">
                      <i :class="fit.icon" style="font-size: 1.5rem;"></i>
                      <h6 class="fw-bold mt-2 mb-1">{{ fit.name }}</h6>
                      <p class="small text-muted mb-0">{{ fit.summary }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button
                class="accordion-button collapsed"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#uniCare"
              >
                <i class="bi bi-droplet me-2"></i> Care Instructions
              </button>
            </h2>
            <div id="uniCare" class="accordion-collapse collapse" data-bs-parent="#universalAccordion">
              <div class="accordion-body">
                <div class="row g-3">
                  <div v-for="item in careIcons" :key="item.label" class="col-md-3 col-6">
                    <div class="text-center">
                      <i :class="item.icon" style="font-size: 1.5rem;"></i>
                      <h6 class="fw-bold small mt-2 mb-0">{{ item.label }}</h6>
                      <p class="small text-muted mb-0">{{ item.detail }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div class="surface p-4 p-lg-5">
      <div class="section-title">
        <p class="section-kicker">By Product</p>
        <h2 class="display-6">Specific Product Guide</h2>
      </div>
      <p class="text-center text-muted mb-4">
        Browse our catalog to find size, fit, and care info for a specific product.
      </p>

      <div v-if="productsLoading" class="text-center py-4">
        <div class="spinner-border text-muted" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else-if="products.length === 0" class="text-center py-4">
        <i class="bi bi-box-seam text-muted" style="font-size: 2.5rem;"></i>
        <p class="text-muted mt-2">No products available yet.</p>
      </div>

      <div v-else>
        <div class="mb-4">
          <select v-model="selectedProductId" class="form-select">
            <option value="" disabled>Select a product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} — ${{ p.price }}</option>
          </select>
        </div>

        <div v-if="selectedProduct" class="specific-guide">
          <div class="row align-items-center g-4 mb-4">
            <div class="col-md-4">
              <img
                :src="selectedProduct.image"
                :alt="selectedProduct.name"
                class="w-100 surface"
                style="border-radius: var(--radius-md); height: 240px; object-fit: cover;"
              />
            </div>
            <div class="col-md-8">
              <span class="badge bg-dark mb-2">{{ selectedProduct.category }}</span>
              <h4 class="fw-bold mb-2">{{ selectedProduct.name }}</h4>
              <p class="text-muted mb-2">{{ selectedProduct.description }}</p>
              <div class="d-flex align-items-center gap-2">
                <div class="text-warning">
                  <i v-for="star in 5" :key="star" :class="['bi', star <= Math.floor(selectedProduct.rating) ? 'bi-star-fill' : 'bi-star']"></i>
                </div>
                <span class="small text-muted">{{ selectedProduct.rating }} · {{ selectedProduct.reviews }} reviews</span>
              </div>
            </div>
          </div>

          <div class="border-top pt-4">
            <div class="row g-4">
              <div class="col-md-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-rulers me-1"></i> Available Sizes</h6>
                <div class="d-flex gap-2 flex-wrap">
                  <span v-for="s in selectedProduct.sizes" :key="s" class="badge size-badge">{{ s }}</span>
                </div>
              </div>
              <div class="col-md-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-palette me-1"></i> Available Colors</h6>
                <div class="d-flex gap-2 flex-wrap">
                  <span v-for="c in selectedProduct.colors" :key="c" class="badge size-badge">{{ c }}</span>
                </div>
              </div>
              <div class="col-md-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-tag me-1"></i> Price</h6>
                <p class="mb-0">
                  <strong>${{ selectedProduct.price.toLocaleString() }}</strong>
                  <span v-if="selectedProduct.originalPrice" class="text-decoration-line-through text-muted ms-2 small">
                    ${{ selectedProduct.originalPrice.toLocaleString() }}
                  </span>
                </p>
              </div>
            </div>
          </div>

          <div class="border-top pt-4 mt-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i> Product-Specific Notes</h6>
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i> This piece runs <strong>{{ productFitNote }}</strong>. Refer to the size chart above for exact measurements.</li>
              <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i> Model is 5'9" (175 cm) and wears size {{ selectedProduct.category === 'Bottoms' ? 'S' : 'M' }}.</li>
              <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i> For care, refer to the fabric composition on the product label or check our general care guide above.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useProductsStore } from "../stores/products";
import { api } from "../lib/api";

const productsStore = useProductsStore();

const loading = ref(true);
const productsLoading = ref(true);
const uniSizeCategory = ref("tops");
const sizeRecords = ref([]);
const fitRecords = ref([]);
const careRecords = ref([]);

const fitTypes = computed(() =>
  fitRecords.value
    .filter((r) => r.content.type === "fit_type")
    .map((r) => r.content)
);

const careIcons = computed(() =>
  careRecords.value
    .filter((r) => r.content.type === "care_icon")
    .map((r) => r.content)
);

const uniSizeRows = computed(() => {
  const rec = sizeRecords.value.find((r) => r.content.category === uniSizeCategory.value);
  return rec?.content?.rows || [];
});

const products = computed(() => productsStore.products);

const selectedProductId = ref("");

const selectedProduct = computed(() => {
  if (!selectedProductId.value) return null;
  return products.value.find((p) => p.id === parseInt(selectedProductId.value)) || null;
});

const productFitNote = computed(() => {
  if (!selectedProduct.value) return "true to size";
  const cat = selectedProduct.value.category?.toLowerCase() || "";
  if (cat.includes("blazer") || cat.includes("tailor") || cat.includes("slim")) return "slim to size";
  if (cat.includes("knit") || cat.includes("sweater")) return "true to size with relaxed ease";
  return "true to size";
});

onMounted(async () => {
  try {
    const [sizeData, fitData, careData] = await Promise.all([
      api.get("/guidelines?type=size_guide"),
      api.get("/guidelines?type=fit_guide"),
      api.get("/guidelines?type=care_instruction"),
    ]);
    sizeRecords.value = (sizeData.guidelines || []).filter((g) => g.isActive);
    fitRecords.value = (fitData.guidelines || []).filter((g) => g.isActive);
    careRecords.value = (careData.guidelines || []).filter((g) => g.isActive);
  } catch {
    // fallback empty
  } finally {
    loading.value = false;
  }

  if (!productsStore.loaded) {
    await productsStore.fetchProducts();
  }
  productsLoading.value = false;
});
</script>

<style scoped>
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
  padding: 0.75rem 0.85rem;
  white-space: nowrap;
}

.size-table tbody td {
  padding: 0.65rem 0.85rem;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.06);
  white-space: nowrap;
}

.size-table tbody tr:hover {
  background: rgba(255, 241, 184, 0.4);
}

.uni-fit-card {
  background: rgba(255, 248, 228, 0.6);
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-sm);
  height: 100%;
}

.size-badge {
  background: rgba(77, 16, 24, 0.08);
  color: var(--primary-black);
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.85rem;
  border-radius: var(--radius-xs);
}
</style>
