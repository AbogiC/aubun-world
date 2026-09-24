<template>
  <div class="orders-page py-5">
    <div class="container">
      <div class="surface p-4 p-md-5">
        <p class="section-kicker mb-3">Operations</p>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-end mb-4">
          <div>
            <h1 class="mb-2">All Orders</h1>
            <p class="text-muted mb-0">Monitor every customer order placed across the store.</p>
          </div>
          <button class="btn btn-outline-dark" :disabled="loading" @click="fetchOrders">
            {{ loading ? "Refreshing..." : "Refresh Orders" }}
          </button>
        </div>

        <div v-if="errorMessage" class="alert alert-danger mb-4">
          {{ errorMessage }}
        </div>

        <div v-else-if="loading && !orders.length" class="text-muted">
          Loading orders...
        </div>

        <div v-else-if="!orders.length" class="empty-state text-center py-5">
          <i class="bi bi-receipt display-4"></i>
          <h2 class="h4 mt-3">No orders yet</h2>
          <p class="text-muted mb-4">Customer orders will appear here as soon as checkout is completed.</p>
        </div>

        <div v-else class="order-list">
          <div class="order-toolbar">
            <div class="position-relative order-toolbar__search">
              <i class="bi bi-search order-toolbar__search-icon"></i>
              <input
                v-model="searchTerm"
                type="search"
                class="form-control order-toolbar__input"
                placeholder="Search order, customer, route, or product"
              />
            </div>

            <select v-model="selectedStatus" class="form-select order-toolbar__select">
              <option value="all">All statuses</option>
              <option v-for="status in statusOptions" :key="status" :value="status">
                {{ formatStatus(status) }}
              </option>
            </select>

            <select v-model="selectedCountry" class="form-select order-toolbar__select">
              <option value="all">All destinations</option>
              <option v-for="country in countryOptions" :key="country" :value="country">
                {{ country }}
              </option>
            </select>
          </div>

          <div class="order-results-meta">
            <span>
              Showing {{ paginatedOrders.length }} of {{ filteredOrders.length }} matching
              {{ filteredOrders.length === 1 ? "order" : "orders" }}
            </span>
            <button
              v-if="hasActiveFilters"
              type="button"
              class="btn btn-link btn-sm p-0 order-results-meta__reset"
              @click="resetFilters"
            >
              Clear search and filters
            </button>
          </div>

          <div v-if="!filteredOrders.length" class="empty-state text-center py-5">
            <i class="bi bi-funnel display-5"></i>
            <h2 class="h4 mt-3">No matching orders</h2>
            <p class="text-muted mb-4">Try a different keyword or adjust the filters.</p>
            <button type="button" class="btn btn-outline-dark" @click="resetFilters">
              Reset Filters
            </button>
          </div>

          <template v-else>
            <article v-for="order in paginatedOrders" :key="order.id" class="order-card">
              <div class="order-card__header">
                <div>
                  <div class="order-card__number">{{ order.orderNumber }}</div>
                  <div class="text-muted small">
                    Placed {{ formatDate(order.createdAt) }}
                  </div>
                </div>
                <div class="d-flex flex-column align-items-lg-end gap-2">
                  <span class="order-status">{{ formatStatus(order.status) }}</span>
                  <div class="fw-semibold fs-5">${{ formatCurrency(order.total) }}</div>
                </div>
              </div>

              <div class="order-card__meta">
                <div class="order-meta-item">
                  <span class="order-meta-label">Customer</span>
                  <span>{{ order.customerName }}</span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Email</span>
                  <span>{{ order.customerEmail }}</span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Shipping</span>
                  <span>
                    {{ order.shippingAddress }}, {{ order.shippingCity }}, {{ order.shippingCountry }}
                    {{ order.shippingPostalCode }}
                  </span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Route</span>
                  <span>{{ order.shippingShopCountry }} / {{ order.shippingTierName }}</span>
                </div>
              </div>

              <div class="order-items">
                <div
                  v-for="item in order.items"
                  :key="item.id"
                  class="order-item"
                >
                  <div class="order-item__content">
                    <div class="order-item__name">{{ item.name }}</div>
                    <div class="text-muted small">
                      {{ item.size }} / {{ item.color }} / Qty {{ item.quantity }}
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="fw-semibold">${{ formatCurrency(item.lineTotal) }}</div>
                    <div class="text-muted small">${{ formatCurrency(item.price) }} each</div>
                  </div>
                </div>
              </div>

              <div class="order-totals">
                <div class="order-total-row">
                  <span>Subtotal</span>
                  <span>${{ formatCurrency(order.subtotal) }}</span>
                </div>
                <div class="order-total-row">
                  <span>Discount</span>
                  <span :class="{ 'text-success': order.discount > 0 }">
                    -${{ formatCurrency(order.discount) }}
                  </span>
                </div>
                <div class="order-total-row">
                  <span>Shipping</span>
                  <span>${{ formatCurrency(order.shipping) }}</span>
                </div>
                <div class="order-total-row order-total-row--grand">
                  <span>Total</span>
                  <span>${{ formatCurrency(order.total) }}</span>
                </div>
              </div>

              <div class="order-fulfillment">
                <p class="order-fulfillment__meaning">
                  <i class="bi bi-info-circle me-1"></i>{{ statusMeaning(order.status) }}
                </p>

                <div v-if="order.courier || order.trackingNumber" class="order-tracking">
                  <div class="order-meta-item">
                    <span class="order-meta-label">Courier</span>
                    <span>{{ order.courier || "-" }}</span>
                  </div>
                  <div class="order-meta-item">
                    <span class="order-meta-label">Tracking / Shipping ID</span>
                    <span class="order-tracking__number">{{ order.trackingNumber || "-" }}</span>
                  </div>
                </div>

                <div v-if="actionFeedback[order.id]" class="alert py-2 px-3 mb-3" :class="actionFeedback[order.id].type === 'error' ? 'alert-danger' : 'alert-success'">
                  {{ actionFeedback[order.id].message }}
                </div>

                <div class="order-actions">
                  <button
                    v-if="order.status === 'paid'"
                    type="button"
                    class="btn btn-dark"
                    :disabled="updatingOrderId === order.id"
                    @click="confirmOrder(order)"
                  >
                    <i class="bi bi-check-circle me-1"></i>
                    {{ updatingOrderId === order.id ? "Confirming..." : "Confirm Products" }}
                  </button>

                  <button
                    v-if="order.status === 'processing'"
                    type="button"
                    class="btn btn-dark"
                    :disabled="updatingOrderId === order.id"
                    @click="markPacked(order)"
                  >
                    <i class="bi bi-box-seam me-1"></i>
                    {{ updatingOrderId === order.id ? "Saving..." : "Mark as Packed" }}
                  </button>

                  <template v-if="order.status === 'packed'">
                    <button
                      v-if="!shipForms[order.id]?.open"
                      type="button"
                      class="btn btn-outline-dark"
                      @click="openShipForm(order)"
                    >
                      <i class="bi bi-truck me-1"></i> Add Shipping Info
                    </button>
                    <form v-else class="ship-form" @submit.prevent="shipOrder(order)">
                      <div class="row g-2">
                        <div class="col-md-6">
                          <label class="form-label small">Courier</label>
                          <input
                            v-model="shipForms[order.id].courier"
                            type="text"
                            class="form-control"
                            placeholder="e.g. DHL, JNE, FedEx"
                            required
                            :disabled="updatingOrderId === order.id"
                          />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small">Tracking / Shipping ID</label>
                          <input
                            v-model="shipForms[order.id].trackingNumber"
                            type="text"
                            class="form-control"
                            placeholder="e.g. AWB123456789"
                            required
                            :disabled="updatingOrderId === order.id"
                          />
                        </div>
                      </div>
                      <div class="d-flex flex-wrap gap-2 mt-2">
                        <button
                          type="submit"
                          class="btn btn-dark"
                          :disabled="updatingOrderId === order.id"
                        >
                          <i class="bi bi-send me-1"></i>
                          {{ updatingOrderId === order.id ? "Shipping..." : "Ship — Out for Delivery" }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-link btn-sm text-decoration-none"
                          :disabled="updatingOrderId === order.id"
                          @click="shipForms[order.id].open = false"
                        >
                          Cancel
                        </button>
                      </div>
                      <p class="text-muted small mb-0 mt-2">
                        After saving, the status changes to Out for Delivery and an email with the
                        courier and tracking ID is sent to the customer.
                      </p>
                    </form>
                  </template>

                  <template v-if="order.status === 'shipped'">
                    <button
                      v-if="!shipForms[order.id]?.open"
                      type="button"
                      class="btn btn-outline-dark btn-sm"
                      @click="openShipForm(order)"
                    >
                      <i class="bi bi-pencil me-1"></i> Edit Shipping Info
                    </button>
                    <form v-else class="ship-form" @submit.prevent="saveTracking(order)">
                      <div class="row g-2">
                        <div class="col-md-6">
                          <label class="form-label small">Courier</label>
                          <input
                            v-model="shipForms[order.id].courier"
                            type="text"
                            class="form-control"
                            required
                            :disabled="updatingOrderId === order.id"
                          />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small">Tracking / Shipping ID</label>
                          <input
                            v-model="shipForms[order.id].trackingNumber"
                            type="text"
                            class="form-control"
                            required
                            :disabled="updatingOrderId === order.id"
                          />
                        </div>
                      </div>
                      <div class="d-flex flex-wrap gap-2 mt-2">
                        <button
                          type="submit"
                          class="btn btn-outline-dark btn-sm"
                          :disabled="updatingOrderId === order.id"
                        >
                          {{ updatingOrderId === order.id ? "Saving..." : "Save Shipping Info" }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-link btn-sm text-decoration-none"
                          :disabled="updatingOrderId === order.id"
                          @click="shipForms[order.id].open = false"
                        >
                          Cancel
                        </button>
                      </div>
                    </form>
                    <button
                      type="button"
                      class="btn btn-dark"
                      :disabled="updatingOrderId === order.id"
                      title="An email will be sent to the customer confirming delivery."
                      @click="markDelivered(order)"
                    >
                      <i class="bi bi-house-check me-1"></i>
                      {{ updatingOrderId === order.id ? "Saving..." : "Mark as Delivered" }}
                    </button>
                  </template>
                </div>
              </div>
            </article>

            <nav v-if="totalPages > 1" class="pagination-shell" aria-label="Orders pagination">
              <button
                type="button"
                class="btn btn-outline-dark"
                :disabled="currentPage === 1"
                @click="goToPage(currentPage - 1)"
              >
                Previous
              </button>
              <div class="pagination-shell__pages">
                <button
                  v-for="page in totalPages"
                  :key="`page-${page}`"
                  type="button"
                  class="btn btn-sm"
                  :class="page === currentPage ? 'btn-dark' : 'btn-outline-dark'"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>
              </div>
              <button
                type="button"
                class="btn btn-outline-dark"
                :disabled="currentPage === totalPages"
                @click="goToPage(currentPage + 1)"
              >
                Next
              </button>
            </nav>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { api } from "../lib/api";
import { useAuthStore } from "../stores/auth";

const PAGE_SIZE = 6;

// Customer-facing meanings shared with the storefront:
// paid = customer already paid · processing = admin confirmed the products ·
// packed = admin packed the parcel · shipped = out for delivery with courier ·
// delivered = parcel arrived.
const STATUS_MEANINGS = {
  pending: "Awaiting payment from the customer.",
  paid: "Customer already paid — ready for admin confirmation.",
  processing: "Admin confirmed the products — preparing the parcel.",
  packed: "Admin already packed the product — ready to hand over to the courier.",
  shipped: "Out for delivery — parcel is with the courier, customer can track it.",
  delivered: "Parcel already arrived to the customer.",
  cancelled: "Order was cancelled.",
};

const FULFILLMENT_STATUSES = ["paid", "processing", "packed", "shipped", "delivered"];

const authStore = useAuthStore();
const orders = ref([]);
const loading = ref(false);
const errorMessage = ref("");
const searchTerm = ref("");
const selectedStatus = ref("all");
const selectedCountry = ref("all");
const currentPage = ref(1);
const updatingOrderId = ref(null);
const actionFeedback = ref({});
const shipForms = ref({});

const statusOptions = computed(() => {
  const fromOrders = orders.value.map((order) => order.status).filter(Boolean);
  return [...new Set([...FULFILLMENT_STATUSES, ...fromOrders])];
});
const countryOptions = computed(() =>
  [...new Set(orders.value.map((order) => order.shippingCountry).filter(Boolean))].sort((left, right) =>
    left.localeCompare(right),
  ),
);
const filteredOrders = computed(() => {
  const keyword = searchTerm.value.trim().toLowerCase();

  return orders.value.filter((order) => {
    const matchesStatus = selectedStatus.value === "all" || order.status === selectedStatus.value;
    const matchesCountry =
      selectedCountry.value === "all" || order.shippingCountry === selectedCountry.value;
    const matchesSearch =
      keyword === "" ||
      [
        order.orderNumber,
        order.customerName,
        order.customerEmail,
        order.shippingAddress,
        order.shippingCity,
        order.shippingCountry,
        order.shippingPostalCode,
        order.shippingShopCountry,
        order.shippingTierName,
        ...order.items.map((item) => `${item.name} ${item.size} ${item.color}`),
        order.courier,
        order.trackingNumber,
      ].some((value) => String(value || "").toLowerCase().includes(keyword));

    return matchesStatus && matchesCountry && matchesSearch;
  });
});
const totalPages = computed(() => Math.max(1, Math.ceil(filteredOrders.value.length / PAGE_SIZE)));
const paginatedOrders = computed(() => {
  const startIndex = (currentPage.value - 1) * PAGE_SIZE;
  return filteredOrders.value.slice(startIndex, startIndex + PAGE_SIZE);
});
const hasActiveFilters = computed(
  () =>
    searchTerm.value.trim() !== "" ||
    selectedStatus.value !== "all" ||
    selectedCountry.value !== "all",
);

const fetchOrders = async () => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const payload = await api.get("/orders");
    orders.value = payload.orders || [];
  } catch (error) {
    errorMessage.value = error.message || "Unable to load orders.";
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  searchTerm.value = "";
  selectedStatus.value = "all";
  selectedCountry.value = "all";
};

const goToPage = (page) => {
  currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
};

const formatCurrency = (value) => Number(value || 0).toLocaleString();

const formatDate = (value) => {
  if (!value) {
    return "-";
  }

  return new Date(value).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
};

const formatStatus = (status) => {
  const key = String(status || "").toLowerCase();

  if (key === "shipped") {
    return "Out for delivery";
  }

  const normalized = String(status || "").replace(/[_-]/g, " ").trim();

  if (!normalized) {
    return "Unknown";
  }

  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const statusMeaning = (status) => STATUS_MEANINGS[String(status || "").toLowerCase()] || "";

const setFeedback = (orderId, type, message) => {
  actionFeedback.value = {
    ...actionFeedback.value,
    [orderId]: { type, message },
  };
};

const applyUpdatedOrder = (updated) => {
  if (!updated) {
    return;
  }

  orders.value = orders.value.map((order) => (order.id === updated.id ? { ...order, ...updated } : order));
};

const openShipForm = (order) => {
  shipForms.value = {
    ...shipForms.value,
    [order.id]: {
      open: true,
      courier: order.courier || "",
      trackingNumber: order.trackingNumber || "",
    },
  };
};

const updateOrderStatus = async (order, payload, successMessage) => {
  updatingOrderId.value = order.id;

  try {
    const response = await api.patch(`/orders/${order.id}`, payload);
    applyUpdatedOrder(response.order);

    if (shipForms.value[order.id]) {
      shipForms.value = {
        ...shipForms.value,
        [order.id]: { ...shipForms.value[order.id], open: false },
      };
    }

    setFeedback(order.id, "success", successMessage);
  } catch (error) {
    setFeedback(order.id, "error", error.message || "Unable to update the order.");
  } finally {
    updatingOrderId.value = null;
  }
};

// paid -> processing: admin confirms the products, next step is packing + shipping info.
const confirmOrder = (order) =>
  updateOrderStatus(order, { status: "processing" }, "Order confirmed — now pack the products.");

// processing -> packed: admin finished packing, next step is courier + tracking info.
const markPacked = (order) =>
  updateOrderStatus(order, { status: "packed" }, "Order marked as packed — add the shipping info next.");

// packed -> shipped (out for delivery): requires courier + tracking number for customer tracking.
const shipOrder = (order) => {
  const form = shipForms.value[order.id] || {};
  const courier = String(form.courier || "").trim();
  const trackingNumber = String(form.trackingNumber || "").trim();

  if (!courier || !trackingNumber) {
    setFeedback(order.id, "error", "Courier and tracking number are required to ship an order.");
    return;
  }

  return updateOrderStatus(
    order,
    { status: "shipped", courier, trackingNumber },
    "Shipping info saved — order is now out for delivery.",
  );
};

const saveTracking = (order) => {
  const form = shipForms.value[order.id] || {};
  const courier = String(form.courier || "").trim();
  const trackingNumber = String(form.trackingNumber || "").trim();

  if (!courier || !trackingNumber) {
    setFeedback(order.id, "error", "Courier and tracking number are required.");
    return;
  }

  return updateOrderStatus(order, { status: "shipped", courier, trackingNumber }, "Shipping info updated.");
};

// shipped -> delivered: parcel arrived to the customer.
const markDelivered = (order) =>
  updateOrderStatus(order, { status: "delivered" }, "Order marked as delivered.");

watch([searchTerm, selectedStatus, selectedCountry], () => {
  currentPage.value = 1;
});

watch(totalPages, (pageCount) => {
  if (currentPage.value > pageCount) {
    currentPage.value = pageCount;
  }
});

onMounted(async () => {
  await authStore.initialize();
  await fetchOrders();
});
</script>

<style scoped>
.orders-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.16), transparent 30%),
    linear-gradient(180deg, rgba(255, 241, 184, 1), rgba(254, 181, 17, 0.3));
  min-height: 60vh;
}

.empty-state {
  border: 1px dashed rgba(77, 16, 24, 0.18);
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.45);
}

.order-list {
  display: grid;
  gap: 1.5rem;
}

.order-toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) repeat(2, minmax(180px, 0.7fr));
  gap: 1rem;
  align-items: center;
}

.order-toolbar__search {
  min-width: 0;
}

.order-toolbar__search-icon {
  position: absolute;
  top: 50%;
  left: 0.9rem;
  transform: translateY(-50%);
  color: var(--ink-muted);
}

.order-toolbar__input {
  padding-left: 2.5rem;
}

.order-results-meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  color: var(--ink-muted);
  font-size: 0.95rem;
}

.order-results-meta__reset {
  color: var(--ink);
  text-decoration: none;
}

.order-card {
  padding: 1.5rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: 1.4rem;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.74), rgba(255, 241, 184, 0.68)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.08), transparent 30%);
  box-shadow: 0 18px 34px rgba(77, 16, 24, 0.08);
}

.order-card__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1.25rem;
}

.order-card__number {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.order-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(77, 16, 24, 0.08);
  font-size: 0.75rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.order-card__meta {
  display: grid;
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.order-meta-item {
  display: grid;
  gap: 0.18rem;
}

.order-meta-label {
  font-size: 0.76rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.order-items {
  display: grid;
  gap: 0.85rem;
  padding: 1rem 0;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
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

.order-totals {
  margin-top: 1rem;
  margin-left: auto;
  max-width: 320px;
}

.order-total-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.28rem 0;
}

.order-total-row--grand {
  margin-top: 0.35rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  font-weight: 700;
}

.order-fulfillment {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px dashed rgba(77, 16, 24, 0.18);
  display: grid;
  gap: 0.9rem;
}

.order-fulfillment__meaning {
  margin: 0;
  color: var(--ink-muted);
  font-size: 0.9rem;
}

.order-tracking {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.85rem;
  padding: 0.85rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.55);
}

.order-tracking__number {
  font-weight: 600;
  letter-spacing: 0.04em;
}

.order-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: flex-start;
}

.ship-form {
  flex: 1 1 100%;
  padding: 1rem;
  border: 1px solid rgba(77, 16, 24, 0.1);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.55);
}

.pagination-shell {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.pagination-shell__pages {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

@media (max-width: 991px) {
  .order-toolbar {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767px) {
  .order-card {
    padding: 1.2rem;
  }

  .order-results-meta,
  .pagination-shell {
    flex-direction: column;
    align-items: stretch;
  }

  .order-card__header,
  .order-item {
    flex-direction: column;
  }

  .order-totals {
    max-width: none;
  }
}
</style>