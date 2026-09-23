<template>
  <div class="dashboard-page py-4">
    <section class="container mb-4">
      <div class="dashboard-hero surface p-4 p-lg-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <p class="section-kicker mb-2">User Access</p>
            <h1 class="display-5 mb-3">User Management</h1>
            <p class="text-muted mb-0">
              Monitor staff accounts, review customer participation, and keep access aligned with your business roles.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
              <button class="btn btn-outline-dark btn-sm" @click="refreshUsers" :disabled="loading">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
          </div>
        </div>

        <div class="stats-section mt-4">
          <div class="stats-grid">
            <div class="metric-card primary">
              <div class="metric-icon">
                <i class="bi bi-people"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Total Users</span>
                <strong class="metric-value">{{ users.length }}</strong>
              </div>
            </div>
            <div class="metric-card featured">
              <div class="metric-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Admins</span>
                <strong class="metric-value">{{ adminCount }}</strong>
              </div>
            </div>
            <div class="metric-card categories">
              <div class="metric-icon">
                <i class="bi bi-person-check"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Managers</span>
                <strong class="metric-value">{{ managerCount }}</strong>
              </div>
            </div>
            <div class="metric-card stock-total">
              <div class="metric-icon">
                <i class="bi bi-toggle-on"></i>
              </div>
              <div class="metric-content">
                <span class="metric-label">Active</span>
                <strong class="metric-value">{{ activeCount }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container">
      <div class="surface p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
          <div>
            <p class="section-kicker mb-2">Directory</p>
            <h2 class="h3 mb-0">Registered Users</h2>
          </div>

          <div class="position-relative" style="max-width: 380px; width: 100%;">
            <i class="bi bi-search search-icon"></i>
            <input
              v-model="search"
              type="search"
              class="form-control search-input"
              placeholder="Search by name or email"
            />
          </div>
        </div>

        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading users...</span>
          </div>
          <p class="text-muted mt-2 mb-0">Loading users...</p>
        </div>

        <div v-else-if="filteredUsers.length === 0" class="empty-state">
          <div class="empty-icon"><i class="bi bi-people"></i></div>
          <h3>No users found</h3>
          <p>No users match your current search.</p>
        </div>

        <div v-else class="dashboard-table-wrap">
          <div class="table-responsive">
            <table class="table align-middle dashboard-table">
              <thead>
                <tr>
                  <th style="width: 24%;">User</th>
                  <th style="width: 24%;">Email</th>
                  <th style="width: 18%;">Role</th>
                  <th style="width: 16%;">Status</th>
                  <th style="width: 18%;">Joined</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id">
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div class="user-avatar">{{ initials(user.name) }}</div>
                      <div>
                        <div class="fw-semibold">{{ user.name || "Unnamed User" }}</div>
                        <div class="small text-muted">ID: #{{ user.id }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="email-text">{{ user.email }}</span>
                  </td>
                  <td>
                    <select
                      v-model="user.role"
                      class="form-select form-select-sm role-select"
                      @change="updateUserRole(user)"
                    >
                      <option value="customer">Customer</option>
                      <option value="manager">Manager</option>
                      <option value="admin">Admin</option>
                    </select>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span :class="['badge status-badge', getAccountStatusClass(user)]">
                        <i class="bi" :class="user.email_verified ? 'bi-check-circle-fill me-1' : 'bi-clock-history me-1'" aria-hidden="true"></i>
                        {{ user.email_verified ? 'Verified' : 'Pending' }}
                      </span>
                      <button
                        type="button"
                        class="btn btn-sm action-toggle"
                        :class="user.isActive === false ? 'btn-outline-dark' : 'btn-dark'"
                        @click="toggleUserStatus(user)"
                      >
                        {{ user.isActive === false ? 'Inactive' : 'Active' }}
                      </button>
                    </div>
                  </td>
                  <td>
                    <div class="small text-muted">{{ formatDate(user.created_at) }}</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { api } from "../lib/api";

const users = ref([]);
const loading = ref(false);
const search = ref("");

const filteredUsers = computed(() => {
  const query = search.value.trim().toLowerCase();
  if (!query) return users.value;

  return users.value.filter((user) => {
    const name = (user.name || "").toLowerCase();
    const email = (user.email || "").toLowerCase();
    return name.includes(query) || email.includes(query);
  });
});

const adminCount = computed(() => users.value.filter((user) => user.role === "admin").length);
const managerCount = computed(() => users.value.filter((user) => user.role === "manager").length);
const activeCount = computed(() => users.value.filter((user) => user.isActive !== false).length);

const initials = (name = "") => {
  const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
  if (!parts.length) return "U";
  return parts.map((part) => part[0]).join("").toUpperCase();
};

const formatRole = (role) => {
  if (!role) return "Customer";
  return role.charAt(0).toUpperCase() + role.slice(1);
};

const roleClass = (role) => {
  switch (role) {
    case "admin":
      return "admin-role";
    case "manager":
      return "manager-role";
    default:
      return "customer-role";
  }
};

const getAccountStatusClass = (user) => {
  const isActive = user.isActive !== false;
  return isActive ? 'verified' : 'pending';
};

const updateUserRole = async (user) => {
  try {
    const { user: updatedUser } = await api.patch(`/users/${user.id}`, { role: user.role });
    const index = users.value.findIndex((item) => item.id === user.id);
    if (index >= 0) {
      users.value[index] = { ...users.value[index], ...updatedUser };
    }
  } catch (error) {
    console.error("Failed to update user role:", error);
    await refreshUsers();
  }
};

const toggleUserStatus = async (user) => {
  const nextStatus = user.isActive === false;

  try {
    const { user: updatedUser } = await api.patch(`/users/${user.id}`, { isActive: nextStatus });
    const index = users.value.findIndex((item) => item.id === user.id);
    if (index >= 0) {
      users.value[index] = { ...users.value[index], ...updatedUser };
    }
  } catch (error) {
    console.error("Failed to toggle user status:", error);
    await refreshUsers();
  }
};

const formatDate = (value) => {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const refreshUsers = async () => {
  loading.value = true;

  try {
    const { users: result } = await api.get("/users");
    users.value = result || [];
  } catch (error) {
    console.error("Failed to load users:", error);
    users.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  refreshUsers();
});
</script>

<style scoped>
.dashboard-page {
  min-height: 100%;
}

.dashboard-hero {
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.08), rgba(254, 181, 17, 0.16));
}

.stats-section {
  margin-top: 1.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  padding: 1rem 1.1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1.125rem;
  background: rgba(255, 255, 255, 0.8);
  box-shadow: var(--shadow-sm);
}

.metric-card.primary { background: linear-gradient(135deg, rgba(77, 16, 24, 0.06), rgba(255, 255, 255, 0.9)); }
.metric-card.featured { background: linear-gradient(135deg, rgba(254, 181, 17, 0.12), rgba(255, 255, 255, 0.9)); }
.metric-card.categories { background: linear-gradient(135deg, rgba(77, 16, 24, 0.05), rgba(255, 255, 255, 0.9)); }
.metric-card.stock-total { background: linear-gradient(135deg, rgba(43, 138, 94, 0.08), rgba(255, 255, 255, 0.9)); }

.metric-icon {
  width: 2.8rem;
  height: 2.8rem;
  border-radius: 0.9rem;
  display: grid;
  place-items: center;
  background: rgba(77, 16, 24, 0.08);
  color: var(--primary-black);
  font-size: 1.2rem;
  flex-shrink: 0;
}

.metric-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.metric-label {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-soft);
  margin-bottom: 0.15rem;
}

.metric-value {
  font-size: clamp(1.2rem, 2vw, 1.8rem);
  color: var(--primary-black);
  line-height: 1.2;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 3rem 1rem;
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.52);
}

.empty-icon {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: rgba(77, 16, 24, 0.06);
  font-size: 1.4rem;
  margin-bottom: 1rem;
}

.user-avatar {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.1), rgba(254, 181, 17, 0.2));
  color: var(--primary-black);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.email-text {
  word-break: break-word;
}

.role-badge,
.status-badge {
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  padding: 0.5rem 0.7rem;
  border-radius: 999px;
  font-weight: 600;
}

.role-select {
  min-width: 140px;
  border-radius: 0.75rem;
  border: 1px solid rgba(77, 16, 24, 0.14);
  background: rgba(255, 255, 255, 0.9);
  color: var(--primary-black);
}

.action-toggle {
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  padding: 0.45rem 0.8rem;
}

.admin-role {
  background: rgba(77, 16, 24, 0.08);
  color: var(--primary-black);
}

.manager-role {
  background: rgba(254, 181, 17, 0.16);
  color: #8a5f00;
}

.customer-role {
  background: rgba(43, 138, 94, 0.1);
  color: var(--success);
}

.status-badge.verified {
  background: rgba(43, 138, 94, 0.12);
  color: var(--success);
}

.status-badge.pending {
  background: rgba(194, 37, 59, 0.08);
  color: var(--error);
}

.search-input {
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 0.8rem;
  padding-left: 2.8rem;
  min-height: 48px;
}

.search-icon {
  position: absolute;
  left: 0.95rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-soft);
  font-size: 0.95rem;
}

@media (max-width: 991.98px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 575.98px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-table-wrap {
    overflow-x: auto;
  }

  .dashboard-table {
    min-width: 620px;
  }
}
</style>
