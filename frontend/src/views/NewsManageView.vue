<template>
  <div class="news-manage py-4">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 class="fw-bold mb-1">News Articles</h4>
          <p class="text-muted mb-0 small">Create, edit, and manage news articles for the public page</p>
        </div>
      </div>

      <div v-if="feedback.message" class="alert" :class="'alert-' + feedback.type" role="alert">
        {{ feedback.message }}
        <button type="button" class="btn-close float-end" @click="feedback.message = ''"></button>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button
            v-for="cat in categories"
            :key="cat"
            class="filter-chip"
            :class="{ active: activeCategory === cat }"
            @click="activeCategory = cat"
          >
            {{ cat }}
          </button>
        </div>
        <button class="btn btn-dark btn-sm" @click="startCreate">
          <i class="bi bi-plus-lg me-1"></i> Add Article
        </button>
      </div>

      <div class="surface p-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="filtered.length === 0" class="text-center py-5">
          <i class="bi bi-newspaper text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No articles found.</p>
          <button class="btn btn-dark btn-sm mt-2" @click="startCreate">Create the first article</button>
        </div>

        <div v-else class="table-responsive">
          <table class="table news-table mb-0">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Title</th>
                <th class="d-none d-md-table-cell">Category</th>
                <th class="d-none d-lg-table-cell">Region</th>
                <th class="d-none d-lg-table-cell">Author</th>
                <th class="d-none d-md-table-cell">Date</th>
                <th style="width: 80px;">Published</th>
                <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filtered" :key="item.id">
                <td class="text-muted small">{{ index + 1 }}</td>
                <td class="fw-medium">{{ item.title }}</td>
                <td class="d-none d-md-table-cell">
                  <span class="category-badge">{{ item.category }}</span>
                </td>
                <td class="d-none d-lg-table-cell text-muted small">{{ item.region || "Common" }}</td>
                <td class="d-none d-lg-table-cell text-muted small">{{ item.author }}</td>
                <td class="d-none d-md-table-cell text-muted small">{{ formatDate(item.publishedAt || item.createdAt) }}</td>
                <td>
                  <span :class="['badge', item.isPublished ? 'bg-success' : 'bg-secondary']">
                    {{ item.isPublished ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-dark" @click="startEdit(item)" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="removeItem(item)" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-dialog-custom">
        <div class="surface p-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold mb-0">{{ editingId ? 'Edit' : 'Add' }} Article</h5>
            <button class="btn-close" @click="closeModal"></button>
          </div>

          <form @submit.prevent="saveItem">
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label small fw-bold">Title</label>
                <input v-model="form.title" class="form-control" placeholder="Article title" required />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Category</label>
                <select v-model="form.category" class="form-select">
                  <option v-for="c in categoryOptions" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Excerpt</label>
              <textarea v-model="form.excerpt" class="form-control" rows="3" placeholder="Brief summary of the article" required></textarea>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Author</label>
                <input v-model="form.author" class="form-control" placeholder="e.g. Sophia Laurent" required />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Read Time</label>
                <input v-model="form.readTime" class="form-control" placeholder="e.g. 5 min read" />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Icon Class</label>
                <input v-model="form.icon" class="form-control" placeholder="bi bi-newspaper" />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Gradient</label>
              <div class="d-flex gap-2 flex-wrap">
                <button
                  v-for="g in gradientOptions"
                  :key="g.value"
                  type="button"
                  class="gradient-swatch"
                  :class="{ active: form.gradient === g.value }"
                  :style="{ background: g.value }"
                  :title="g.label"
                  @click="form.gradient = g.value"
                ></button>
                <input v-model="form.gradient" class="form-control form-control-sm font-monospace flex-grow-1" placeholder="Custom gradient..." />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Region</label>
              <select v-model="form.region" class="form-select">
                <option value="">Common (all regions)</option>
                <option v-for="c in regionOptions" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Content (optional, HTML)</label>
                <textarea v-model="form.content" class="form-control font-monospace" rows="4" placeholder="Full article content (optional)"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">&nbsp;</label>
                <div class="form-check form-switch mt-2">
                  <input v-model="form.isPublished" type="checkbox" class="form-check-input" id="isPublished" />
                  <label class="form-check-label" for="isPublished">{{ form.isPublished ? 'Published' : 'Draft' }}</label>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 justify-content-end border-top pt-3">
              <button type="button" class="btn btn-outline-dark btn-sm" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-dark btn-sm" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingId ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { api } from "../lib/api";

const categories = ["All", "Collection", "Behind the Scenes", "Sustainability", "Press", "Events"];
const categoryOptions = categories.filter((c) => c !== "All");

const regionOptions = [
  "United States", "Canada", "United Kingdom", "Germany", "France",
  "Italy", "Spain", "Japan", "China", "Australia",
  "Brazil", "India", "South Korea", "Indonesia", "Malaysia",
  "Philippines", "Singapore", "Thailand", "Vietnam", "United Arab Emirates",
];

const gradientOptions = [
  { value: "linear-gradient(135deg, #4d1018, #c48d0c)", label: "Burgundy Gold" },
  { value: "linear-gradient(135deg, #6c1823, #fef8e4)", label: "Maroon Cream" },
  { value: "linear-gradient(135deg, #2b8a5e, #4d1018)", label: "Green Maroon" },
  { value: "linear-gradient(135deg, #8c5a14, #4d1018)", label: "Bronze" },
  { value: "linear-gradient(135deg, #c48d0c, #6c1823)", label: "Gold Burgundy" },
  { value: "linear-gradient(135deg, #1a1a2e, #4d1018)", label: "Midnight" },
  { value: "linear-gradient(135deg, #feb511, #4d1018)", label: "Gold Maroon" },
  { value: "linear-gradient(135deg, #4d1018, #2b1a1e)", label: "Deep Auburn" },
];

const activeCategory = ref("All");
const articles = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);

const feedback = reactive({ message: "", type: "success" });

const form = reactive({
  title: "",
  category: "Collection",
  excerpt: "",
  content: "",
  author: "",
  readTime: "5 min read",
  gradient: "linear-gradient(135deg, #4d1018, #c48d0c)",
  icon: "bi bi-newspaper",
  region: "",
  isPublished: true,
});

function resetForm() {
  form.title = "";
  form.category = "Collection";
  form.excerpt = "";
  form.content = "";
  form.author = "";
  form.readTime = "5 min read";
  form.gradient = "linear-gradient(135deg, #4d1018, #c48d0c)";
  form.icon = "bi bi-newspaper";
  form.region = "";
  form.isPublished = true;
  editingId.value = null;
}

const filtered = computed(() => {
  if (activeCategory.value === "All") return articles.value;
  return articles.value.filter((a) => a.category === activeCategory.value);
});

function formatDate(dateStr) {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
}

function showFeedback(message, type = "success") {
  feedback.message = message;
  feedback.type = type;
}

async function fetchArticles() {
  loading.value = true;
  try {
    const data = await api.get("/news");
    articles.value = data.articles || [];
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    loading.value = false;
  }
}

function startCreate() {
  resetForm();
  showModal.value = true;
}

function startEdit(item) {
  editingId.value = item.id;
  form.title = item.title;
  form.category = item.category;
  form.excerpt = item.excerpt;
  form.content = item.content || "";
  form.author = item.author;
  form.readTime = item.readTime;
  form.gradient = item.gradient;
  form.icon = item.icon;
  form.region = item.region || "";
  form.isPublished = item.isPublished;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

async function saveItem() {
  if (!form.title.trim() || !form.excerpt.trim() || !form.author.trim()) {
    showFeedback("Title, excerpt, and author are required.", "danger");
    return;
  }

  saving.value = true;

  try {
    const payload = {
      title: form.title.trim(),
      category: form.category,
      excerpt: form.excerpt.trim(),
      content: form.content || null,
      author: form.author.trim(),
      readTime: form.readTime || "5 min read",
      gradient: form.gradient,
      icon: form.icon || "bi bi-newspaper",
      isPublished: form.isPublished,
    };

    if (editingId.value) {
      const data = await api.patch(`/news/${editingId.value}`, payload);
      showFeedback(data.message || "Updated successfully.");
    } else {
      const data = await api.post("/news", payload);
      showFeedback(data.message || "Created successfully.");
    }

    closeModal();
    await fetchArticles();
  } catch (err) {
    showFeedback(err.message, "danger");
  } finally {
    saving.value = false;
  }
}

async function removeItem(item) {
  if (!window.confirm(`Delete "${item.title}"?`)) return;

  try {
    const data = await api.delete(`/news/${item.id}`);
    showFeedback(data.message || "Deleted successfully.");
    await fetchArticles();
  } catch (err) {
    showFeedback(err.message, "danger");
  }
}

watch(activeCategory, () => {});

fetchArticles();
</script>

<style scoped>
.filter-chip {
  padding: 0.4rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(77, 16, 24, 0.14);
  background: rgba(255, 248, 228, 0.6);
  color: var(--ink-soft);
  font-size: 0.78rem;
  cursor: pointer;
  transition:
    background var(--transition-base),
    color var(--transition-base),
    border-color var(--transition-base);
}

.filter-chip:hover {
  background: rgba(255, 248, 228, 0.9);
  border-color: rgba(77, 16, 24, 0.3);
}

.filter-chip.active {
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

.news-table thead th {
  background: rgba(77, 16, 24, 0.04);
  border-bottom: 2px solid rgba(77, 16, 24, 0.1);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 600;
  padding: 0.75rem 0.85rem;
}

.news-table tbody td {
  padding: 0.7rem 0.85rem;
  font-size: 0.88rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.05);
  vertical-align: middle;
}

.news-table tbody tr:hover {
  background: rgba(255, 241, 184, 0.3);
}

.category-badge {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.15);
  color: var(--ink-muted);
  font-size: 0.75rem;
}

.gradient-swatch {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-xs);
  border: 2px solid rgba(77, 16, 24, 0.12);
  cursor: pointer;
  transition: border-color var(--transition-base), transform var(--transition-base);
  padding: 0;
}

.gradient-swatch:hover {
  transform: scale(1.12);
}

.gradient-swatch.active {
  border-color: var(--primary-black);
  outline: 2px solid var(--gold);
  outline-offset: 1px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(20, 10, 12, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 8rem 1rem 3rem;
  z-index: 1050;
  overflow-y: auto;
}

.modal-dialog-custom {
  width: 100%;
  max-width: 680px;
  animation: modalIn 0.2s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(-20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
