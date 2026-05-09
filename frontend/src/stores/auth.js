import { defineStore } from "pinia";
import { api, getAuthToken, setAuthToken } from "../lib/api";
import { useCartStore } from "./cart";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    loading: false,
    ready: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user && getAuthToken()),
  },

  actions: {
    async initialize() {
      if (this.ready) return;

      const token = getAuthToken();
      if (!token) {
        this.ready = true;
        return;
      }

      this.loading = true;

      try {
        const { user } = await api.get("/auth/me");
        this.user = user;
      } catch (error) {
        this.user = null;

        if (error?.status === 401) {
          setAuthToken(null);
        }
      } finally {
        this.loading = false;
        this.ready = true;
      }
    },

    async refreshUser() {
      const token = getAuthToken();
      if (!token) {
        this.user = null;
        this.ready = true;
        return;
      }

      this.loading = true;

      try {
        const { user } = await api.get("/auth/me");
        this.user = user;
      } catch (error) {
        this.user = null;

        if (error?.status === 401) {
          setAuthToken(null);
        }
      } finally {
        this.loading = false;
      }
    },

    async login(credentials) {
      this.loading = true;
      this.error = null;

      try {
        const { token, user } = await api.post("/auth/login", credentials);
        setAuthToken(token);
        this.user = user;
        await useCartStore().refreshFromApi();
        return user;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
        this.ready = true;
      }
    },

    async register(payload) {
      this.loading = true;
      this.error = null;

      try {
        const response = await api.post("/auth/register", payload);
        const { token, user } = response;
        setAuthToken(token);
        this.user = user;
        await useCartStore().refreshFromApi();
        return response;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
        this.ready = true;
      }
    },

    logout() {
      setAuthToken(null);
      this.user = null;
      this.error = null;
      useCartStore().clearCart();
    },

    async updateProfile(payload) {
      this.loading = true;
      this.error = null;

      try {
        const { user } = await api.patch("/auth/profile", payload);
        this.user = user;
        return user;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async changePassword(payload) {
      this.loading = true;
      this.error = null;

      try {
        await api.post("/auth/change-password", payload);
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateShippingAddress(payload) {
      this.loading = true;
      this.error = null;

      try {
        const { shippingAddress } = await api.patch("/auth/shipping-address", payload);
        this.user.shippingAddress = shippingAddress;
        return shippingAddress;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async resendVerificationEmail() {
      this.loading = true;
      this.error = null;

      try {
        await api.post("/auth/resend-verification");
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
