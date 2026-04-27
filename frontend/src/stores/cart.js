import { defineStore } from "pinia";

export const useCartStore = defineStore("cart", {
  state: () => ({
    items: [],
    discount: 0,
  }),

  getters: {
    totalItems: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
    subtotal: (state) => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0),
    total: (state) => state.subtotal - state.discount,
  },

  actions: {
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
    },

    removeFromCart(productId, size, color) {
      this.items = this.items.filter(
        (item) => !(item.id === productId && item.size === size && item.color === color),
      );
    },

    updateQuantity(productId, size, color, quantity) {
      const item = this.items.find(
        (item) => item.id === productId && item.size === size && item.color === color,
      );
      if (item) {
        item.quantity = quantity;
      }
    },

    clearCart() {
      this.items = [];
    },

    applyDiscount(code) {
      if (code === "LUXURY20") {
        this.discount = this.subtotal * 0.2;
        return true;
      }
      return false;
    },
  },
});
