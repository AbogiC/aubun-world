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
        const { token, user } = await api.post("/auth/register", payload);
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

    logout() {
      setAuthToken(null);
      this.user = null;
      this.error = null;
      useCartStore().clearCart();
    },
  },
});
