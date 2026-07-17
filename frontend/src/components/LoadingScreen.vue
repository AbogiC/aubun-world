<template>
  <div class="loading-screen" :class="{ 'loading-screen--hidden': !visible }">
    <div class="loading-bg">
      <div class="loading-orb orb-a"></div>
      <div class="loading-orb orb-b"></div>
      <div class="loading-orb orb-c"></div>
    </div>

    <div class="loading-content">
      <div class="loading-emblem">
        <div class="loading-ring">
          <svg viewBox="0 0 120 120" class="loading-ring-svg">
            <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(254,181,17,0.12)" stroke-width="1.5" />
            <circle
              cx="60" cy="60" r="54"
              fill="none" stroke="rgba(254,181,17,0.7)"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-dasharray="340"
              stroke-dashoffset="340"
              class="loading-ring-progress"
            />
          </svg>
          <div class="loading-ring-center">
            <div class="loading-diamond"></div>
          </div>
        </div>
      </div>

      <h1 class="loading-brand">AUBUN WORLD</h1>

      <div class="loading-bar-track">
        <div class="loading-bar-fill" ref="barRef"></div>
      </div>

      <p class="loading-tagline">Where Elegance Meets Expression</p>
    </div>

    <div class="loading-footer">
      <span class="loading-footer-text">Crafting your experience</span>
      <span class="loading-dots">
        <span></span><span></span><span></span>
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

defineProps({ visible: Boolean });

const barRef = ref(null);

onMounted(() => {
  if (barRef.value) {
    barRef.value.addEventListener("animationend", () => {
      barRef.value.style.animationPlayState = "paused";
    });
  }
});
</script>

<style scoped>
.loading-screen {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background:
    linear-gradient(160deg, rgba(60, 8, 14, 1) 0%, rgba(77, 16, 24, 1) 40%, rgba(108, 24, 35, 0.95) 70%, rgba(40, 6, 10, 1) 100%);
  color: var(--white, #fcfaf6);
  transition: opacity 0.6s ease, visibility 0.6s ease;
}

.loading-screen--hidden {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}

/* --- Background orbs --- */
.loading-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.loading-orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(80px);
  opacity: 0.35;
  animation: loadFloat 10s ease-in-out infinite;
}

.orb-a {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.25), transparent 70%);
  top: -10%;
  right: -10%;
  animation-delay: 0s;
}

.orb-b {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.15), transparent 70%);
  bottom: -15%;
  left: -10%;
  animation-delay: -3s;
}

.orb-c {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.12), transparent 70%);
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: -6s;
}

@keyframes loadFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.05); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
}

/* --- Content --- */
.loading-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
  text-align: center;
  padding: 2rem;
  animation: loadFadeIn 1s ease-out;
}

@keyframes loadFadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Emblem / Ring --- */
.loading-emblem {
  margin-bottom: 0.5rem;
}

.loading-ring {
  position: relative;
  width: 100px;
  height: 100px;
  animation: loadRingPulse 2.5s ease-in-out infinite;
}

@keyframes loadRingPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.08); opacity: 0.85; }
}

.loading-ring-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.loading-ring-progress {
  animation: loadRingDraw 2s ease-out forwards, loadRingSpin 1.8s linear infinite 2s;
}

@keyframes loadRingDraw {
  to { stroke-dashoffset: 0; }
}

@keyframes loadRingSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-ring-center {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-diamond {
  width: 18px;
  height: 18px;
  border: 2px solid var(--gold, #feb511);
  transform: rotate(45deg);
  animation: loadDiamondGlow 2s ease-in-out infinite;
}

@keyframes loadDiamondGlow {
  0%, 100% { box-shadow: 0 0 6px rgba(254, 181, 17, 0.3); opacity: 0.8; }
  50% { box-shadow: 0 0 20px rgba(254, 181, 17, 0.6); opacity: 1; }
}

/* --- Brand --- */
.loading-brand {
  font-family: "Playfair Display", Georgia, serif;
  font-size: clamp(2.2rem, 6vw, 4rem);
  font-weight: 700;
  letter-spacing: 0.35em;
  color: var(--gold, #feb511);
  text-shadow: 0 0 40px rgba(254, 181, 17, 0.15);
  animation: loadBrandReveal 1.2s ease-out;
  line-height: 1.2;
}

@keyframes loadBrandReveal {
  from {
    letter-spacing: 0.6em;
    opacity: 0;
    filter: blur(6px);
  }
  to {
    letter-spacing: 0.35em;
    opacity: 1;
    filter: blur(0);
  }
}

/* --- Progress bar --- */
.loading-bar-track {
  width: 200px;
  max-width: 60vw;
  height: 2px;
  background: rgba(254, 181, 17, 0.12);
  border-radius: 4px;
  overflow: hidden;
}

.loading-bar-fill {
  height: 100%;
  width: 0;
  background: linear-gradient(90deg, var(--gold, #feb511), rgba(254, 181, 17, 0.4));
  border-radius: 4px;
  animation: loadBar 2.4s ease-in-out forwards;
}

@keyframes loadBar {
  0% { width: 0; }
  30% { width: 22%; }
  60% { width: 55%; }
  85% { width: 78%; }
  100% { width: 92%; }
}

/* --- Tagline --- */
.loading-tagline {
  font-family: "Inter", sans-serif;
  font-size: clamp(0.75rem, 1.2vw, 0.85rem);
  letter-spacing: 0.34em;
  text-transform: uppercase;
  color: rgba(254, 181, 17, 0.5);
  font-weight: 300;
  margin-top: -0.5rem;
  animation: loadTaglineFade 1.6s ease-out;
}

@keyframes loadTaglineFade {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Footer --- */
.loading-footer {
  position: absolute;
  bottom: 2.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  z-index: 1;
  animation: loadFadeIn 1.5s ease-out;
}

.loading-footer-text {
  font-size: 0.7rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(254, 181, 17, 0.3);
  font-weight: 300;
}

.loading-dots {
  display: inline-flex;
  gap: 4px;
  align-items: center;
}

.loading-dots span {
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.4);
  animation: loadDot 1.2s ease-in-out infinite;
}

.loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes loadDot {
  0%, 100% { opacity: 0.3; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.2); }
}

/* --- Reduced motion --- */
@media (prefers-reduced-motion: reduce) {
  .loading-screen,
  .loading-orb,
  .loading-ring,
  .loading-ring-progress,
  .loading-diamond,
  .loading-brand,
  .loading-bar-fill,
  .loading-tagline,
  .loading-footer,
  .loading-dots span {
    animation: none !important;
    transition: none !important;
  }
  .loading-bar-fill { width: 92%; }
}
</style>
