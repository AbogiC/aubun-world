<template>
  <Transition name="consent">
    <div v-if="visible" class="consent-overlay">
      <div class="consent-banner surface-elevated">
        <div class="consent-header">
          <div class="consent-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <h3 class="consent-title">Your Privacy</h3>
            <p class="consent-subtitle">We value your trust</p>
          </div>
        </div>

        <div class="consent-body">
          <div class="consent-section">
            <div class="consent-section-head">
              <i class="bi bi-cookie"></i>
              <span>Cookies</span>
            </div>
            <p class="consent-desc">
              We use essential cookies to keep your cart and session secure. Optional cookies help us improve your experience.
            </p>
            <div class="consent-choices">
              <button
                class="consent-choice"
                :class="{ active: consentLevel === 'essential' }"
                @click="consentLevel = 'essential'"
              >
                <span class="consent-dot"></span>
                Essential Only
              </button>
              <button
                class="consent-choice"
                :class="{ active: consentLevel === 'all' }"
                @click="consentLevel = 'all'"
              >
                <span class="consent-dot"></span>
                Accept All
              </button>
            </div>
          </div>

          <div class="consent-divider"></div>

          <div class="consent-section">
            <div class="consent-section-head">
              <i class="bi bi-bell"></i>
              <span>Notifications</span>
            </div>
            <p class="consent-desc">
              Stay informed about order updates, exclusive drops, and limited collections.
            </p>
            <div class="consent-choices">
              <button
                class="consent-choice"
                :class="{ active: notifChoice === 'off' }"
                @click="notifChoice = 'off'"
              >
                <span class="consent-dot"></span>
                Off
              </button>
              <button
                class="consent-choice"
                :class="{ active: notifChoice === 'on' }"
                @click="notifChoice = 'on'"
              >
                <span class="consent-dot"></span>
                Allow
              </button>
            </div>
          </div>
        </div>

        <div class="consent-footer">
          <button class="btn btn-luxury consent-btn" @click="save">
            <span>Save Preferences</span>
            <i class="bi bi-check-lg"></i>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from "vue";

const STORAGE_KEY = "aubun_consent";

const visible = ref(false);
const consentLevel = ref("essential");
const notifChoice = ref("off");

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY);
  if (!saved) {
    setTimeout(() => {
      visible.value = true;
    }, 1200);
  } else {
    const parsed = JSON.parse(saved);
    consentLevel.value = parsed.cookies || "essential";
    notifChoice.value = parsed.notifications || "off";
  }
});

async function save() {
  localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({
      cookies: consentLevel.value,
      notifications: notifChoice.value,
    }),
  );

  if (notifChoice.value === "on" && "Notification" in window) {
    const result = await Notification.requestPermission();
    if (result === "granted") {
      new Notification("AUBUN WORLD", {
        body: "You'll now receive updates on orders and new collections.",
        icon: "/logo_aubun.png",
      });
    }
  }

  visible.value = false;
}
</script>

<style scoped>
.consent-overlay {
  position: fixed;
  inset: 0;
  z-index: 99998;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(20, 10, 12, 0.4);
  backdrop-filter: blur(3px);
  padding: 1.5rem;
}

.consent-banner {
  width: 100%;
  max-width: 540px;
  padding: 2rem;
  border-radius: var(--radius-xl);
  animation: consentSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1);
  margin-bottom: 2rem;
}

@keyframes consentSlideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* --- Header --- */
.consent-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.consent-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--primary-black), var(--secondary-black));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--gold);
  font-size: 1.3rem;
  flex-shrink: 0;
}

.consent-title {
  font-family: "Playfair Display", Georgia, serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--primary-black);
  margin: 0;
  letter-spacing: 0.04em;
}

.consent-subtitle {
  font-size: 0.75rem;
  color: var(--ink-muted);
  letter-spacing: 0.16em;
  text-transform: uppercase;
  margin: 0.15rem 0 0;
}

/* --- Body --- */
.consent-body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.consent-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.consent-section-head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--primary-black);
}

.consent-section-head i {
  font-size: 1rem;
  opacity: 0.6;
}

.consent-desc {
  font-size: 0.82rem;
  color: var(--ink-soft);
  line-height: 1.6;
  margin: 0;
}

.consent-choices {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.consent-choice {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 1rem;
  border: 1px solid rgba(77, 16, 24, 0.14);
  border-radius: 999px;
  background: rgba(255, 248, 228, 0.5);
  color: var(--primary-black);
  font-size: 0.72rem;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all var(--transition-base);
}

.consent-choice:hover {
  border-color: rgba(77, 16, 24, 0.3);
  background: rgba(255, 248, 228, 0.8);
}

.consent-choice.active {
  border-color: var(--primary-black);
  background: var(--primary-black);
  color: var(--gold);
}

.consent-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: currentColor;
  opacity: 0.4;
  transition: opacity var(--transition-base);
}

.consent-choice.active .consent-dot {
  opacity: 1;
}

.consent-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(77, 16, 24, 0.1), transparent);
}

/* --- Footer --- */
.consent-footer {
  margin-top: 1.5rem;
}

.consent-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.85rem 2rem;
  font-size: 0.72rem;
}

.consent-btn i {
  font-size: 1rem;
}

/* --- Transition --- */
.consent-enter-active {
  transition: opacity 0.35s ease, backdrop-filter 0.35s ease;
}

.consent-leave-active {
  transition: opacity 0.25s ease, backdrop-filter 0.25s ease;
}

.consent-enter-from,
.consent-leave-to {
  opacity: 0;
  backdrop-filter: blur(0px);
}

.consent-enter-from .consent-banner,
.consent-leave-to .consent-banner {
  transform: translateY(30px) scale(0.97);
}

/* --- Mobile --- */
@media (max-width: 575.98px) {
  .consent-overlay {
    padding: 0;
    align-items: flex-end;
  }

  .consent-banner {
    max-width: 100%;
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    margin-bottom: 0;
    padding: 1.5rem 1.25rem;
  }

  .consent-choices {
    flex-wrap: wrap;
  }

  .consent-choice {
    flex: 1;
    justify-content: center;
    min-width: 0;
  }
}

/* --- Reduced motion --- */
@media (prefers-reduced-motion: reduce) {
  .consent-banner {
    animation: none;
  }
}
</style>
