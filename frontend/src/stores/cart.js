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

function isAuthenticated() {
  return Boolean(getAuthToken());
}

function clearLocalCart() {
  localStorage.removeItem(STORAGE_KEY);
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
      if (!isAuthenticated()) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
      }
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
        clearLocalCart();
      } catch (error) {
        // If authenticated but API fails, clear localStorage to avoid stale data
        if (getAuthToken()) {
          clearLocalCart();
          this.items = [];
          this.discount = 0;
          this.discountCode = null;
        }
      }
    },

    addToCart(product, size, color, quantity = 1) {
      const availableStock = product.stock ?? 0;
      const existingItem = this.items.find(
        (item) => item.id === product.id && item.size === size && item.color === color,
      );

      const currentInCart = existingItem ? existingItem.quantity : 0;
      const newQuantity = currentInCart + quantity;

      if (newQuantity > availableStock) {
        throw new Error(`Only ${availableStock} item(s) available in stock.`);
      }

      if (existingItem) {
        existingItem.quantity = newQuantity;
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
        // We need the product stock info - check if available in item or we need to pass product
        // For now, just validate quantity > 0
        if (quantity <= 0) {
          throw new Error('Quantity must be at least 1.');
        }
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

    buildOrderPayload(payload) {
      // Always include frontend items: the backend uses the DB cart for
      // logged-in users but falls back to these items when the DB cart is
      // empty/out of sync (prevents "Your cart is empty" at PayPal time).
      // When a snapshot payload (with items) is passed in — e.g. capture
      // after the cart was already cleared — respect it instead of the
      // (now empty) live cart.
      const hasSnapshotItems = Array.isArray(payload.items) && payload.items.length > 0;
      const items = hasSnapshotItems
        ? payload.items
        : this.items.map((item) => ({
          product_id: item.id ?? item.productId,
          name: item.name,
          image: item.image,
          quantity: item.quantity,
          size: item.size,
          color: item.color,
          unit_price: item.price,
          line_total: item.price * item.quantity,
        }));
      const shippingCost = payload.shippingCost || 0;
      return {
        ...payload,
        items,
        subtotal: hasSnapshotItems && typeof payload.subtotal === "number" ? payload.subtotal : this.subtotal,
        discount: hasSnapshotItems && typeof payload.discount === "number" ? payload.discount : this.discount,
        shipping_cost: hasSnapshotItems && typeof payload.shipping_cost === "number" ? payload.shipping_cost : shippingCost,
        total: hasSnapshotItems && typeof payload.total === "number" ? payload.total : this.total + shippingCost,
        shipping_tier_name: payload.shippingTierName || '',
        shop_country_name: payload.shopCountryName || '',
      };
    },

    async checkout(payload) {
      payload = this.buildOrderPayload(payload);
      const { order, cart } = await api.post("/orders/checkout", payload);
      if (cart) {
        this.syncFromPayload(cart);
      }
      return order;
    },

    async createPayPalOrder(payload) {
      payload = this.buildOrderPayload(payload);
      return api.post("/orders", payload);
    },

    async createPayPalOrderForExisting(orderNumber) {
      return api.post(`/orders/${encodeURIComponent(orderNumber)}/paypal`, {});
    },

    async capturePayPalOrder(orderId, payload) {
      payload = this.buildOrderPayload(payload);
      const { order, cart, paypalOrder } = await api.post(`/orders/${orderId}/capture`, payload);
      if (cart) {
        this.syncFromPayload(cart);
      }
      return { order, paypalOrder };
    },
  },
});
