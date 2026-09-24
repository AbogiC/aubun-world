<template>
  <div>
    <div v-if="!clientId" class="alert alert-warning py-2 small mb-0">
      Google sign-in is not configured (missing <code>VITE_GOOGLE_CLIENT_ID</code>).
    </div>
    <div v-else>
      <div ref="buttonEl"></div>
      <div v-if="error" class="alert alert-danger py-2 small mt-2 mb-0">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
  text: { type: String, default: "continue_with" }, // signin_with | signup_with | continue_with
  shape: { type: String, default: "pill" },
  theme: { type: String, default: "outline" },
  size: { type: String, default: "large" },
});

const emit = defineEmits(["credential", "error"]);

const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID || "";
const buttonEl = ref(null);
const error = ref("");

let scriptPromise = null;

function loadGis() {
  if (window.google?.accounts?.id) return Promise.resolve();
  if (scriptPromise) return scriptPromise;
  scriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = "https://accounts.google.com/gsi/client";
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error("Failed to load Google Sign-In."));
    document.head.appendChild(script);
  });
  return scriptPromise;
}

onMounted(async () => {
  if (!clientId) return;
  try {
    await loadGis();
    window.google.accounts.id.initialize({
      client_id: clientId,
      callback: (response) => {
        if (response?.credential) {
          emit("credential", response.credential);
        } else {
          const message = "Google sign-in returned no credential.";
          error.value = message;
          emit("error", message);
        }
      },
      auto_select: false,
      cancel_on_tap_outside: true,
    });
    if (buttonEl.value) {
      window.google.accounts.id.renderButton(buttonEl.value, {
        type: "standard",
        text: props.text,
        shape: props.shape,
        theme: props.theme,
        size: props.size,
        width: 320,
      });
    }
  } catch (err) {
    error.value = err.message || "Google sign-in failed to load.";
    emit("error", error.value);
  }
});
</script>
