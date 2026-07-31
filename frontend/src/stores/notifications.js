import { defineStore } from "pinia";
import { api } from "../lib/api";

export const useNotificationStore = defineStore("notifications", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    subscriptions: null,
    loading: false,
    initialized: false,
  }),

  getters: {
    hasUnread: (state) => state.unreadCount > 0,
    recentNotifications: (state) => state.notifications.slice(0, 5),
  },

  actions: {
    async initialize() {
      if (this.initialized) return;
      await this.fetchUnreadCount();
      this.initialized = true;
    },

    async fetchUnreadCount() {
      try {
        const { unreadCount } = await api.get("/notifications/unread-count");
        this.unreadCount = unreadCount;
      } catch {
        // silently fail
      }
    },

    async fetchNotifications(limit = 50, offset = 0) {
      this.loading = true;
      try {
        const data = await api.get(`/notifications?limit=${limit}&offset=${offset}`);
        this.notifications = data.notifications;
        this.unreadCount = data.unreadCount;
      } catch {
        // silently fail
      } finally {
        this.loading = false;
      }
    },

    async markAsRead(id) {
      try {
        await api.patch(`/notifications/${id}/read`);
        const notif = this.notifications.find((n) => n.id === id);
        if (notif) {
          notif.isRead = true;
          this.unreadCount = Math.max(0, this.unreadCount - 1);
        }
      } catch {
        // silently fail
      }
    },

    async markAllAsRead() {
      try {
        await api.post("/notifications/mark-all-read");
        this.notifications.forEach((n) => (n.isRead = true));
        this.unreadCount = 0;
      } catch {
        // silently fail
      }
    },

    async deleteNotification(id) {
      try {
        await api.delete(`/notifications/${id}`);
        this.notifications = this.notifications.filter((n) => n.id !== id);
      } catch {
        // silently fail
      }
    },

    async fetchSubscriptions() {
      try {
        const { subscriptions } = await api.get("/notifications/subscriptions");
        this.subscriptions = subscriptions;
        return subscriptions;
      } catch {
        return null;
      }
    },

    async updateSubscriptions(subscriptions) {
      try {
        const data = await api.put("/notifications/subscriptions", { subscriptions });
        this.subscriptions = data.subscriptions;
        return data.subscriptions;
      } catch (error) {
        throw error;
      }
    },

    startPolling(intervalMs = 30000) {
      this._pollingInterval = setInterval(() => {
        this.fetchUnreadCount();
      }, intervalMs);
    },

    stopPolling() {
      if (this._pollingInterval) {
        clearInterval(this._pollingInterval);
        this._pollingInterval = null;
      }
    },
  },
});
