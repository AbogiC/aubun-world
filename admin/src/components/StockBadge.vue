<template>
  <span class="stock-badge" :class="badgeClasses">
    <template v-if="!props.editing">
      <i class="bi" :class="iconClass" aria-hidden="true"></i>
      <span class="stock-value">{{ props.stock }}</span>
      <span class="stock-label">{{ label }}</span>
    </template>
    <template v-else>
      <span class="editing-indicator">
        <i class="bi bi-pencil-fill me-1" aria-hidden="true"></i>
        Editing...
      </span>
    </template>
  </span>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  stock: {
    type: Number,
    required: true,
  },
  editing: {
    type: Boolean,
    default: false,
  },
});

const badgeClasses = computed(() => {
  if (props.editing) return "editing";
  if (props.stock === 0) return "out-of-stock";
  if (props.stock <= 10) return "low-stock";
  return "in-stock";
});

const iconClass = computed(() => {
  if (props.stock === 0) return "bi-x-circle-fill";
  if (props.stock <= 10) return "bi-exclamation-triangle-fill";
  return "bi-check-circle-fill";
});

const label = computed(() => {
  if (props.stock === 0) return "Out";
  if (props.stock <= 10) return "Low";
  return "OK";
});
</script>

<style scoped>
.stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.stock-badge .stock-value {
  font-size: 0.85rem;
  font-weight: 700;
}

.stock-badge .stock-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.9;
}

.stock-badge.out-of-stock {
  background: rgba(220, 53, 69, 0.12);
  color: #dc3545;
  border: 1px solid rgba(220, 53, 69, 0.2);
}

.stock-badge.low-stock {
  background: rgba(255, 193, 7, 0.15);
  color: #b8860b;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.stock-badge.in-stock {
  background: rgba(40, 167, 69, 0.12);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.2);
}

.stock-badge.editing {
  background: rgba(40, 167, 69, 0.1);
  color: #28a745;
  border: 1px dashed #28a745;
  animation: pulse 1.5s ease-in-out infinite;
}

.stock-badge.editing .editing-indicator {
  font-size: 0.75rem;
  font-weight: 500;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}
</style>