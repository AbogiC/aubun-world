<template>
  <Teleport to="body">
    <div v-if="modelValue" class="country-modal" @click.self="close">
      <div class="country-modal__dialog surface elevated">
        <div class="country-modal__header">
          <div>
            <p class="section-kicker mb-2">Country Pricing</p>
            <h3 class="h3 mb-1">Set Prices by Continent</h3>
            <p class="text-muted mb-0">Select a continent or individual countries, then apply one price to all selected countries.</p>
          </div>
          <button type="button" class="btn btn-outline-dark btn-sm" @click="close">
            Close
          </button>
        </div>

        <div class="country-modal__toolbar">
          <div class="country-modal__toolbar-input">
            <label class="form-label">Selected Price</label>
            <input v-model="modalPrice" type="number" min="0.01" step="0.01" class="form-control" placeholder="120.00" />
          </div>
          <div class="country-modal__toolbar-actions">
            <button type="button" class="btn btn-dark" :disabled="!selectedCountries.length || !modalPrice" @click="applyPrice">
              Apply to Selected
            </button>
            <button type="button" class="btn btn-outline-danger" :disabled="!selectedCountries.length" @click="removeSelected">
              Remove Selected
            </button>
            <button type="button" class="btn btn-outline-dark" :disabled="!selectedCountries.length" @click="clearSelection">
              Clear Selection
            </button>
          </div>
        </div>

        <div v-if="priceFilterOptions.length" class="country-modal__filters">
          <button
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = 'all'"
          >
            All Countries
          </button>
          <button
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === 'unassigned' ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = 'unassigned'"
          >
            Base Price
          </button>
          <button
            v-for="price in priceFilterOptions"
            :key="`price-filter-${price}`"
            type="button"
            class="btn btn-sm"
            :class="activePriceFilter === price ? 'btn-dark' : 'btn-outline-dark'"
            @click="activePriceFilter = price"
          >
            ${{ Number(price).toLocaleString() }}
          </button>
        </div>

        <div class="country-modal__selection" v-if="selectedCountries.length">
          <span
            v-for="country in selectedCountries"
            :key="`selected-${country}`"
            class="country-modal__selection-chip"
          >
            {{ country }}
          </span>
        </div>

        <div class="country-modal__body">
          <section
            v-for="continent in countryPricingCatalog"
            :key="continent.name"
            class="country-continent"
          >
            <div class="country-continent__header">
              <label class="form-check d-flex align-items-center gap-2 mb-0">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="isContinentFullySelected(continent)"
                  @change="toggleContinent(continent)"
                />
                <span class="fw-semibold">{{ continent.name }}</span>
              </label>
              <span class="small text-muted">{{ selectedCountInContinent(continent) }}/{{ continent.countries.length }} selected</span>
            </div>

            <div class="country-grid">
              <label
                v-for="country in filteredCountriesForContinent(continent)"
                :key="`${continent.name}-${country}`"
                class="country-option"
                :class="{ 'country-option--active': isCountrySelected(country) }"
              >
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="isCountrySelected(country)"
                  @change="toggleCountry(country)"
                />
                <div class="country-option__body">
                  <span class="country-option__name">{{ country }}</span>
                  <span v-if="countryPriceMap[country] !== undefined" class="country-option__price">
                    ${{ Number(countryPriceMap[country]).toLocaleString() }}
                  </span>
                  <span v-else class="country-option__price country-option__price--muted">
                    Base price
                  </span>
                </div>
              </label>
            </div>
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  modelValue: Boolean,
  countryPricingCatalog: Array,
  countryPriceMap: Object,
  priceFilterOptions: Array,
});

const emit = defineEmits([
  "update:modelValue",
  "apply",
  "removeSelected",
  "clearSelection",
]);

const modalPrice = ref("");
const selectedCountries = ref([]);
const activePriceFilter = ref("all");

const close = () => {
  emit("update:modelValue", false);
  modalPrice.value = "";
  selectedCountries.value = [];
  activePriceFilter.value = "all";
};

const open = (initialCountries = []) => {
  modalPrice.value = "";
  selectedCountries.value = [...initialCountries];
  activePriceFilter.value = "all";
};

const openWithFilter = (price, countries = []) => {
  modalPrice.value = price;
  selectedCountries.value = [...countries];
  activePriceFilter.value = Number(price);
};

const isCountrySelected = (country) => selectedCountries.value.includes(country);

const toggleCountry = (country) => {
  if (isCountrySelected(country)) {
    selectedCountries.value = selectedCountries.value.filter((c) => c !== country);
    return;
  }
  selectedCountries.value = [...selectedCountries.value, country];
};

const isContinentFullySelected = (continent) =>
  continent.countries.every((country) => selectedCountries.value.includes(country));

const selectedCountInContinent = (continent) =>
  continent.countries.filter((country) => selectedCountries.value.includes(country)).length;

const filteredCountriesForContinent = (continent) =>
  continent.countries.filter((country) => {
    if (activePriceFilter.value === "all") return true;
    const assignedPrice = props.countryPriceMap[country];
    if (activePriceFilter.value === "unassigned") return assignedPrice === undefined;
    return Number(assignedPrice) === Number(activePriceFilter.value);
  });

const toggleContinent = (continent) => {
  const visibleCountries = filteredCountriesForContinent(continent);
  if (visibleCountries.length === 0) return;
  if (visibleCountries.every((country) => selectedCountries.value.includes(country))) {
    selectedCountries.value = selectedCountries.value.filter(
      (c) => !visibleCountries.includes(c),
    );
    return;
  }
  selectedCountries.value = Array.from(
    new Set([...selectedCountries.value, ...visibleCountries]),
  );
};

const applyPrice = () => {
  const price = Number(modalPrice.value);
  if (!selectedCountries.value.length || !price) return;
  emit("apply", price, [...selectedCountries.value]);
};

const removeSelected = () => {
  if (!selectedCountries.value.length) return;
  emit("removeSelected", [...selectedCountries.value]);
  selectedCountries.value = [];
};

const clearSelection = () => {
  selectedCountries.value = [];
};

defineExpose({ open, openWithFilter });
</script>

<style scoped>
.country-modal {
  position: fixed;
  inset: 0;
  z-index: 1070;
  padding: 1.5rem;
  background: rgba(77, 16, 24, 0.38);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.country-modal__dialog {
  width: min(1080px, 100%);
  max-height: 90vh;
  padding: 1.5rem;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: #fffcf5;
  border-radius: 1.5rem;
  box-shadow:
    0 24px 80px rgba(77, 16, 24, 0.24),
    0 8px 32px rgba(77, 16, 24, 0.12);
  border: 1px solid rgba(77, 16, 24, 0.06);
}

.country-modal__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.country-modal__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
  padding: 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(255, 241, 184, 0.72);
}

.country-modal__toolbar-input {
  width: min(220px, 100%);
}

.country-modal__toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.country-modal__selection {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 1rem 0 0.5rem;
}

.country-modal__selection-chip {
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(77, 16, 24, 0.1);
  font-size: 0.85rem;
}

.country-modal__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-top: 1rem;
}

.country-modal__body {
  overflow: auto;
  margin-top: 1rem;
  padding-right: 0.25rem;
  display: grid;
  gap: 1rem;
}

.country-continent {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  padding: 1rem;
  background: rgba(255, 241, 184, 0.68);
}

.country-continent__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.country-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.country-option {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 0.95rem;
  background: rgba(255, 241, 184, 0.78);
  cursor: pointer;
}

.country-option--active {
  border-color: rgba(77, 16, 24, 0.3);
  box-shadow: 0 10px 22px rgba(77, 16, 24, 0.12);
}

.country-option__body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.country-option__name {
  font-weight: 600;
  line-height: 1.3;
}

.country-option__price {
  font-size: 0.82rem;
  color: var(--ink-muted);
}

.country-option__price--muted {
  opacity: 0.72;
}

@media (max-width: 991px) {
  .country-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .country-modal {
    padding: 0.75rem;
  }

  .country-modal__dialog {
    max-height: 95vh;
    padding: 1rem;
    border-radius: 1.25rem 1.25rem 0 0;
    align-items: flex-end;
  }

  .country-modal__header,
  .country-continent__header {
    flex-direction: column;
    align-items: stretch;
  }

  .country-grid {
    grid-template-columns: 1fr;
  }
}
</style>
