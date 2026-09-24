<template>
  <div class="orders-page py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="surface p-4 p-md-5">
            <p class="section-kicker mb-3">My Account</p>
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-end mb-4">
              <div>
                <h1 class="mb-2">My Orders</h1>
                <p class="text-muted mb-0">Track your purchases from payment to delivery.</p>
              </div>
              <button class="btn btn-outline-dark" :disabled="loading" @click="fetchOrders">
                {{ loading ? "Refreshing..." : "Refresh Orders" }}
              </button>
            </div>

            <div v-if="loading && !orders.length" class="text-muted">Loading orders...</div>
            <div v-else-if="errorMessage" class="alert alert-danger">
              {{ errorMessage }}
              <button type="button" class="btn btn-outline-dark btn-sm ms-2" @click="fetchOrders">
                Retry
              </button>
            </div>
            <div v-else-if="!orders.length" class="empty-state text-center py-5">
              <i class="bi bi-receipt display-4"></i>
              <h2 class="h4 mt-3">No orders yet</h2>
              <p class="text-muted mb-4">Your completed checkouts will appear here.</p>
              <router-link to="/products" class="btn btn-luxury">Start Shopping</router-link>
            </div>

            <div v-else class="order-list">
              <article v-for="order in orders" :key="order.id" class="order-card surface-elevated p-3 p-md-4 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <span class="fw-semibold">{{ order.orderNumber || `#${order.id}` }}</span>
                    <span class="text-muted small ms-2">{{ formatDate(order.createdAt) }}</span>
                  </div>
                  <span class="fw-semibold text-nowrap">${{ formatCurrency(order.total) }}</span>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                  <span class="badge text-bg-dark">{{ formatOrderStatus(order.status) }}</span>
                  <span class="text-muted small">{{ orderStatusMeaning(order.status) }}</span>
                </div>

                <div class="order-items">
                  <div v-for="item in order.items || []" :key="item.id" class="order-item">
                    <div class="order-item__content">
                      <div class="order-item__name">{{ item.name }}</div>
                      <div class="text-muted small">
                        {{ item.size }} / {{ item.color }} / Qty {{ item.quantity }}
                      </div>
                    </div>
                    <div class="text-end text-nowrap">
                      <div class="fw-semibold">${{ formatCurrency(item.lineTotal) }}</div>
                    </div>
                  </div>
                </div>

                <div v-if="order.courier || order.trackingNumber" class="order-tracking mt-3">
                  <div><strong>Courier:</strong> {{ order.courier || "-" }}</div>
                  <div><strong>Tracking ID:</strong> {{ order.trackingNumber || "-" }}</div>
                  <div class="text-muted small mt-1">
                    Use the tracking ID on the courier website to track your parcel.
                  </div>
                </div>

                <div class="text-muted small mt-2">
                  {{ order.shippingCity || "-" }}, {{ order.shippingCountry || "-" }}
                </div>
              </article>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { api } from "../lib/api";
import { useAuthStore } from "../stores/auth";

const authStore = useAuthStore();
const orders = ref([]);
const loading = ref(false);
const errorMessage = ref("");

// Customer-facing order meanings:
// paid = customer already paid the bill · processing = admin confirmed the products ·
// packed = admin already packed the product · shipped = product already in courier ·
// delivered = parcel already arrived to customer.
const ORDER_STATUS_MEANINGS = {
  pending: "Awaiting payment.",
  paid: "You already paid the bill — waiting for admin confirmation.",
  processing: "Admin confirmed the products — preparing your parcel.",
  packed: "Admin already packed your product.",
  shipped: "Out for delivery — your product is already in the courier.",
  delivered: "Delivered — your parcel already arrived.",
  cancelled: "This order was cancelled.",
};

const fetchOrders = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    const payload = await api.get("/orders");
    const rawOrders = payload.orders || payload.data || [];
    // Normalize so one malformed order can never blank the whole list.
    orders.value = (Array.isArray(rawOrders) ? rawOrders : []).map((order) => ({
      ...order,
      items: Array.isArray(order.items) ? order.items : [],
      total: Number(order.total ?? 0),
    }));
  } catch (error) {
    orders.value = [];
    errorMessage.value = error.message || "Unable to load orders.";
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => Number(value || 0).toLocaleString();

const formatDate = (value) => {
  if (!value) return "-";
  return new Date(value).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatOrderStatus = (status) => {
  const key = String(status || "").toLowerCase();
  if (key === "shipped") return "Out for delivery";
  if (!key) return "Unknown";
  return key.charAt(0).toUpperCase() + key.slice(1);
};

const orderStatusMeaning = (status) => ORDER_STATUS_MEANINGS[String(status || "").toLowerCase()] || "";

onMounted(async () => {
  try {
    await authStore.refreshUser();
  } catch {
    // Auth refresh failure must never block the order history.
  }
  await fetchOrders();
});
</script>

<style scoped>
.orders-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.16), transparent 32%),
    linear-gradient(180deg, rgba(255, 241, 184, 1), rgba(254, 181, 17, 0.34));
  min-height: 60vh;
}

.empty-state {
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.45);
}

.order-list {
  display: grid;
  gap: 1rem;
}

.order-card {
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: var(--radius-md);
  background: rgba(255, 248, 228, 0.5);
}

.order-items {
  display: grid;
  gap: 0.6rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
}

.order-item {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.order-item__content {
  min-width: 0;
}

.order-item__name {
  font-weight: 600;
}

.order-tracking {
  padding: 0.65rem 0.8rem;
  border: 1px dashed rgba(77, 16, 24, 0.2);
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.6);
}
</style>
