<template>
  <div class="notification-bell" ref="bellRef">
    <button
      type="button"
      class="btn btn-sm position-relative notification-btn"
      :class="{ 'notification-btn--active': isOpen }"
      @click="togglePanel"
      aria-label="Notifications"
    >
      <i class="bi bi-bell"></i>
      <span v-if="store.hasUnread" class="notif-badge" :key="store.unreadCount">
        {{ displayCount }}
      </span>
    </button>

    <Transition name="notif-panel">
      <div v-if="isOpen" class="notif-panel surface-elevated">
        <div class="notif-panel-header">
          <h3 class="notif-panel-title">Notifications</h3>
          <div class="notif-panel-actions">
            <button
              v-if="store.unreadCount > 0"
              class="notif-action-btn"
              @click="markAllRead"
              title="Mark all as read"
            >
              <i class="bi bi-check2-all"></i>
            </button>
            <button class="notif-action-btn" @click="togglePanel" title="Close">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <div class="notif-panel-body">
          <div v-if="store.loading" class="notif-loading">
            <div class="spinner-border spinner-border-sm text-dark" role="status"></div>
          </div>

          <div v-else-if="store.notifications.length === 0" class="notif-empty">
            <i class="bi bi-bell-slash notif-empty-icon"></i>
            <p>No notifications yet.</p>
          </div>

          <div v-else class="notif-list">
            <div
              v-for="notif in store.notifications"
              :key="notif.id"
              class="notif-item"
              :class="{ 'notif-item--unread': !notif.isRead }"
            >
              <div class="notif-item-content">
                <div class="notif-item-top">
                  <span class="notif-type-badge" :class="`notif-type--${notif.type}`">
                    {{ typeLabel(notif.type) }}
                  </span>
                  <span class="notif-time">{{ timeAgo(notif.createdAt) }}</span>
                </div>
                <p class="notif-item-title">{{ notif.title }}</p>
                <p class="notif-item-message">{{ notif.message }}</p>
              </div>
              <div class="notif-item-actions">
                <button
                  v-if="!notif.isRead"
                  class="notif-item-btn"
                  @click="markAsRead(notif.id)"
                  title="Mark as read"
                >
                  <i class="bi bi-check"></i>
                </button>
                <button
                  class="notif-item-btn notif-item-btn--danger"
                  @click="deleteNotif(notif.id)"
                  title="Dismiss"
                >
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <a
                v-if="notif.link"
                :href="notif.link"
                class="notif-item-link"
                @click="handleLinkClick($event, notif)"
              >
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
            </div>
          </div>
        </div>

        <div class="notif-panel-footer">
          <router-link to="/profile" class="notif-settings-link" @click="isOpen = false">
            <i class="bi bi-gear"></i> Notification Settings
          </router-link>
        </div>
      </div>
    </Transition>
  </div>

  <Teleport to="body">
    <div v-if="isOpen" class="notif-overlay" @click="isOpen = false"></div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import { useRouter } from "vue-router";
import { useNotificationStore } from "../stores/notifications";

const store = useNotificationStore();
const router = useRouter();
const bellRef = ref(null);
const isOpen = ref(false);

const displayCount = computed(() => {
  const total = store.unreadCount;
  return total > 9 ? "9+" : total;
});

const typeLabel = (type) => {
  const labels = {
    new_collection: "Collection",
    new_article: "News",
    guideline_update: "Guide",
  };
  return labels[type] || type;
};

const timeAgo = (dateStr) => {
  if (!dateStr) return "";
  const now = new Date();
  const date = new Date(dateStr);
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  if (diffMins < 1) return "Just now";
  if (diffMins < 60) return `${diffMins}m ago`;
  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours}h ago`;
  const diffDays = Math.floor(diffHours / 24);
  return `${diffDays}d ago`;
};

const togglePanel = async () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    await store.fetchNotifications();
  }
};

const markAsRead = async (id) => {
  await store.markAsRead(id);
};

const markAllRead = async () => {
  await store.markAllAsRead();
};

const deleteNotif = async (id) => {
  await store.deleteNotification(id);
};

const handleLinkClick = (event, notif) => {
  event.preventDefault();
  if (!notif.isRead) {
    store.markAsRead(notif.id);
  }
  isOpen.value = false;
  router.push(notif.link);
};

const handleClickOutside = (event) => {
  if (bellRef.value && !bellRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener("mousedown", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", handleClickOutside);
});
</script>

<style scoped>
.notification-bell {
  position: relative;
}

.notification-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: var(--primary-black);
  transition: all var(--transition-base);
  position: relative;
}

.notification-btn:hover,
.notification-btn--active {
  background: rgba(77, 16, 24, 0.06);
  border-color: rgba(77, 16, 24, 0.2);
}

.notification-btn i {
  font-size: 1.1rem;
  line-height: 1;
}

.notif-badge {
  position: absolute;
  top: -3px;
  right: -4px;
  min-width: 1.1rem;
  height: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.2rem;
  font-size: 0.55rem;
  font-weight: 700;
  font-family: "Inter", sans-serif;
  line-height: 1;
  color: var(--primary-black);
  background: var(--gold);
  border-radius: 999px;
  box-shadow: 0 2px 8px rgba(254, 181, 17, 0.5), 0 0 0 2px var(--primary-black);
  animation: badgePop 300ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes badgePop {
  0% { transform: scale(0); }
  60% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.notif-overlay {
  position: fixed;
  inset: 0;
  z-index: 1040;
  background: transparent;
}

.notif-panel {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  z-index: 1050;
  width: 380px;
  max-width: calc(100vw - 2rem);
  max-height: 480px;
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.98);
  box-shadow: 0 18px 36px rgba(77, 16, 24, 0.12);
  backdrop-filter: blur(14px);
  animation: dropdownIn 0.2s ease;
}

@keyframes dropdownIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.notif-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  flex-shrink: 0;
}

.notif-panel-title {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--primary-black);
  margin: 0;
}

.notif-panel-actions {
  display: flex;
  gap: 0.25rem;
}

.notif-action-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--ink-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.85rem;
}

.notif-action-btn:hover {
  background: rgba(77, 16, 24, 0.06);
  color: var(--primary-black);
}

.notif-panel-body {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0;
}

.notif-loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: var(--ink-muted);
}

.notif-empty-icon {
  font-size: 2rem;
  opacity: 0.4;
}

.notif-empty p {
  font-size: 0.85rem;
  margin: 0;
}

.notif-list {
  display: flex;
  flex-direction: column;
}

.notif-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  transition: background 0.2s ease;
  position: relative;
}

.notif-item:hover {
  background: rgba(77, 16, 24, 0.03);
}

.notif-item--unread {
  background: rgba(254, 181, 17, 0.06);
}

.notif-item--unread::before {
  content: "";
  position: absolute;
  left: 0.5rem;
  top: 1.15rem;
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: var(--gold);
}

.notif-item-content {
  flex: 1;
  min-width: 0;
}

.notif-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.notif-type-badge {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-weight: 600;
  line-height: 1.4;
}

.notif-type--new_collection {
  background: rgba(77, 16, 24, 0.1);
  color: #4d1018;
}

.notif-type--new_article {
  background: rgba(11, 11, 12, 0.08);
  color: var(--primary-black);
}

.notif-type--guideline_update {
  background: rgba(254, 181, 17, 0.2);
  color: #7a5a00;
}

.notif-time {
  font-size: 0.68rem;
  color: var(--ink-soft);
  white-space: nowrap;
}

.notif-item-title {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--primary-black);
  margin: 0 0 0.15rem;
}

.notif-item-message {
  font-size: 0.75rem;
  color: var(--ink-muted);
  margin: 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notif-item-actions {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex-shrink: 0;
}

.notif-item-btn {
  width: 1.6rem;
  height: 1.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--ink-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.7rem;
}

.notif-item-btn:hover {
  background: rgba(77, 16, 24, 0.06);
  color: var(--primary-black);
}

.notif-item-btn--danger:hover {
  background: rgba(220, 53, 69, 0.1);
  color: var(--error);
}

.notif-item-link {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.notif-panel-footer {
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  padding: 0.65rem 1.25rem;
  flex-shrink: 0;
}

.notif-settings-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: var(--ink-muted);
  text-decoration: none;
  transition: color 0.2s ease;
}

.notif-settings-link:hover {
  color: var(--primary-black);
}

.notif-settings-link i {
  font-size: 0.85rem;
}

.notif-panel-enter-active { transition: all 0.2s ease; }
.notif-panel-leave-active { transition: all 0.15s ease; }
.notif-panel-enter-from,
.notif-panel-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
