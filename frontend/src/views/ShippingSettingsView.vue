<template>
  <div class="shipping-settings-page py-5">
    <section class="container mb-4">
      <div class="shipping-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">Logistics Management</p>
            <h1 class="display-5 mb-3">Shipping Settings</h1>
            <p class="text-muted mb-0">
              Register your existing shop countries, map destination countries by continent, and assign distance-based shipping costs from one place.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="shipping-metrics">
              <div class="metric-card">
                <span class="metric-label">Shop Countries</span>
                <strong>{{ shopCountries.length }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Mapped Countries</span>
                <strong>{{ shippingMappings.length }}</strong>
              </div>
              <div class="metric-card">
                <span class="metric-label">Active Rules</span>
                <strong>{{ activeMappingsCount }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container">
      <div class="row g-4">
        <div class="col-xl-4">
          <div class="surface p-4 p-lg-5 h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Step 1</p>
                <h2 class="h3 mb-1">Shop Countries</h2>
                <p class="text-muted mb-0">Add every country where you already have a shop.</p>
              </div>
            </div>

            <form class="shop-form" @submit.prevent="addShopCountry">
              <label class="form-label">Shop Country</label>
              <div class="d-flex gap-2">
                <input
                  v-model="shopCountryForm.countryName"
                  type="text"
                  class="form-control"
                  placeholder="Example: Indonesia"
                  :disabled="addingShopCountry"
                  required
                />
                <button type="submit" class="btn btn-dark" :disabled="addingShopCountry">
                  {{ addingShopCountry ? "Saving..." : "Add" }}
                </button>
              </div>
            </form>

            <div class="shop-list mt-4">
              <div
                v-for="shopCountry in shopCountries"
                :key="shopCountry.id"
                class="shop-card"
              >
                <div>
                  <div class="fw-semibold">{{ shopCountry.countryName }}</div>
                  <div class="small text-muted">
                    Used by {{ usageCountByShopCountryId[shopCountry.id] || 0 }} destination countries
                  </div>
                </div>
                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm"
                  :disabled="deletingShopCountryId === shopCountry.id"
                  @click="removeShopCountry(shopCountry)"
                >
                  {{ deletingShopCountryId === shopCountry.id ? "Removing..." : "Delete" }}
                </button>
              </div>

              <div v-if="!shopCountries.length && !loading" class="empty-state text-center py-4">
                <i class="bi bi-shop display-6 text-muted"></i>
                <p class="text-muted mb-0 mt-3">No shop countries yet. Add one to start mapping shipping routes.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="surface p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
              <div>
                <p class="section-kicker mb-2">Step 2</p>
                <h2 class="h3 mb-1">{{ editingGroupKey ? "Edit Shipping Rule" : "Create Shipping Rule" }}</h2>
                <p class="text-muted mb-0">
                  Select one shop country, choose destination countries by continent, then define one or more distance-based shipping costs.
                </p>
              </div>
              <button
                v-if="editingGroupKey"
                type="button"
                class="btn btn-outline-dark btn-sm"
                @click="resetEditor"
              >
                Cancel Edit
              </button>
            </div>

            <div v-if="feedback.message" :class="['alert mb-4', feedback.type === 'error' ? 'alert-danger' : 'alert-success']">
              {{ feedback.message }}
            </div>

            <div v-if="loading" class="text-muted">Loading shipping settings...</div>

            <template v-else>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nearest Shop Country</label>
                  <select v-model="editor.shopCountryId" class="form-select">
                    <option :value="0">Select shop country</option>
                    <option v-for="shopCountry in shopCountries" :key="shopCountry.id" :value="shopCountry.id">
                      {{ shopCountry.countryName }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Shipping Status</label>
                  <div class="status-switch">
                    <div class="form-check form-switch mb-0">
                      <input id="shipping-status" v-model="editor.isActive" class="form-check-input" type="checkbox" />
                      <label for="shipping-status" class="form-check-label">
                        {{ editor.isActive ? "Active" : "Inactive" }}
                      </label>
                    </div>
                    <div class="small text-muted">Inactive rules stay saved but mark those routes as disabled.</div>
                  </div>
                </div>
              </div>

              <div class="tiers-panel mt-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                  <div>
                    <label class="form-label mb-1">Shipping Cost Tiers</label>
                    <div class="form-text mt-0">Each selected country will receive the same tier set from this shop country.</div>
                  </div>
                  <button type="button" class="btn btn-outline-dark btn-sm" @click="addRateTier">
                    Add Tier
                  </button>
                </div>

                <div class="tier-list">
                  <div
                    v-for="(rate, index) in editor.shippingRates"
                    :key="`tier-${index}`"
                    class="tier-card"
                  >
                    <div class="row g-3 align-items-end">
                      <div class="col-lg-3">
                        <label class="form-label">Tier Name</label>
                        <input v-model="rate.tierName" type="text" class="form-control" placeholder="Nearby" />
                      </div>
                      <div class="col-md-3 col-lg-2">
                        <label class="form-label">Min KM</label>
                        <input v-model.number="rate.minDistanceKm" type="number" min="0" step="1" class="form-control" />
                      </div>
                      <div class="col-md-3 col-lg-2">
                        <label class="form-label">Max KM</label>
                        <input v-model="rate.maxDistanceKm" type="number" min="0" step="1" class="form-control" placeholder="Optional" />
                      </div>
                      <div class="col-md-4 col-lg-3">
                        <label class="form-label">Shipping Cost</label>
                        <input v-model.number="rate.shippingCost" type="number" min="0.01" step="0.01" class="form-control" />
                      </div>
                      <div class="col-lg-2">
                        <button
                          type="button"
                          class="btn btn-outline-danger w-100"
                          :disabled="editor.shippingRates.length === 1"
                          @click="removeRateTier(index)"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mapping-toolbar mt-4">
                <div class="d-flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="btn btn-sm"
                    :class="selectedContinentFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'"
                    @click="selectedContinentFilter = 'all'"
                  >
                    All Continents
                  </button>
                  <button
                    v-for="continent in countryCatalog"
                    :key="continent.name"
                    type="button"
                    class="btn btn-sm"
                    :class="selectedContinentFilter === continent.name ? 'btn-dark' : 'btn-outline-dark'"
                    @click="selectedContinentFilter = continent.name"
                  >
                    {{ continent.name }}
                  </button>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-outline-dark btn-sm" @click="selectVisibleCountries">
                    Select Visible
                  </button>
                  <button type="button" class="btn btn-outline-dark btn-sm" @click="clearSelectedCountries">
                    Clear Selection
                  </button>
                </div>
              </div>

              <div v-if="editor.selectedCountries.length" class="selection-chips mt-3">
                <span
                  v-for="country in editor.selectedCountries"
                  :key="`selected-country-${country}`"
                  class="selection-chip"
                >
                  {{ country }}
                </span>
              </div>

              <div class="continent-list mt-4">
                <section
                  v-for="continent in visibleContinents"
                  :key="continent.name"
                  class="continent-card"
                >
                  <div class="continent-card__header">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                      <label class="form-check d-flex align-items-center gap-2 mb-0">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          :checked="isContinentFullySelected(continent)"
                          @change="toggleContinent(continent)"
                        />
                        <span class="fw-semibold">{{ continent.name }}</span>
                      </label>
                      <span class="small text-muted">
                        {{ selectedCountInContinent(continent) }}/{{ continent.countries.length }} selected
                      </span>
                    </div>
                    <button
                      type="button"
                      class="btn btn-outline-dark btn-sm continent-toggle"
                      @click="toggleContinentCollapse(continent.name)"
                    >
                      {{ isContinentCollapsed(continent.name) ? "Expand" : "Collapse" }}
                    </button>
                  </div>

                  <Transition
                    name="continent-expand"
                    @enter="onExpandEnter"
                    @after-enter="onExpandAfterEnter"
                    @leave="onExpandLeave"
                  >
                    <div v-if="!isContinentCollapsed(continent.name)" class="country-grid-wrap">
                      <div class="country-grid">
                        <label
                          v-for="country in continent.countries"
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
                            <span
                              v-if="existingMappingByCountry[country]"
                              class="country-option__meta"
                            >
                              Current: {{ existingMappingByCountry[country].shopCountryName }}
                            </span>
                            <span v-else class="country-option__meta country-option__meta--muted">
                              Not mapped yet
                            </span>
                          </div>
                        </label>
                      </div>
                    </div>
                  </Transition>
                </section>
              </div>

              <div class="d-flex flex-wrap gap-3 mt-4">
                <button type="button" class="btn btn-luxury" :disabled="savingMappings || !shopCountries.length" @click="saveShippingRule">
                  {{ savingMappings ? "Saving..." : editingGroupKey ? "Update Shipping Rule" : "Save Shipping Rule" }}
                </button>
                <button type="button" class="btn btn-outline-luxury" @click="resetEditor">
                  Reset Form
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </section>

    <section class="container mt-4">
      <div class="surface p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <p class="section-kicker mb-2">Saved Rules</p>
            <h2 class="h3 mb-1">Shipping Mapping Overview</h2>
            <p class="text-muted mb-0">Rules are grouped by continent, linked shop country, status, and shipping tiers.</p>
          </div>
        </div>

        <div v-if="!groupedMappings.length" class="empty-state text-center py-5">
          <i class="bi bi-globe2 display-5 text-muted"></i>
          <h3 class="h5 mt-3">No shipping mappings yet</h3>
          <p class="text-muted mb-0">Create your first rule above to connect countries with the nearest shop country.</p>
        </div>

        <div v-else class="rule-grid">
          <article
            v-for="group in groupedMappings"
            :key="group.key"
            class="rule-card"
          >
            <div class="rule-card__header">
              <div>
                <span :class="['badge rounded-pill mb-2', group.isActive ? 'text-bg-dark' : 'text-bg-secondary']">
                  {{ group.isActive ? "Active" : "Inactive" }}
                </span>
                <h3 class="h5 mb-1">{{ group.continentName }}</h3>
                <p class="text-muted mb-0">
                  Linked to <strong>{{ group.shopCountryName }}</strong>
                </p>
              </div>
              <div class="rule-card__actions">
                <button type="button" class="btn btn-outline-dark btn-sm" @click="editGroup(group)">
                  Edit
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" :disabled="savingMappings" @click="removeGroup(group)">
                  Delete
                </button>
              </div>
            </div>

            <div class="small text-muted mb-3">
              {{ group.countries.length }} countries
            </div>

            <div class="rule-card__countries">
              <span
                v-for="country in group.countries"
                :key="`${group.key}-${country}`"
                class="selection-chip"
              >
                {{ country }}
              </span>
            </div>

            <div class="tier-summary mt-3">
              <div
                v-for="rate in group.shippingRates"
                :key="`${group.key}-${rate.tierName}-${rate.minDistanceKm}`"
                class="tier-summary__row"
              >
                <span>{{ rate.tierName }}</span>
                <span>
                  {{ formatDistanceRange(rate) }} • ${{ Number(rate.shippingCost).toLocaleString() }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { api } from "../lib/api";
import { COUNTRY_PRICING_CATALOG } from "../lib/countryPricingCatalog";

const loading = ref(false);
const addingShopCountry = ref(false);
const deletingShopCountryId = ref(null);
const savingMappings = ref(false);
const editingGroupKey = ref("");
const selectedContinentFilter = ref("all");
const shopCountries = ref([]);
const shippingMappings = ref([]);
const countryCatalog = COUNTRY_PRICING_CATALOG;
const collapsedContinents = ref(countryCatalog.map((continent) => continent.name));
const feedback = reactive({
  type: "success",
  message: "",
});
const shopCountryForm = reactive({
  countryName: "",
});

const createRateTier = () => ({
  tierName: "",
  minDistanceKm: 0,
  maxDistanceKm: "",
  shippingCost: 0,
});

const createEditor = () => ({
  shopCountryId: 0,
  isActive: true,
  selectedCountries: [],
  shippingRates: [
    {
      tierName: "Standard",
      minDistanceKm: 0,
      maxDistanceKm: "",
      shippingCost: 0,
    },
  ],
});

const editor = reactive(createEditor());

const countryToContinentMap = Object.fromEntries(
  countryCatalog.flatMap((continent) =>
    continent.countries.map((country) => [country, continent.name]),
  ),
);

const activeMappingsCount = computed(
  () => shippingMappings.value.filter((mapping) => mapping.isActive).length,
);
const usageCountByShopCountryId = computed(() =>
  shippingMappings.value.reduce((counts, mapping) => {
    counts[mapping.shopCountryId] = (counts[mapping.shopCountryId] || 0) + 1;
    return counts;
  }, {}),
);
const existingMappingByCountry = computed(() =>
  Object.fromEntries(
    shippingMappings.value.map((mapping) => [mapping.destinationCountryName, mapping]),
  ),
);
const visibleContinents = computed(() => {
  if (selectedContinentFilter.value === "all") {
    return countryCatalog;
  }

  return countryCatalog.filter((continent) => continent.name === selectedContinentFilter.value);
});
const groupedMappings = computed(() => {
  const groups = shippingMappings.value.reduce((collection, mapping) => {
    const ratesKey = JSON.stringify(
      [...mapping.shippingRates].map((rate) => ({
        tierName: rate.tierName,
        minDistanceKm: Number(rate.minDistanceKm),
        maxDistanceKm: rate.maxDistanceKm === null ? null : Number(rate.maxDistanceKm),
        shippingCost: Number(rate.shippingCost),
      })),
    );
    const key = [
      mapping.shopCountryId,
      mapping.continentName,
      mapping.isActive ? "1" : "0",
      ratesKey,
    ].join(":");

    collection[key] ??= {
      key,
      shopCountryId: mapping.shopCountryId,
      shopCountryName: mapping.shopCountryName,
      continentName: mapping.continentName,
      isActive: mapping.isActive,
      shippingRates: mapping.shippingRates,
      countries: [],
    };
    collection[key].countries.push(mapping.destinationCountryName);
    return collection;
  }, {});

  return Object.values(groups)
    .map((group) => ({
      ...group,
      countries: [...group.countries].sort((left, right) => left.localeCompare(right)),
    }))
    .sort((left, right) => left.continentName.localeCompare(right.continentName));
});

const fetchSettings = async () => {
  loading.value = true;

  try {
    const payload = await api.get("/shipping-settings");
    shopCountries.value = payload.shopCountries || [];
    shippingMappings.value = payload.shippingMappings || [];
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    loading.value = false;
  }
};

const resetEditor = () => {
  Object.assign(editor, createEditor());
  editingGroupKey.value = "";
  selectedContinentFilter.value = "all";
};

const addShopCountry = async () => {
  addingShopCountry.value = true;
  feedback.message = "";

  try {
    const payload = await api.post("/shop-countries", {
      countryName: shopCountryForm.countryName.trim(),
    });
    shopCountries.value = [...shopCountries.value, payload.shopCountry].sort((left, right) =>
      left.countryName.localeCompare(right.countryName),
    );
    shopCountryForm.countryName = "";
    feedback.type = "success";
    feedback.message = payload.message;
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    addingShopCountry.value = false;
  }
};

const removeShopCountry = async (shopCountry) => {
  const confirmed = window.confirm(`Delete shop country "${shopCountry.countryName}"?`);

  if (!confirmed) {
    return;
  }

  deletingShopCountryId.value = shopCountry.id;
  feedback.message = "";

  try {
    await api.delete(`/shop-countries/${shopCountry.id}`);
    shopCountries.value = shopCountries.value.filter((entry) => entry.id !== shopCountry.id);
    feedback.type = "success";
    feedback.message = "Shop country deleted successfully.";
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    deletingShopCountryId.value = null;
  }
};

const addRateTier = () => {
  editor.shippingRates.push(createRateTier());
};

const removeRateTier = (index) => {
  if (editor.shippingRates.length === 1) {
    return;
  }

  editor.shippingRates.splice(index, 1);
};

const isCountrySelected = (country) => editor.selectedCountries.includes(country);

const toggleCountry = (country) => {
  if (isCountrySelected(country)) {
    editor.selectedCountries = editor.selectedCountries.filter((entry) => entry !== country);
    return;
  }

  editor.selectedCountries = [...editor.selectedCountries, country];
};

const isContinentFullySelected = (continent) =>
  continent.countries.every((country) => editor.selectedCountries.includes(country));

const selectedCountInContinent = (continent) =>
  continent.countries.filter((country) => editor.selectedCountries.includes(country)).length;

const toggleContinent = (continent) => {
  if (isContinentFullySelected(continent)) {
    editor.selectedCountries = editor.selectedCountries.filter(
      (country) => !continent.countries.includes(country),
    );
    return;
  }

  editor.selectedCountries = Array.from(
    new Set([...editor.selectedCountries, ...continent.countries]),
  );
};

const isContinentCollapsed = (continentName) =>
  collapsedContinents.value.includes(continentName);

const toggleContinentCollapse = (continentName) => {
  if (isContinentCollapsed(continentName)) {
    collapsedContinents.value = collapsedContinents.value.filter((entry) => entry !== continentName);
    return;
  }

  collapsedContinents.value = [...collapsedContinents.value, continentName];
};

const onExpandEnter = (element) => {
  element.style.height = "0";
  element.style.opacity = "0";
  element.style.transform = "translateY(-10px)";
  void element.offsetHeight;
  element.style.height = `${element.scrollHeight}px`;
  element.style.opacity = "1";
  element.style.transform = "translateY(0)";
};

const onExpandAfterEnter = (element) => {
  element.style.height = "auto";
};

const onExpandLeave = (element) => {
  element.style.height = `${element.scrollHeight}px`;
  element.style.opacity = "1";
  element.style.transform = "translateY(0)";
  void element.offsetHeight;
  element.style.height = "0";
  element.style.opacity = "0";
  element.style.transform = "translateY(-10px)";
};

const clearSelectedCountries = () => {
  editor.selectedCountries = [];
};

const selectVisibleCountries = () => {
  editor.selectedCountries = Array.from(
    new Set([
      ...editor.selectedCountries,
      ...visibleContinents.value.flatMap((continent) => continent.countries),
    ]),
  );
};

const normalizedRates = () =>
  editor.shippingRates.map((rate) => ({
    tierName: String(rate.tierName || "").trim(),
    minDistanceKm: Number(rate.minDistanceKm || 0),
    maxDistanceKm: rate.maxDistanceKm === "" || rate.maxDistanceKm === null
      ? null
      : Number(rate.maxDistanceKm),
    shippingCost: Number(rate.shippingCost || 0),
  }));

const saveShippingRule = async () => {
  feedback.message = "";

  if (!editor.shopCountryId) {
    feedback.type = "error";
    feedback.message = "Please choose a shop country first.";
    return;
  }

  if (!editor.selectedCountries.length) {
    feedback.type = "error";
    feedback.message = "Please select at least one destination country.";
    return;
  }

  const nextMappings = shippingMappings.value.filter(
    (mapping) => !editor.selectedCountries.includes(mapping.destinationCountryName),
  );
  const rates = normalizedRates();

  const draftMappings = [
    ...nextMappings,
    ...editor.selectedCountries.map((country) => ({
      shopCountryId: editor.shopCountryId,
      shopCountryName: shopCountries.value.find((entry) => entry.id === editor.shopCountryId)?.countryName || "",
      continentName: countryToContinentMap[country] || "Other",
      destinationCountryName: country,
      isActive: editor.isActive,
      shippingRates: rates,
    })),
  ];

  savingMappings.value = true;

  try {
    const payload = await api.post("/shipping-settings/sync", {
      shippingMappings: draftMappings,
    });
    shippingMappings.value = payload.shippingMappings || [];
    feedback.type = "success";
    feedback.message = payload.message;
    resetEditor();
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    savingMappings.value = false;
  }
};

const editGroup = (group) => {
  editingGroupKey.value = group.key;
  editor.shopCountryId = group.shopCountryId;
  editor.isActive = group.isActive;
  editor.selectedCountries = [...group.countries];
  editor.shippingRates = group.shippingRates.map((rate) => ({
    tierName: rate.tierName,
    minDistanceKm: rate.minDistanceKm,
    maxDistanceKm: rate.maxDistanceKm ?? "",
    shippingCost: rate.shippingCost,
  }));
  selectedContinentFilter.value = group.continentName;
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const removeGroup = async (group) => {
  const confirmed = window.confirm(`Delete shipping rule for ${group.continentName} and ${group.countries.length} countries?`);

  if (!confirmed) {
    return;
  }

  savingMappings.value = true;
  feedback.message = "";

  try {
    const payload = await api.post("/shipping-settings/sync", {
      shippingMappings: shippingMappings.value.filter(
        (mapping) => !group.countries.includes(mapping.destinationCountryName),
      ),
    });
    shippingMappings.value = payload.shippingMappings || [];
    feedback.type = "success";
    feedback.message = payload.message;

    if (editingGroupKey.value === group.key) {
      resetEditor();
    }
  } catch (error) {
    feedback.type = "error";
    feedback.message = error.message;
  } finally {
    savingMappings.value = false;
  }
};

const formatDistanceRange = (rate) => {
  if (rate.maxDistanceKm === null || rate.maxDistanceKm === "") {
    return `${Number(rate.minDistanceKm).toLocaleString()}+ km`;
  }

  return `${Number(rate.minDistanceKm).toLocaleString()}-${Number(rate.maxDistanceKm).toLocaleString()} km`;
};

onMounted(fetchSettings);
</script>

<style scoped>
.shipping-hero {
  background:
    radial-gradient(circle at top right, rgba(0, 0, 0, 0.12), transparent 32%),
    linear-gradient(145deg, rgba(255, 255, 255, 0.94), rgba(238, 236, 232, 0.88));
}

.shipping-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}

.metric-card {
  padding: 1rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.64);
}

.metric-card strong {
  display: block;
  font-size: 1.7rem;
  margin-top: 0.3rem;
}

.metric-label {
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.shop-list,
.tier-list,
.continent-list,
.rule-grid {
  display: grid;
  gap: 1rem;
}

.shop-card,
.tier-card,
.continent-card,
.rule-card {
  padding: 1rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.72);
}

.status-switch,
.tiers-panel,
.mapping-toolbar {
  padding: 1rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.72);
}

.shop-card,
.mapping-toolbar,
.continent-card__header,
.rule-card__header,
.rule-card__actions,
.tier-summary__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.selection-chips,
.rule-card__countries {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.selection-chip {
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(11, 11, 12, 0.08);
  font-size: 0.85rem;
}

.country-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.country-grid-wrap {
  overflow: hidden;
}

.continent-toggle {
  min-width: 92px;
}

.continent-expand-enter-active,
.continent-expand-leave-active {
  transition:
    height 0.32s ease,
    opacity 0.26s ease,
    transform 0.32s ease;
  will-change: height, opacity, transform;
}

.country-option {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(11, 11, 12, 0.08);
  border-radius: 0.95rem;
  background: rgba(255, 255, 255, 0.78);
  cursor: pointer;
}

.country-option--active {
  border-color: rgba(11, 11, 12, 0.32);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
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

.country-option__meta {
  font-size: 0.82rem;
  color: var(--ink-muted);
}

.country-option__meta--muted {
  opacity: 0.72;
}

.tier-summary {
  display: grid;
  gap: 0.65rem;
}

.tier-summary__row {
  padding-top: 0.75rem;
  border-top: 1px solid rgba(11, 11, 12, 0.08);
  font-size: 0.92rem;
}

@media (max-width: 991px) {
  .shipping-metrics,
  .country-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .shipping-metrics,
  .country-grid {
    grid-template-columns: 1fr;
  }

  .shop-card,
  .mapping-toolbar,
  .continent-card__header,
  .rule-card__header,
  .rule-card__actions,
  .tier-summary__row {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
