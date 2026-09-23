<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="modal-overlay payment-overlay"
      @click.self="emit('close')"
      role="dialog"
      aria-modal="true"
      aria-label="Payment"
    >
      <div class="modal-dialog-box surface-elevated payment-modal-box payment-lux">
        <div class="payment-lux__glow" aria-hidden="true"></div>

        <header class="payment-lux__header">
          <div class="payment-lux__heading">
            <p class="section-kicker mb-1">Secure Checkout · Aubun World</p>
            <h2 class="modal-title mb-0">Complete Payment</h2>
            <p class="payment-lux__subtitle mb-0">
              <i class="bi bi-shield-lock-fill"></i>
              256-bit encrypted · Protected by PayPal
            </p>
          </div>
          <button type="button" class="btn btn-close-custom payment-lux__close" aria-label="Close payment" @click="emit('close')">
            <i class="bi bi-x-lg"></i>
          </button>
        </header>

        <div class="payment-lux__summary" aria-label="Order summary">
          <div class="payment-lux__summary-item">
            <span class="payment-lux__summary-label">Email</span>
            <strong class="payment-lux__summary-value payment-lux__summary-value--truncate" :title="email || invoiceEmail">{{ email || invoiceEmail || '—' }}</strong>
          </div>
          <div class="payment-lux__summary-item">
            <span class="payment-lux__summary-label">Items</span>
            <strong class="payment-lux__summary-value">{{ itemCount }}</strong>
          </div>
          <div class="payment-lux__summary-item payment-lux__summary-item--total">
            <span class="payment-lux__summary-label">Total due</span>
            <strong class="payment-lux__summary-value payment-lux__total">${{ Number(total).toLocaleString() }}</strong>
          </div>
        </div>

        <div class="payment-lux__body">
          <div class="payment-method-switch payment-lux__methods mb-3" aria-label="Payment method selector">
            <button type="button" class="payment-method-button active" disabled>
              <i class="bi bi-paypal"></i>
              PayPal
              <span class="payment-lux__badge">Recommended</span>
            </button>
            <div class="payment-lux__secure-note">
              <i class="bi bi-lock-fill"></i>
              <span>Cards & PayPal balance accepted inside the secure PayPal window.</span>
            </div>
          </div>

          <div v-if="paymentError" class="alert alert-danger mb-3">
            {{ paymentError }}
          </div>

          <div v-if="invoiceEmail" class="payment-lux__invoice mb-3" :class="invoiceSent ? 'payment-lux__invoice--sent' : 'payment-lux__invoice--pending'">
            <i class="bi bi-envelope-check-fill"></i>
            <div>
              <div class="fw-semibold">Order invoice {{ invoiceSent ? 'sent' : 'being sent' }} to {{ invoiceEmail }}</div>
              <div class="small opacity-75">Complete your PayPal payment below to confirm the order.</div>
            </div>
          </div>

          <div class="payment-lux__paypal">
            <div v-if="paypalLoading" class="payment-lux__skeleton" aria-hidden="true">
              <div class="payment-lux__skeleton-line"></div>
              <div class="payment-lux__skeleton-btn"></div>
              <div class="payment-lux__skeleton-btn payment-lux__skeleton-btn--dark"></div>
              <span class="text-muted small text-center d-block mt-2">Loading secure PayPal checkout…</span>
            </div>
            <div v-if="paypalError" class="alert alert-danger mt-3 mb-0">
              {{ paypalError }}
            </div>
            <div
              v-show="paypalEnabled && !paypalLoading"
              id="checkout-paypal-button-container"
              class="mt-1"
              :class="{ 'paypal-button-container--busy': submitting }"
            ></div>
            <div v-if="!paypalEnabled && !paypalLoading" class="alert alert-warning mt-3 mb-0">
              PayPal checkout is not configured yet. Add your PayPal client credentials on the backend first.
            </div>
          </div>
        </div>

        <footer class="payment-lux__footer">
          <span class="payment-lux__assurance">
            <i class="bi bi-patch-check-fill"></i> Buyer protection
            <span class="mx-1">·</span>
            <i class="bi bi-truck"></i> Tracked shipping
          </span>
          <button type="button" class="btn btn-link payment-lux__cancel" @click="emit('close')">
            Cancel & return
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onUnmounted, watch } from "vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  email: { type: String, default: "" },
  itemCount: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
  paymentError: { type: String, default: "" },
  invoiceEmail: { type: String, default: "" },
  invoiceSent: { type: Boolean, default: false },
  paypalLoading: { type: Boolean, default: false },
  paypalError: { type: String, default: "" },
  paypalEnabled: { type: Boolean, default: false },
  submitting: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const onKeydown = (event) => {
  if (event.key === "Escape" && props.show) emit("close");
};

watch(
  () => props.show,
  (isOpen) => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
      window.addEventListener("keydown", onKeydown);
    } else {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", onKeydown);
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  document.body.style.overflow = "";
  window.removeEventListener("keydown", onKeydown);
});
</script>

<style scoped>
.paypal-button-container--busy {
  opacity: 0.72;
  pointer-events: none;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  overflow-y: auto;
  overscroll-behavior: contain;
  background:
    linear-gradient(180deg, rgba(20, 10, 12, 0.55), rgba(20, 10, 12, 0.72)),
    radial-gradient(circle at 50% 0%, rgba(254, 181, 17, 0.16), transparent 42%),
    radial-gradient(circle at 85% 100%, rgba(40, 167, 69, 0.1), transparent 40%);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: overlayFade 0.22s ease;
}

@keyframes overlayFade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.payment-overlay {
  padding: clamp(0.75rem, 3vw, 1.5rem);
}

.payment-modal-box {
  width: min(100%, 620px);
  padding: 0;
  overflow: hidden;
  border: 1px solid rgba(212, 175, 55, 0.28);
  border-radius: 1.5rem;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 251, 235, 0.98));
  box-shadow:
    0 32px 80px rgba(20, 10, 12, 0.28),
    0 2px 0 rgba(212, 175, 55, 0.35) inset;
}

.modal-dialog-box {
  animation: modalIn 0.25s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.5rem, 2vw, 1.85rem);
}

.payment-lux {
  position: relative;
  display: flex;
  flex-direction: column;
  max-height: min(90vh, 760px);
  max-height: min(90dvh, 760px);
}

.payment-lux__glow {
  position: absolute;
  inset: 0 0 auto 0;
  height: 120px;
  pointer-events: none;
  background:
    linear-gradient(135deg, rgba(77, 16, 24, 0.1), transparent 45%),
    linear-gradient(315deg, rgba(212, 175, 55, 0.22), transparent 55%);
}

.payment-lux__header {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-shrink: 0;
  padding: 1.4rem 1.4rem 1rem;
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
}

.payment-lux__subtitle {
  margin-top: 0.35rem;
  font-size: 0.82rem;
  letter-spacing: 0.02em;
  color: var(--ink-muted);
}

.payment-lux__subtitle i {
  color: var(--success);
  margin-right: 0.3rem;
}

.payment-lux__close {
  flex-shrink: 0;
  width: 2.4rem;
  height: 2.4rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(77, 16, 24, 0.12);
  background: rgba(255, 255, 255, 0.8);
  transition: transform 180ms ease, box-shadow 180ms ease;
}

.payment-lux__close:hover {
  transform: rotate(90deg);
  box-shadow: 0 8px 20px rgba(77, 16, 24, 0.14);
}

.payment-lux__summary {
  flex-shrink: 0;
  display: grid;
  grid-template-columns: 1.4fr 0.6fr 1fr;
  gap: 0.75rem;
  margin: 1rem 1.4rem 0;
  padding: 0.85rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.04), rgba(254, 181, 17, 0.12));
}

.payment-lux__summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.payment-lux__summary-label {
  font-size: 0.68rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.payment-lux__summary-value {
  font-size: 0.95rem;
  color: var(--ink);
  white-space: nowrap;
}

.payment-lux__summary-value--truncate {
  overflow: hidden;
  text-overflow: ellipsis;
}

.payment-lux__summary-item--total {
  text-align: right;
}

.payment-lux__total {
  font-family: Georgia, serif;
  font-size: 1.25rem;
  letter-spacing: 0.01em;
  background: linear-gradient(135deg, #4d1018, #8a6d1c);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.payment-lux__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: 1rem 1.4rem 1.2rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(77, 16, 24, 0.3) transparent;
}

.payment-lux__body::-webkit-scrollbar {
  width: 8px;
}

.payment-lux__body::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(77, 16, 24, 0.35), rgba(212, 175, 55, 0.5));
}

.payment-lux__body::-webkit-scrollbar-track {
  background: transparent;
}

.payment-method-switch {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  padding: 0.4rem;
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: 1rem;
  background: rgba(77, 16, 24, 0.03);
}

.payment-method-button {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border: 1px solid transparent;
  border-radius: 0.8rem;
  background: transparent;
  color: var(--ink-soft);
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.02em;
  padding: 0.95rem 1rem;
  transition: all 220ms ease;
}

.payment-method-button i {
  font-size: 1.25rem;
  color: #003087;
}

.payment-method-button.active {
  background: linear-gradient(135deg, rgba(77, 16, 24, 0.07), rgba(254, 181, 17, 0.22));
  border-color: rgba(212, 175, 55, 0.4);
  color: var(--ink);
  box-shadow: 0 8px 20px rgba(77, 16, 24, 0.08);
}

.payment-lux__badge {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  color: #4d1018;
  background: linear-gradient(135deg, #feb511, #f3d27a);
  box-shadow: 0 4px 12px rgba(254, 181, 17, 0.4);
}

.payment-lux__methods {
  margin-bottom: 0.9rem;
}

.payment-lux__secure-note {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.8rem;
  line-height: 1.5;
  color: var(--ink-muted);
  padding: 0 0.35rem 0.15rem;
}

.payment-lux__secure-note i {
  color: var(--success);
  margin-top: 0.15rem;
}

.payment-lux__invoice {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  border-radius: 1rem;
  padding: 0.85rem 1rem;
  font-size: 0.9rem;
  line-height: 1.5;
  border: 1px solid;
}

.payment-lux__invoice i {
  font-size: 1.25rem;
  margin-top: 0.1rem;
}

.payment-lux__invoice--sent {
  color: #1e7e34;
  background: linear-gradient(135deg, #e8f5e9, #f4fbf4);
  border-color: rgba(40, 167, 69, 0.32);
}

.payment-lux__invoice--pending {
  color: #7a5b00;
  background: linear-gradient(135deg, #fff8e1, #fffdf3);
  border-color: rgba(254, 181, 17, 0.45);
}

.payment-lux__paypal {
  min-height: 90px;
}

.payment-lux__skeleton-line {
  height: 0.8rem;
  width: 55%;
  margin: 0 auto 0.9rem;
  border-radius: 999px;
  background: linear-gradient(90deg, #eee 25%, #f7f7f7 50%, #eee 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

.payment-lux__skeleton-btn {
  height: 3rem;
  border-radius: 0.8rem;
  margin-bottom: 0.7rem;
  background: linear-gradient(90deg, #ffc439 25%, #ffd970 50%, #ffc439 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

.payment-lux__skeleton-btn--dark {
  background: linear-gradient(90deg, #2c2e2f 25%, #4a4d4f 50%, #2c2e2f 75%);
  background-size: 200% 100%;
}

@keyframes shimmer {
  from { background-position: 200% 0; }
  to { background-position: -200% 0; }
}

.payment-lux__footer {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  padding: 0.9rem 1.4rem 1.2rem;
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  background: rgba(255, 255, 255, 0.7);
}

.payment-lux__assurance {
  font-size: 0.8rem;
  color: var(--ink-muted);
}

.payment-lux__assurance i {
  color: var(--success);
}

.payment-lux__cancel {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ink-soft);
  text-decoration: none;
  padding: 0.3rem 0.4rem;
}

.payment-lux__cancel:hover {
  color: var(--ink);
  text-decoration: underline;
}

@media (max-width: 575.98px) {
  .modal-overlay,
  .payment-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .payment-modal-box,
  .payment-lux {
    width: 100%;
    max-height: 94vh;
    max-height: 94dvh;
    border-radius: 1.25rem 1.25rem 0 0;
  }

  .payment-lux__header,
  .payment-lux__body,
  .payment-lux__footer {
    padding-left: 1.1rem;
    padding-right: 1.1rem;
  }

  .payment-lux__summary {
    grid-template-columns: 1fr 1fr;
    margin-left: 1.1rem;
    margin-right: 1.1rem;
  }

  .payment-lux__summary-item--total {
    grid-column: 1 / -1;
    text-align: left;
    border-top: 1px dashed rgba(77, 16, 24, 0.15);
    padding-top: 0.6rem;
  }

  .payment-lux__footer {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .payment-lux__assurance {
    order: 2;
  }

  .payment-lux__cancel {
    order: 1;
    width: 100%;
    padding: 0.7rem;
    border: 1px solid rgba(77, 16, 24, 0.12);
    border-radius: 0.8rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .modal-overlay,
  .modal-dialog-box,
  .payment-lux__close,
  .payment-lux__skeleton-line,
  .payment-lux__skeleton-btn {
    animation: none;
    transition: none;
  }
}
</style>
