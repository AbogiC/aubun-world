import { defineStore } from "pinia";
import { api, getAuthToken } from "../lib/api";

const STORAGE_KEY = "aubun_cart_items";

function loadLocalItems() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
  } catch {
    return [];
  }
}

export const useCartStore = defineStore("cart", {
  state: () => ({
    items: loadLocalItems(),
    discount: 0,
    discountCode: null,
  }),

  getters: {
    totalItems: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
    subtotal: (state) => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0),
    total: (state) => state.subtotal - state.discount,
  },

  actions: {
    persistLocalState() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
    },

    syncFromPayload(cart) {
      this.items = cart.items.map((item) => ({
        ...item,
        id: item.productId,
        serverId: item.id,
      }));
      this.discount = cart.discount;
      this.discountCode = cart.discountCode;
      this.persistLocalState();
    },

    async refreshFromApi() {
      if (!getAuthToken()) return;

      try {
        const { cart } = await api.get("/cart");
        this.syncFromPayload(cart);
      } catch {
        // Keep storefront usable even if the visitor is not logged in yet.
      }
    },

    addToCart(product, size, color, quantity = 1) {
      const existingItem = this.items.find(
        (item) => item.id === product.id && item.size === size && item.color === color,
      );

      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        this.items.push({
          id: product.id,
          name: product.name,
          price: product.price,
          image: product.image,
          size,
          color,
          quantity,
        });
      }

      this.persistLocalState();

      if (getAuthToken()) {
        api.post("/cart/items", {
          product_id: product.id,
          quantity,
          size,
          color,
        }).then(({ cart }) => this.syncFromPayload(cart)).catch(() => {});
      }
    },

    removeFromCart(productId, size, color) {
      const existingItem = this.items.find(
        (item) => item.id === productId && item.size === size && item.color === color,
      );

      this.items = this.items.filter(
        (item) => !(item.id === productId && item.size === size && item.color === color),
      );
      this.persistLocalState();

      if (getAuthToken() && existingItem?.serverId) {
        api.delete(`/cart/items/${existingItem.serverId}`).then(({ cart }) => this.syncFromPayload(cart)).catch(() => {});
      }
    },

    updateQuantity(productId, size, color, quantity) {
      const item = this.items.find(
        (item) => item.id === productId && item.size === size && item.color === color,
      );
      if (item) {
        item.quantity = quantity;
        this.persistLocalState();

        if (getAuthToken() && item.serverId) {
          api.patch(`/cart/items/${item.serverId}`, { quantity }).then(({ cart }) => this.syncFromPayload(cart)).catch(() => {});
        }
      }
    },

    clearCart() {
      this.items = [];
      this.discount = 0;
      this.discountCode = null;
      this.persistLocalState();

      if (getAuthToken()) {
        api.delete("/cart").then(({ cart }) => this.syncFromPayload(cart)).catch(() => {});
      }
    },

    async applyDiscount(code) {
      if (!getAuthToken()) {
        return false;
      }

      const { cart } = await api.post("/cart/apply-discount", { code });
      this.syncFromPayload(cart);
      return true;
    },

    async checkout(payload) {
      const isGuest = !getAuthToken();
      if (isGuest) {
        payload = {
          ...payload,
          items: this.items.map((item) => ({
            product_id: item.id,
            name: item.name,
            image: item.image,
            quantity: item.quantity,
            size: item.size,
            color: item.color,
            unit_price: item.price,
            line_total: item.price * item.quantity,
          })),
          subtotal: this.subtotal,
          discount: this.discount,
          shipping_cost: payload.shippingCost || 0,
          total: this.total + (payload.shippingCost || 0),
          shipping_tier_name: payload.shippingTierName || '',
          shop_country_name: payload.shopCountryName || '',
        };
      }
      const { order, cart } = await api.post("/orders/checkout", payload);
      if (cart) {
        this.syncFromPayload(cart);
      }
      return order;
    },

    async createPayPalOrder(payload) {
      const isGuest = !getAuthToken();
      if (isGuest) {
        payload = {
          ...payload,
          items: this.items.map((item) => ({
            product_id: item.id,
            name: item.name,
            image: item.image,
            quantity: item.quantity,
            size: item.size,
            color: item.color,
            unit_price: item.price,
            line_total: item.price * item.quantity,
          })),
          subtotal: this.subtotal,
          discount: this.discount,
          shipping_cost: payload.shippingCost || 0,
          total: this.total + (payload.shippingCost || 0),
          shipping_tier_name: payload.shippingTierName || '',
          shop_country_name: payload.shopCountryName || '',
        };
      }
      return api.post("/orders", payload);
    },

    async capturePayPalOrder(orderId, payload) {
      const isGuest = !getAuthToken();
      if (isGuest) {
        payload = {
          ...payload,
          items: this.items.map((item) => ({
            product_id: item.id,
            name: item.name,
            image: item.image,
            quantity: item.quantity,
            size: item.size,
            color: item.color,
            unit_price: item.price,
            line_total: item.price * item.quantity,
          })),
          subtotal: this.subtotal,
          discount: this.discount,
          shipping_cost: payload.shippingCost || 0,
          total: this.total + (payload.shippingCost || 0),
          shipping_tier_name: payload.shippingTierName || '',
          shop_country_name: payload.shopCountryName || '',
        };
      }
      const { order, cart, paypalOrder } = await api.post(`/orders/${orderId}/capture`, payload);
      if (cart) {
        this.syncFromPayload(cart);
      }
      return { order, paypalOrder };
    },
  },
});
