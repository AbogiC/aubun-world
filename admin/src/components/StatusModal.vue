<template>
  <Teleport to="body">
    <Transition name="status-modal" appear>
      <div
        v-if="modelValue"
        class="status-modal-overlay"
        role="presentation"
        @click.self="onOverlayClick"
      >
        <div
          class="status-modal-dialog surface-elevated"
          :class="`status-modal--${type}`"
          role="dialog"
          aria-modal="true"
          :aria-label="title || typeLabel"
        >
          <button
            v-if="dismissible"
            type="button"
            class="status-modal-close"
            aria-label="Close notification"
            @click="close"
          >
            <i class="bi bi-x-lg"></i>
          </button>

          <div class="status-modal-icon" aria-hidden="true">
            <i :class="iconClass"></i>
          </div>

          <p class="section-kicker status-modal-kicker">{{ typeLabel }}</p>
          <h3 v-if="title" class="status-modal-title">{{ title }}</h3>
          <p v-if="message" class="status-modal-message">{{ message }}</p>

          <div class="status-modal-actions">
            <button
              v-if="showCancel"
              type="button"
              class="btn btn-outline-dark"
              :disabled="loading"
              @click="onCancel"
            >
              {{ cancelText }}
            </button>
            <button
              type="button"
              class="btn"
              :class="confirmButtonClass"
              :disabled="loading"
              @click="onConfirm"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from "vue";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  type: {
    type: String,
    default: "success",
    validator: (v) => ["success", "error", "warning", "info"].includes(v),
  },
  title: { type: String, default: "" },
  message: { type: String, default: "" },
  confirmText: { type: String, default: "Got it" },
  cancelText: { type: String, default: "Cancel" },
  showCancel: { type: Boolean, default: false },
  dismissible: { type: Boolean, default: true },
  loading: { type: Boolean, default: false },
  autoCloseMs: { type: Number, default: 0 },
});

const emit = defineEmits(["update:modelValue", "confirm", "cancel", "close"]);

const iconClass = computed(() => {
  switch (props.type) {
    case "success":
      return "bi bi-check-lg";
    case "error":
      return "bi bi-x-lg";
    case "warning":
      return "bi bi-exclamation-triangle";
    case "info":
    default:
      return "bi bi-info-lg";
  }
});

const typeLabel = computed(() => {
  switch (props.type) {
    case "success":
      return "Success";
    case "error":
      return "Something went wrong";
    case "warning":
      return "Please confirm";
    case "info":
    default:
      return "Notice";
  }
});

const confirmButtonClass = computed(() => {
  switch (props.type) {
    case "error":
      return "btn-danger";
    case "warning":
      return "btn-luxury";
    case "info":
      return "btn-dark";
    case "success":
    default:
      return "btn-luxury";
  }
});

const close = () => {
  if (!props.dismissible || props.loading) return;
  emit("update:modelValue", false);
  emit("close");
};

const onOverlayClick = () => {
  close();
};

const onCancel = () => {
  if (props.loading) return;
  emit("update:modelValue", false);
  emit("cancel");
  emit("close");
};

const onConfirm = () => {
  if (props.loading) return;
  emit("confirm");
  if (!props.showCancel) {
    emit("update:modelValue", false);
    emit("close");
  }
};

const onKeydown = (e) => {
  if (e.key === "Escape") close();
};

let autoCloseTimer = null;

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      document.addEventListener("keydown", onKeydown);
      document.body.style.overflow = "hidden";
      if (props.autoCloseMs > 0) {
        clearTimeout(autoCloseTimer);
        autoCloseTimer = setTimeout(() => {
          emit("update:modelValue", false);
          emit("close");
        }, props.autoCloseMs);
      }
    } else {
      document.removeEventListener("keydown", onKeydown);
      document.body.style.overflow = "";
      clearTimeout(autoCloseTimer);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  document.removeEventListener("keydown", onKeydown);
  document.body.style.overflow = "";
  clearTimeout(autoCloseTimer);
});
</script>

<style scoped>
.status-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: rgba(46, 8, 13, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.status-modal-dialog {
  position: relative;
  width: 100%;
  max-width: 26rem;
  text-align: center;
  padding: 2.25rem 1.75rem 1.75rem;
  border-radius: var(--radius-xl);
  overflow: hidden;
}

.status-modal-dialog::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-dark));
}

.status-modal--success::before {
  background: linear-gradient(90deg, #1d6b44, #2b8a5e, #1d6b44);
}

.status-modal--error::before {
  background: linear-gradient(90deg, #8f1a2b, #c2253b, #8f1a2b);
}

.status-modal--warning::before {
  background: linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-dark));
}

.status-modal--info::before {
  background: linear-gradient(90deg, var(--primary-black), var(--secondary-black), var(--primary-black));
}

.status-modal-close {
  position: absolute;
  top: 0.7rem;
  right: 0.7rem;
  width: 2rem;
  height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  border: 1px solid rgba(77, 16, 24, 0.14);
  background: rgba(255, 248, 228, 0.7);
  color: var(--primary-black);
  cursor: pointer;
  transition: transform var(--transition-base), background var(--transition-base);
}

.status-modal-close:hover {
  transform: translateY(-1px);
  background: rgba(77, 16, 24, 0.08);
}

.status-modal-icon {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: 1.7rem;
  box-shadow: var(--shadow-md);
  animation: status-pop 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.status-modal--success .status-modal-icon {
  background: linear-gradient(145deg, #2b8a5e, #1d6b44);
  color: #fff8e2;
  box-shadow: 0 16px 32px rgba(43, 138, 94, 0.35);
}

.status-modal--error .status-modal-icon {
  background: linear-gradient(145deg, #c2253b, #8f1a2b);
  color: #fff1f1;
  box-shadow: 0 16px 32px rgba(194, 37, 59, 0.35);
}

.status-modal--warning .status-modal-icon {
  background: linear-gradient(145deg, var(--gold), var(--gold-dark));
  color: var(--primary-black);
  box-shadow: 0 16px 32px rgba(254, 181, 17, 0.4);
}

.status-modal--info .status-modal-icon {
  background: linear-gradient(145deg, var(--secondary-black), var(--primary-black));
  color: var(--gold);
  box-shadow: 0 16px 32px rgba(77, 16, 24, 0.35);
}

.status-modal-kicker {
  margin-bottom: 0.5rem;
}

.status-modal--success .status-modal-kicker {
  color: #2b8a5e;
}

.status-modal--error .status-modal-kicker {
  color: var(--error);
}

.status-modal-title {
  font-size: 1.4rem;
  margin-bottom: 0.5rem;
  text-wrap: balance;
}

.status-modal-message {
  color: var(--ink-soft);
  line-height: 1.7;
  font-size: 0.95rem;
  margin-bottom: 0;
  text-wrap: pretty;
  word-break: break-word;
}

.status-modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  margin-top: 1.5rem;
  flex-wrap: wrap;
}

.status-modal-actions .btn {
  min-width: 8rem;
  padding: 0.7rem 1.5rem;
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  font-weight: 600;
}

@keyframes status-pop {
  from {
    opacity: 0;
    transform: scale(0.6);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.status-modal-enter-active,
.status-modal-leave-active {
  transition: opacity 240ms ease;
}

.status-modal-enter-active .status-modal-dialog,
.status-modal-leave-active .status-modal-dialog {
  transition: transform 280ms cubic-bezier(0.22, 1, 0.36, 1), opacity 240ms ease;
}

.status-modal-enter-from,
.status-modal-leave-to {
  opacity: 0;
}

.status-modal-enter-from .status-modal-dialog,
.status-modal-leave-to .status-modal-dialog {
  opacity: 0;
  transform: translateY(16px) scale(0.96);
}

@media (max-width: 575.98px) {
  .status-modal-overlay {
    padding: 1rem;
    align-items: flex-end;
  }

  .status-modal-dialog {
    max-width: 100%;
    padding: 2rem 1.25rem 1.25rem;
    border-radius: var(--radius-lg);
  }

  .status-modal-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .status-modal-actions .btn {
    width: 100%;
    min-width: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .status-modal-icon {
    animation: none;
  }

  .status-modal-enter-active,
  .status-modal-leave-active,
  .status-modal-enter-active .status-modal-dialog,
  .status-modal-leave-active .status-modal-dialog {
    transition: none;
  }
}
</style>
