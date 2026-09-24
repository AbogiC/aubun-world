<template>
  <div class="about-customization-page">
    <div class="page-header">
      <h1>About Us Customization</h1>
      <p class="text-muted">Edit the hero, mission, values and team shown on the customer About page</p>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="row g-4">
      <!-- Hero Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header">
            <h2 class="h5 mb-0">Hero Section</h2>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Kicker</label>
                <input type="text" class="form-control" v-model="form.heroKicker" placeholder="Our Story" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" v-model="form.heroTitle" placeholder="Our Story" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Subtitle</label>
                <input type="text" class="form-control" v-model="form.heroSubtitle" placeholder="Crafting elegance since 2010" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mission Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header">
            <h2 class="h5 mb-0">Mission Section</h2>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Kicker</label>
                <input type="text" class="form-control" v-model="form.missionKicker" placeholder="Our Purpose" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" v-model="form.missionTitle" placeholder="Our Mission" />
              </div>
              <div class="col-12">
                <label class="form-label">Lead paragraph</label>
                <textarea class="form-control" v-model="form.missionLead" rows="2" placeholder="To create timeless pieces..."></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Body paragraph 1</label>
                <textarea class="form-control" v-model="form.missionBody1" rows="3" placeholder="At Aubun World, we believe..."></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Body paragraph 2</label>
                <textarea class="form-control" v-model="form.missionBody2" rows="3" placeholder="Our commitment to quality..."></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Mission image URL (optional)</label>
                <input type="text" class="form-control" v-model="form.missionImageUrl" placeholder="https://example.com/mission.jpg" />
                <div class="form-text">Leave empty to show the default icon visual. If set, the image is shown instead.</div>
                <img v-if="form.missionImageUrl" :src="form.missionImageUrl" alt="Mission preview" class="mission-preview mt-2" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Values Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Values</h2>
            <button type="button" class="btn btn-sm btn-luxury" @click="addValue">
              <i class="bi bi-plus-lg me-1"></i> Add Value
            </button>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Section title</label>
                <input type="text" class="form-control" v-model="form.valuesTitle" placeholder="Our Values" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Section subtitle</label>
                <input type="text" class="form-control" v-model="form.valuesSubtitle" placeholder="The principles that guide every creation" />
              </div>
            </div>

            <div v-if="form.values.length === 0" class="text-center py-4 text-muted">
              <p class="mb-0">No values yet. Click "Add Value" to create one. Empty list falls back to defaults on the storefront.</p>
            </div>

            <div v-else>
              <div v-for="(item, index) in form.values" :key="index" class="editable-item-card card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <strong>{{ item.title || `Value ${index + 1}` }}</strong>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" @click="moveValue(index, -1)" :disabled="index === 0">
                      <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="moveValue(index, 1)" :disabled="index === form.values.length - 1">
                      <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" @click="removeValue(index)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Icon (Bootstrap icon class)</label>
                      <input type="text" class="form-control font-monospace" v-model="item.icon" placeholder="bi bi-star-fill" list="about-icon-suggestions" />
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Title</label>
                      <input type="text" class="form-control" v-model="item.title" placeholder="Craftsmanship" />
                    </div>
                    <div class="col-12">
                      <label class="form-label">Description</label>
                      <textarea class="form-control" v-model="item.description" rows="2" placeholder="What this value means..."></textarea>
                    </div>
                    <div class="col-12">
                      <div class="form-check">
                        <input type="checkbox" class="form-check-input" v-model="item.isActive" :id="'value-active-' + index" />
                        <label class="form-check-label" :for="'value-active-' + index">Active (visible on storefront)</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Section -->
      <div class="col-12">
        <div class="card surface">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Team</h2>
            <button type="button" class="btn btn-sm btn-luxury" @click="addMember">
              <i class="bi bi-plus-lg me-1"></i> Add Member
            </button>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Section title</label>
                <input type="text" class="form-control" v-model="form.teamTitle" placeholder="Our Team" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Section subtitle</label>
                <input type="text" class="form-control" v-model="form.teamSubtitle" placeholder="The people behind the brand" />
              </div>
            </div>

            <div v-if="form.team.length === 0" class="text-center py-4 text-muted">
              <p class="mb-0">No team members yet. Click "Add Member" to create one. Empty list falls back to defaults on the storefront.</p>
            </div>

            <div v-else>
              <div v-for="(item, index) in form.team" :key="index" class="editable-item-card card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <strong>{{ item.name || `Member ${index + 1}` }}</strong>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" @click="moveMember(index, -1)" :disabled="index === 0">
                      <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="moveMember(index, 1)" :disabled="index === form.team.length - 1">
                      <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" @click="removeMember(index)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Name</label>
                      <input type="text" class="form-control" v-model="item.name" placeholder="Sophia Laurent" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Role</label>
                      <input type="text" class="form-control" v-model="item.role" placeholder="Founder & Creative Director" />
                    </div>
                    <div class="col-12">
                      <div class="form-check">
                        <input type="checkbox" class="form-check-input" v-model="item.isActive" :id="'team-active-' + index" />
                        <label class="form-check-label" :for="'team-active-' + index">Active (visible on storefront)</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <datalist id="about-icon-suggestions">
      <option value="bi bi-scissors"></option>
      <option value="bi bi-globe2"></option>
      <option value="bi bi-stars"></option>
      <option value="bi bi-clock-history"></option>
      <option value="bi bi-gem"></option>
      <option value="bi bi-diamond-fill"></option>
      <option value="bi bi-star-fill"></option>
      <option value="bi bi-heart-fill"></option>
      <option value="bi bi-award-fill"></option>
    </datalist>

    <!-- Save Button -->
    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="button" class="btn btn-outline-secondary" @click="resetForm" :disabled="loading || saving">
        Reset
      </button>
      <button type="button" class="btn btn-luxury" @click="saveSettings" :disabled="loading || saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
        Save Settings
      </button>
    </div>

    <StatusModal
      v-model="statusModal.show"
      :type="statusModal.type"
      :title="statusModal.title"
      :message="statusModal.message"
      :confirm-text="statusModal.confirmText"
      :cancel-text="statusModal.cancelText"
      :show-cancel="statusModal.showCancel"
      :loading="statusModal.loading"
      @confirm="onStatusConfirm"
      @cancel="onStatusCancel"
      @close="onStatusClose"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { api } from "../lib/api";
import StatusModal from "../components/StatusModal.vue";

const loading = ref(false);
const saving = ref(false);

const form = ref({
  heroKicker: "",
  heroTitle: "",
  heroSubtitle: "",
  missionKicker: "",
  missionTitle: "",
  missionLead: "",
  missionBody1: "",
  missionBody2: "",
  missionImageUrl: "",
  valuesTitle: "",
  valuesSubtitle: "",
  teamTitle: "",
  teamSubtitle: "",
  values: [],
  team: [],
});

const statusModal = ref({
  show: false,
  type: "success",
  title: "",
  message: "",
  confirmText: "Got it",
  cancelText: "Cancel",
  showCancel: false,
  loading: false,
  pendingAction: null,
});

const showStatus = (type, title, message, options = {}) => {
  statusModal.value = {
    show: true,
    type,
    title,
    message,
    confirmText: options.confirmText || (type === "warning" ? "Confirm" : "Got it"),
    cancelText: options.cancelText || "Cancel",
    showCancel: options.showCancel || false,
    loading: false,
    pendingAction: options.onConfirm || null,
  };
};

const showSuccess = (title, message, options = {}) => showStatus("success", title, message, options);
const showError = (title, message, options = {}) => showStatus("error", title, message, options);
const askConfirm = (title, message, options = {}) =>
  showStatus("warning", title, message, { showCancel: true, confirmText: options.confirmText || "Confirm", ...options });

const onStatusConfirm = async () => {
  const action = statusModal.value.pendingAction;
  statusModal.value.pendingAction = null;
  statusModal.value.show = false;
  statusModal.value.loading = false;
  if (!action) return;
  await action();
};

const onStatusCancel = () => {
  statusModal.value.pendingAction = null;
  statusModal.value.show = false;
};

const onStatusClose = () => {
  if (statusModal.value.loading) return;
  statusModal.value.pendingAction = null;
};

const fetchInitialData = async () => {
  loading.value = true;
  try {
    const res = await api.get("/about-view");
    const s = res.settings;
    if (s) {
      form.value.heroKicker = s.heroKicker || "";
      form.value.heroTitle = s.heroTitle || "";
      form.value.heroSubtitle = s.heroSubtitle || "";
      form.value.missionKicker = s.missionKicker || "";
      form.value.missionTitle = s.missionTitle || "";
      form.value.missionLead = s.missionLead || "";
      form.value.missionBody1 = s.missionBody1 || "";
      form.value.missionBody2 = s.missionBody2 || "";
      form.value.missionImageUrl = s.missionImageUrl || "";
      form.value.valuesTitle = s.valuesTitle || "";
      form.value.valuesSubtitle = s.valuesSubtitle || "";
      form.value.teamTitle = s.teamTitle || "";
      form.value.teamSubtitle = s.teamSubtitle || "";
    }
    const values = res.values || s?.values || [];
    const team = res.team || s?.team || [];
    form.value.values = values.map((v, idx) => ({
      icon: v.icon || "bi bi-star-fill",
      title: v.title || "",
      description: v.description || "",
      sortOrder: v.sortOrder ?? idx,
      isActive: v.isActive ?? true,
    }));
    form.value.team = team.map((m, idx) => ({
      name: m.name || "",
      role: m.role || "",
      sortOrder: m.sortOrder ?? idx,
      isActive: m.isActive ?? true,
    }));
  } catch (error) {
    console.error("Failed to load about settings:", error);
  } finally {
    loading.value = false;
  }
};

const addValue = () => {
  form.value.values.push({
    icon: "bi bi-star-fill",
    title: "",
    description: "",
    sortOrder: form.value.values.length,
    isActive: true,
  });
};

const removeValue = (index) => {
  form.value.values.splice(index, 1);
  form.value.values.forEach((item, idx) => { item.sortOrder = idx; });
};

const moveValue = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= form.value.values.length) return;
  [form.value.values[index], form.value.values[newIndex]] = [form.value.values[newIndex], form.value.values[index]];
  form.value.values.forEach((item, idx) => { item.sortOrder = idx; });
};

const addMember = () => {
  form.value.team.push({
    name: "",
    role: "",
    sortOrder: form.value.team.length,
    isActive: true,
  });
};

const removeMember = (index) => {
  form.value.team.splice(index, 1);
  form.value.team.forEach((item, idx) => { item.sortOrder = idx; });
};

const moveMember = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= form.value.team.length) return;
  [form.value.team[index], form.value.team[newIndex]] = [form.value.team[newIndex], form.value.team[index]];
  form.value.team.forEach((item, idx) => { item.sortOrder = idx; });
};

const doSaveSettings = async () => {
  saving.value = true;
  try {
    const payload = {
      heroKicker: form.value.heroKicker,
      heroTitle: form.value.heroTitle,
      heroSubtitle: form.value.heroSubtitle,
      missionKicker: form.value.missionKicker,
      missionTitle: form.value.missionTitle,
      missionLead: form.value.missionLead,
      missionBody1: form.value.missionBody1,
      missionBody2: form.value.missionBody2,
      missionImageUrl: form.value.missionImageUrl,
      valuesTitle: form.value.valuesTitle,
      valuesSubtitle: form.value.valuesSubtitle,
      teamTitle: form.value.teamTitle,
      teamSubtitle: form.value.teamSubtitle,
      values: form.value.values.map((item) => ({
        icon: item.icon,
        title: item.title,
        description: item.description,
        sortOrder: item.sortOrder,
        isActive: item.isActive,
      })),
      team: form.value.team.map((item) => ({
        name: item.name,
        role: item.role,
        sortOrder: item.sortOrder,
        isActive: item.isActive,
      })),
    };

    await api.patch("/about-view", payload);
    await fetchInitialData();
    showSuccess("Settings saved", "Your About page changes have been published to the live storefront.");
  } catch (error) {
    console.error("Failed to save about settings:", error);
    showError("Save failed", error.message || "Failed to save settings. Please try again.");
  } finally {
    saving.value = false;
  }
};

const saveSettings = () => {
  askConfirm(
    "Save settings?",
    "This will publish your About page changes to the live storefront.",
    { confirmText: "Save settings", onConfirm: doSaveSettings }
  );
};

const resetForm = () => {
  askConfirm(
    "Discard changes?",
    "This will reset the form to the last saved settings. Unsaved changes will be lost.",
    { confirmText: "Reset form", onConfirm: async () => { await fetchInitialData(); } }
  );
};

onMounted(() => {
  fetchInitialData();
});
</script>

<style scoped>
.about-customization-page {
  padding: 1.5rem;
}

.page-header {
  margin-bottom: 1.5rem;
}

.page-header h1 {
  font-size: 1.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.editable-item-card {
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: var(--radius-lg);
}

.editable-item-card .card-header {
  background: rgba(255, 248, 228, 0.5);
  border-bottom: 1px solid rgba(77, 16, 24, 0.1);
  padding: 0.75rem 1rem;
}

.mission-preview {
  max-width: 320px;
  max-height: 200px;
  object-fit: cover;
  border-radius: 0.75rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  display: block;
}

@media (max-width: 767.98px) {
  .about-customization-page {
    padding: 1rem;
  }
}
</style>
