<template>
  <Teleport to="body">
    <div v-if="modelValue" class="product-modal" @click.self="$emit('update:modelValue', false)">
    <div class="product-modal__dialog">
      <div class="product-modal__header">
        <div>
          <p class="section-kicker mb-1">{{ editingProductId ? "Editing Product" : "New Product" }}</p>
          <h3 class="h3 mb-0">{{ editingProductId ? "Update Catalog Item" : "Add Product" }}</h3>
        </div>
        <button type="button" class="product-modal__close" @click="$emit('update:modelValue', false)">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <form class="product-modal__form" @submit.prevent="$emit('save')">
        <div class="product-modal__body">
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label">Product Name</label>
              <input v-model="form.name" type="text" class="form-control" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input v-model="form.category" type="text" class="form-control" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Base Price ($)</label>
              <input v-model.number="form.price" type="number" min="0.01" step="0.01" class="form-control" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Original Price ($)</label>
              <input v-model="form.originalPrice" type="number" min="0" step="0.01" class="form-control" placeholder="Optional" />
            </div>

            <div class="col-md-6">
              <label class="form-label">Rating</label>
              <input v-model.number="form.rating" type="number" min="0" max="5" step="0.1" class="form-control" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Reviews</label>
              <input v-model.number="form.reviews" type="number" min="0" step="1" class="form-control" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Sizes</label>
              <input v-model="form.sizes" type="text" class="form-control" placeholder="XS, S, M, L" required />
            </div>

            <div class="col-md-6">
              <label class="form-label">Colors</label>
              <input v-model="form.colors" type="text" class="form-control" placeholder="Black, White, Gray" required />
            </div>

            <div class="col-12">
              <label class="form-label">Product Photo</label>
              <div class="product-modal__upload">
                <input
                  type="file"
                  class="form-control"
                  accept="image/png,image/jpeg,image/webp,image/gif"
                  :disabled="uploadingImage"
                  @change="$emit('imageChange', $event)"
                />
                <div class="form-text">Upload JPG, PNG, WEBP, or GIF up to 5 MB.</div>
                <div v-if="form.image" class="product-modal__preview">
                  <img :src="form.image" alt="Preview" />
                  <button type="button" class="btn btn-outline-dark btn-sm" @click="$emit('clearImage')">
                    <i class="bi bi-trash"></i> Remove
                  </button>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea v-model="form.description" class="form-control" rows="3" required></textarea>
            </div>

            <div class="col-12">
              <div class="country-pricing-panel">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                  <div>
                    <label class="form-label mb-1">Country Prices</label>
                    <div class="form-text mt-0">Add country-specific price overrides.</div>
                  </div>
                  <button type="button" class="btn btn-outline-dark btn-sm" @click="$emit('openCountryPricing')">
                    Manage Country Prices
                  </button>
                </div>

                <div v-if="groupedCountryPrices.length" class="country-price-list">
                  <div
                    v-for="group in groupedCountryPrices"
                    :key="`country-price-${group.price}`"
                    class="country-price-chip"
                  >
                    <div>
                      <div class="fw-semibold">${{ Number(group.price).toLocaleString() }}</div>
                      <div class="small text-muted">{{ group.countries.length }} countries</div>
                    </div>
                    <div class="country-price-chip__actions">
                      <button type="button" class="btn btn-outline-dark btn-sm" @click="$emit('filterCountryPriceBy', group.price)">
                        View
                      </button>
                      <button type="button" class="btn btn-outline-danger country-price-chip__remove" @click="$emit('removeCountryPriceGroup', group.price)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div v-else class="country-pricing-empty">
                  No country-specific prices yet. Base price will be used for all customers.
                </div>
              </div>
            </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input id="modal-featured" v-model="form.featured" class="form-check-input" type="checkbox" />
                  <label for="modal-featured" class="form-check-label">Mark as featured</label>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input id="modal-showed" v-model="form.isShowed" class="form-check-input" type="checkbox" />
                  <label for="modal-showed" class="form-check-label">Show in Collection page</label>
                </div>
              </div>
            </div>

          <div v-if="feedback.message" :class="['alert mt-3 mb-0', feedback.type === 'error' ? 'alert-danger' : 'alert-success']">
            {{ feedback.message }}
          </div>
        </div>

        <div class="product-modal__footer">
          <button type="button" class="btn btn-outline-dark" @click="$emit('update:modelValue', false)">Cancel</button>
          <button type="submit" class="btn btn-luxury" :disabled="saving">
            <i v-if="saving" class="bi bi-arrow-repeat me-1"></i>
            {{ saving ? (editingProductId ? "Saving..." : "Creating...") : editingProductId ? "Save Changes" : "Create Product" }}
          </button>
        </div>
      </form>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue: Boolean,
  form: Object,
  editingProductId: [Number, String],
  saving: Boolean,
  uploadingImage: Boolean,
  feedback: Object,
  groupedCountryPrices: Array,
});

defineEmits([
  "update:modelValue",
  "save",
  "imageChange",
  "clearImage",
  "openCountryPricing",
  "filterCountryPriceBy",
  "removeCountryPriceGroup",
]);
</script>

<style scoped>
.product-modal {
  position: fixed;
  inset: 0;
  z-index: 1060;
  padding: 1.5rem;
  background: rgba(77, 16, 24, 0.45);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: productFadeIn 0.25s ease;
}

@keyframes productFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes productSlideUp {
  from { opacity: 0; transform: translateY(32px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.product-modal__dialog {
  width: min(820px, 100%);
  max-height: 90vh;
  background: #fffcf5;
  border-radius: 1.5rem;
  box-shadow:
    0 24px 80px rgba(77, 16, 24, 0.24),
    0 8px 32px rgba(77, 16, 24, 0.12);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: productSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid rgba(77, 16, 24, 0.06);
}

.product-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.75rem 2rem 1rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.06);
}

.product-modal__close {
  width: 2.5rem;
  height: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 0.85rem;
  background: rgba(255, 241, 184, 0.6);
  color: rgba(77, 16, 24, 0.6);
  font-size: 1rem;
  transition: all 0.2s;
  flex: 0 0 auto;
  cursor: pointer;
}

.product-modal__close:hover {
  background: rgba(77, 16, 24, 0.1);
  color: rgba(77, 16, 24, 0.9);
  border-color: rgba(77, 16, 24, 0.3);
}

.product-modal__form {
  display: flex;
  flex-direction: column;
  max-height: calc(90vh - 6rem);
}

.product-modal__body {
  padding: 1.5rem 2rem;
  overflow-y: auto;
  flex: 1 1 auto;
}

.product-modal__body::-webkit-scrollbar {
  width: 6px;
}

.product-modal__body::-webkit-scrollbar-track {
  background: transparent;
}

.product-modal__body::-webkit-scrollbar-thumb {
  background: rgba(77, 16, 24, 0.14);
  border-radius: 3px;
}

.product-modal__footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.85rem;
  padding: 1.25rem 2rem 1.75rem;
  border-top: 1px solid rgba(77, 16, 24, 0.06);
}

.product-modal__upload {
  padding: 1rem;
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.42);
}

.product-modal__preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  border-radius: 0.9rem;
  background: rgba(255, 241, 184, 0.6);
}

.product-modal__preview img {
  width: 72px;
  height: 84px;
  object-fit: cover;
  border-radius: 0.75rem;
  background: rgba(77, 16, 24, 0.06);
}

.product-modal .country-pricing-panel {
  background: rgba(255, 241, 184, 0.5);
}

.country-pricing-panel {
  padding: 1rem;
  border: 1px dashed rgba(77, 16, 24, 0.2);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.62);
}

.country-pricing-empty {
  color: var(--ink-muted);
  font-size: 0.95rem;
}

.country-price-list {
  display: grid;
  gap: 0.85rem;
}

.country-price-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.74);
}

.country-price-chip__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.country-price-chip__remove {
  width: 2.75rem;
  height: 2.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  padding: 0;
}

@media (max-width: 767px) {
  .product-modal {
    padding: 0.75rem;
    align-items: flex-end;
  }

  .product-modal__dialog {
    max-height: 95vh;
    border-radius: 1.25rem 1.25rem 0 0;
  }

  .product-modal__header {
    padding: 1.25rem 1.25rem 0.75rem;
  }

  .product-modal__body {
    padding: 1rem 1.25rem;
  }

  .product-modal__footer {
    padding: 1rem 1.25rem 1.25rem;
    flex-direction: column-reverse;
  }

  .product-modal__footer .btn {
    width: 100%;
  }
}
</style>
