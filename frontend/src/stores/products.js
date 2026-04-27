import { defineStore } from "pinia";
import { api } from "../lib/api";

export const useProductsStore = defineStore("products", {
  state: () => ({
    products: [],
    categories: ["All"],
    loading: false,
    loaded: false,
    error: null,
  }),

  getters: {
    featuredProducts: (state) => state.products.filter((p) => p.featured),
    productsByCategory: (state) => (category) => {
      if (category === "All") return state.products;
      return state.products.filter((p) => p.category === category);
    },
  },

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true;
      this.error = null;

      try {
        const search = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
          if (value !== null && value !== undefined && value !== "") {
            search.set(key, value);
          }
        });

        const query = search.toString() ? `?${search.toString()}` : "";
        const [{ products }, { categories }] = await Promise.all([
          api.get(`/products${query}`),
          api.get("/categories"),
        ]);

        this.products = products;
        this.categories = categories;
        this.loaded = true;
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
  },
});
