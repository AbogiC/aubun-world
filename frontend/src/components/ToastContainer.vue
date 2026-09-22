<template>
  <div class="toast-container" aria-live="polite" aria-atomic="true">
    <div
      v-for="toast in toastStore.toasts"
      :key="toast.id"
      class="toast-item"
      :class="`toast-${toast.type}`"
    >
      <div class="toast-content">
        <i :class="iconClass(toast.type)" class="toast-icon"></i>
        <span class="toast-message">{{ toast.message }}</span>
      </div>
      <button class="toast-close" @click="toastStore.remove(toast.id)" aria-label="Dismiss">
        <i class="bi bi-x"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useToastStore } from "../stores/toast";

const toastStore = useToastStore();

const iconClass = (type) => {
  const icons = {
    success: "bi bi-check-circle-fill",
    error: "bi bi-x-circle-fill",
    warning: "bi bi-exclamation-triangle-fill",
    info: "bi bi-info-circle-fill",
  };
  return icons[type] || icons.info;
};
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: 380px;
  pointer-events: none;
}

.toast-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  background: #fff;
  border-left: 4px solid;
  animation: slideIn 0.3s ease-out;
  pointer-events: auto;
  min-width: 280px;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.toast-content {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  flex: 1;
}

.toast-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.toast-message {
  font-size: 0.9rem;
  line-height: 1.4;
  color: #1a1a2e;
}

.toast-close {
  background: none;
  border: none;
  padding: 0.25rem;
  color: #6c757d;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.2s;
}

.toast-close:hover {
  color: #1a1a2e;
}

/* Type-specific colors */
.toast-success {
  border-left-color: #28a745;
}
.toast-success .toast-icon {
  color: #28a745;
}

.toast-error {
  border-left-color: #dc3545;
}
.toast-error .toast-icon {
  color: #dc3545;
}

.toast-warning {
  border-left-color: #ffc107;
}
.toast-warning .toast-icon {
  color: #b8860b;
}

.toast-info {
  border-left-color: #0d6efd;
}
.toast-info .toast-icon {
  color: #0d6efd;
}
</style>