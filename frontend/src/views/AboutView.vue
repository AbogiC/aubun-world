<template>
  <div ref="aboutRootRef" class="about-page">
    <section class="about-hero" data-reveal-section>
      <div class="container text-center">
        <div class="hero-content">
          <p class="section-kicker" style="color: rgba(254, 181, 17, 0.7);">{{ heroKicker }}</p>
          <div class="hero-divider"></div>
          <h1 class="display-3 mb-4" style="color: var(--gold-light);">{{ heroTitle }}</h1>
          <p class="lead opacity-75 mb-0" style="color: var(--white);">{{ heroSubtitle }}</p>
          <div class="scroll-indicator">
            <span>Discover More</span>
            <i class="bi bi-chevron-down"></i>
          </div>
        </div>
      </div>
      <div class="hero-ornament hero-ornament-1"></div>
      <div class="hero-ornament hero-ornament-2"></div>
    </section>

    <section class="about-section py-5" data-reveal-section>
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6 mb-4">
            <div class="about-visual surface d-flex align-items-center justify-content-center subtle-glow">
              <img
                v-if="missionImageUrl"
                :src="missionImageUrl"
                alt="Our mission"
                class="about-mission-image"
              />
              <div v-else class="visual-icon-group">
                <i class="bi bi-gem"></i>
                <i class="bi bi-diamond-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="section-heading">
              <p class="section-kicker">{{ missionKicker }}</p>
              <h2 class="mb-4">{{ missionTitle }}</h2>
            </div>
            <div class="section-content">
              <p v-if="missionLead" class="lead mb-3">
                {{ missionLead }}
              </p>
              <p v-if="missionBody1">
                {{ missionBody1 }}
              </p>
              <p v-if="missionBody2">
                {{ missionBody2 }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-section values-section py-5" data-reveal-section>
      <div class="container">
        <div class="section-title section-heading">
          <h2>{{ valuesTitle }}</h2>
          <p class="text-muted">{{ valuesSubtitle }}</p>
        </div>
        <div class="row g-4 section-content">
          <div v-for="(value, index) in displayValues" :key="value.title" class="col-md-6 col-lg-3">
            <div
              class="value-card surface text-center p-4 hover-lift card-stagger"
              :style="{ transitionDelay: `${120 + index * 100}ms` }"
            >
              <div class="value-icon-wrap">
                <i :class="value.icon"></i>
              </div>
              <h5>{{ value.title }}</h5>
              <p class="mb-0 text-muted small">{{ value.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-section py-5" data-reveal-section>
      <div class="container">
        <div class="section-title section-heading">
          <h2>{{ teamTitle }}</h2>
          <p class="text-muted">{{ teamSubtitle }}</p>
        </div>
        <div class="row g-4 section-content">
          <div
            v-for="(member, index) in displayTeam"
            :key="member.name"
            class="col-md-4 mb-4"
          >
            <div
              class="team-card surface text-center p-4 hover-lift card-stagger"
              :style="{ transitionDelay: `${120 + index * 80}ms` }"
            >
              <div class="team-avatar mx-auto mb-3 d-flex align-items-center justify-content-center">
                <span class="team-initials">{{ getInitials(member.name) }}</span>
              </div>
              <h5>{{ member.name }}</h5>
              <p class="role-badge">{{ member.role }}</p>
              <div class="team-social">
                <a href="#" class="team-social-link" @click.prevent><i class="bi bi-linkedin"></i></a>
                <a href="#" class="team-social-link" @click.prevent><i class="bi bi-envelope-fill"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { api } from "../lib/api";

const aboutRootRef = ref(null);

const DEFAULT_VALUES = [
  { icon: "bi bi-scissors", title: "Craftsmanship", description: "Every stitch tells a story of precision and passion, honoring the art of fine garment making." },
  { icon: "bi bi-globe2", title: "Sustainability", description: "Committed to ethical sourcing and eco-conscious practices that protect our planet." },
  { icon: "bi bi-stars", title: "Innovation", description: "Blending timeless design with modern techniques to create something truly unique." },
  { icon: "bi bi-clock-history", title: "Heritage", description: "Rooted in tradition, evolving with purpose. Our legacy is woven into every piece." },
];

const DEFAULT_TEAM = [
  { name: "Sophia Laurent", role: "Founder & Creative Director" },
  { name: "Marcus Chen", role: "Head of Design" },
  { name: "Isabella Rossi", role: "Marketing Director" },
  { name: "James Whitmore", role: "Production Manager" },
  { name: "Olivia Park", role: "Sustainability Officer" },
  { name: "David Thompson", role: "Customer Experience" },
];

const DEFAULTS = {
  heroKicker: "Our Story",
  heroTitle: "Our Story",
  heroSubtitle: "Crafting elegance since 2010",
  missionKicker: "Our Purpose",
  missionTitle: "Our Mission",
  missionLead: "To create timeless pieces that transcend trends and become cherished wardrobe staples.",
  missionBody1:
    "At Aubun World, we believe that true luxury lies in the details. Every stitch, every fabric choice, and every design element is carefully considered to create garments that not only look exceptional but feel extraordinary to wear.",
  missionBody2:
    "Our commitment to quality craftsmanship and sustainable practices ensures that each piece is not just a purchase, but an investment in enduring style.",
  missionImageUrl: "",
  valuesTitle: "Our Values",
  valuesSubtitle: "The principles that guide every creation",
  teamTitle: "Our Team",
  teamSubtitle: "The people behind the brand",
};

const aboutSettings = ref(null);
const valuesList = ref([]);
const teamList = ref([]);

const pick = (value, fallback) => {
  if (value === null || value === undefined) return fallback;
  if (typeof value === "string" && value.trim() === "") return fallback;
  return value;
};

const heroKicker = computed(() => pick(aboutSettings.value?.heroKicker, DEFAULTS.heroKicker));
const heroTitle = computed(() => pick(aboutSettings.value?.heroTitle, DEFAULTS.heroTitle));
const heroSubtitle = computed(() => pick(aboutSettings.value?.heroSubtitle, DEFAULTS.heroSubtitle));
const missionKicker = computed(() => pick(aboutSettings.value?.missionKicker, DEFAULTS.missionKicker));
const missionTitle = computed(() => pick(aboutSettings.value?.missionTitle, DEFAULTS.missionTitle));
const missionLead = computed(() => pick(aboutSettings.value?.missionLead, DEFAULTS.missionLead));
const missionBody1 = computed(() => pick(aboutSettings.value?.missionBody1, DEFAULTS.missionBody1));
const missionBody2 = computed(() => pick(aboutSettings.value?.missionBody2, DEFAULTS.missionBody2));
const missionImageUrl = computed(() => (aboutSettings.value?.missionImageUrl || "").trim());
const valuesTitle = computed(() => pick(aboutSettings.value?.valuesTitle, DEFAULTS.valuesTitle));
const valuesSubtitle = computed(() => pick(aboutSettings.value?.valuesSubtitle, DEFAULTS.valuesSubtitle));
const teamTitle = computed(() => pick(aboutSettings.value?.teamTitle, DEFAULTS.teamTitle));
const teamSubtitle = computed(() => pick(aboutSettings.value?.teamSubtitle, DEFAULTS.teamSubtitle));

const displayValues = computed(() => {
  const active = (valuesList.value || []).filter((v) => v.isActive !== false && v.title);
  return active.length ? active : DEFAULT_VALUES;
});

const displayTeam = computed(() => {
  const active = (teamList.value || []).filter((m) => m.isActive !== false && m.name);
  return active.length ? active : DEFAULT_TEAM;
});

let sectionObserver;

const getInitials = (name) => {
  return (name || "")
    .split(" ")
    .filter(Boolean)
    .map((n) => n[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
};

const fetchAboutSettings = async () => {
  try {
    const data = await api.get("/about-view");
    if (data.settings) {
      aboutSettings.value = data.settings;
      valuesList.value = data.values || data.settings.values || [];
      teamList.value = data.team || data.settings.team || [];
    }
  } catch (error) {
    console.error("Failed to fetch about settings:", error);
    aboutSettings.value = null;
  }
};

onMounted(async () => {
  await fetchAboutSettings();
  await nextTick();

  const sections = aboutRootRef.value?.querySelectorAll("[data-reveal-section]");
  if (sections?.length) {
    sectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          sectionObserver?.unobserve(entry.target);
        });
      },
      { threshold: 0.18, rootMargin: "0px 0px -10% 0px" },
    );
    sections.forEach((section) => sectionObserver.observe(section));
  }
});

onBeforeUnmount(() => {
  sectionObserver?.disconnect();
});
</script>

<style scoped>
.about-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.2), transparent 28%),
    linear-gradient(180deg, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.62));
}

.about-hero,
.about-section {
  position: relative;
  opacity: 0;
  transform: translateY(44px);
  filter: blur(10px);
  transition:
    opacity 760ms cubic-bezier(0.22, 1, 0.36, 1),
    transform 760ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 760ms ease;
}

.about-hero.is-visible,
.about-section.is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.section-heading,
.section-content,
.card-stagger {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-section.is-visible .section-heading {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 70ms;
}

.about-section.is-visible .section-content {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 170ms;
}

.about-section.is-visible .card-stagger {
  opacity: 1;
  transform: translateY(0);
}

.about-hero {
  min-height: 85vh;
  display: flex;
  align-items: center;
  padding: 6rem 0 4rem;
  background:
    linear-gradient(135deg, rgba(77, 16, 24, 0.98), rgba(108, 24, 35, 0.94)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.15), transparent 45%);
  background-size: 200% 200%;
  animation: heroGradient 12s ease infinite;
  overflow: hidden;
  isolation: isolate;
}

@keyframes heroGradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.about-hero::before,
.about-hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

.about-hero::before {
  width: 22rem;
  height: 22rem;
  top: -7rem;
  right: -5rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.25), transparent 72%);
}

.about-hero::after {
  width: 18rem;
  height: 18rem;
  bottom: -6rem;
  left: -3rem;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.14), transparent 72%);
}

.hero-content {
  position: relative;
  z-index: 1;
  text-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
}

.hero-divider {
  width: 64px;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  margin: 1rem auto;
}

.scroll-indicator {
  margin-top: 3rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  color: rgba(254, 181, 17, 0.6);
  font-size: 0.7rem;
  letter-spacing: 0.24em;
  text-transform: uppercase;
}

.scroll-indicator i {
  font-size: 1.2rem;
  animation: bounceDown 2s ease infinite;
}

@keyframes bounceDown {
  0%, 100% { transform: translateY(0); opacity: 1; }
  50% { transform: translateY(6px); opacity: 0.5; }
}

.hero-ornament {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: var(--gold);
  opacity: 0.3;
  z-index: 0;
  pointer-events: none;
}

.hero-ornament-1 {
  top: 20%;
  left: 8%;
  width: 6px;
  height: 6px;
  animation: floatOrnament 6s ease-in-out infinite;
}

.hero-ornament-2 {
  bottom: 25%;
  right: 10%;
  width: 8px;
  height: 8px;
  animation: floatOrnament 8s ease-in-out infinite reverse;
}

@keyframes floatOrnament {
  0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
  50% { transform: translateY(-18px) scale(1.3); opacity: 0.6; }
}

.about-visual {
  height: 400px;
  border-radius: var(--radius-lg);
  position: relative;
  overflow: hidden;
}

.about-mission-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.visual-icon-group {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  justify-content: center;
}

.visual-icon-group i {
  color: var(--primary-black);
  opacity: 0.7;
  font-size: 3rem;
  animation: pulseIcon 3s ease-in-out infinite;
}

.visual-icon-group i:nth-child(2) {
  font-size: 4rem;
  opacity: 0.9;
  animation-delay: 0.5s;
}

.visual-icon-group i:nth-child(3) {
  animation-delay: 1s;
}

@keyframes pulseIcon {
  0%, 100% { transform: scale(1); opacity: 0.7; }
  50% { transform: scale(1.08); opacity: 1; }
}

.values-section {
  position: relative;
}

.values-section::before {
  content: "";
  position: absolute;
  inset: 1.25rem 2rem;
  border-radius: var(--radius-xl);
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.3), rgba(255, 241, 184, 0)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.06), transparent 42%);
  pointer-events: none;
}

.value-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-lg);
  position: relative;
}

.value-icon-wrap {
  width: 64px;
  height: 64px;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.8), rgba(254, 181, 17, 0.5));
  font-size: 1.6rem;
  color: var(--primary-black);
}

.value-card h5 {
  margin-bottom: 0.5rem;
}

.stats-section {
  position: relative;
}

.stat-card {
  border-radius: var(--radius-lg);
  border: 1px solid rgba(77, 16, 24, 0.08);
  position: relative;
}

.stat-icon-wrap {
  width: 48px;
  height: 48px;
  margin: 0 auto 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.9), rgba(254, 181, 17, 0.6));
  font-size: 1.25rem;
  color: var(--primary-black);
}

.stat-label {
  color: var(--ink-muted);
  letter-spacing: 0.08em;
}

.team-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
  border-radius: var(--radius-lg);
  position: relative;
}

.team-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82));
}

.team-initials {
  font-family: "Playfair Display", Georgia, "Times New Roman", serif;
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--primary-black);
  letter-spacing: 0.08em;
}

.role-badge {
  display: inline-block;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.15);
  color: var(--ink-muted);
  font-size: 0.78rem;
  margin-bottom: 1rem;
}

.team-social {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

.team-social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(77, 16, 24, 0.06);
  color: var(--ink-soft);
  text-decoration: none;
  font-size: 0.9rem;
  transition:
    background var(--transition-base),
    color var(--transition-base),
    transform var(--transition-base);
}

.team-social-link:hover {
  background: var(--primary-black);
  color: var(--gold);
  transform: translateY(-2px);
}

@media (max-width: 991.98px) {
  .about-hero {
    min-height: auto;
    padding-top: 5.5rem;
  }

  .about-visual {
    height: 280px;
  }
}

@media (max-width: 767.98px) {
  .values-section::before {
    inset: 0.75rem;
  }

  .visual-icon-group i {
    font-size: 2rem;
  }

  .visual-icon-group i:nth-child(2) {
    font-size: 2.8rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .about-hero,
  .about-section,
  .section-heading,
  .section-content,
  .card-stagger,
  .about-hero {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }

  .hero-ornament,
  .scroll-indicator i,
  .visual-icon-group i {
    animation: none !important;
  }
}
</style>
