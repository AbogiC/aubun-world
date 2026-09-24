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
        
        // Migrate localStorage cart to database for logged-in user
        const cartStore = useCartStore();
        const hadLocalItems = cartStore.items.length > 0;
        
        if (hadLocalItems) {
          try {
            for (const item of cartStore.items) {
              await api.post("/cart/items", {
                product_id: item.id,
                quantity: item.quantity,
                size: item.size,
                color: item.color,
              });
            }
          } catch (migrationError) {
            console.warn('Cart migration failed:', migrationError);
          }
        }
        
        // Always refresh from API after login (this will clear localStorage)
        await cartStore.refreshFromApi();
        
        return user;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
        this.ready = true;
      }
    },

    async loginWithGoogle(credential) {
      this.loading = true;
      this.error = null;

      try {
        const response = await api.post("/auth/google", { credential });
        const { token, user } = response;
        setAuthToken(token);
        this.user = user;

        // Migrate localStorage cart to database for logged-in user
        const cartStore = useCartStore();
        const hadLocalItems = cartStore.items.length > 0;

        if (hadLocalItems) {
          try {
            for (const item of cartStore.items) {
              await api.post("/cart/items", {
                product_id: item.id,
                quantity: item.quantity,
                size: item.size,
                color: item.color,
              });
            }
          } catch (migrationError) {
            console.warn("Cart migration failed:", migrationError);
          }
        }

        await cartStore.refreshFromApi();

        return response;
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
        
        // Migrate localStorage cart to database for logged-in user
        const cartStore = useCartStore();
        if (cartStore.items.length > 0) {
          try {
            for (const item of cartStore.items) {
              await api.post("/cart/items", {
                product_id: item.id,
                quantity: item.quantity,
                size: item.size,
                color: item.color,
              });
            }
            // Refresh cart from API and clear localStorage
            await cartStore.refreshFromApi();
          } catch {
            // If migration fails, still proceed with registration
          }
        } else {
          await cartStore.refreshFromApi();
        }
        
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
