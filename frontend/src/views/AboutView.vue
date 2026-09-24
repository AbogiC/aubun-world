<template>
  <div ref="aboutRootRef" class="about-page">
    <section class="about-hero" data-reveal-section>
      <div class="container text-center">
        <div v-if="hasHero" class="hero-content">
          <p v-if="heroKicker" class="section-kicker" style="color: rgba(254, 181, 17, 0.7);">{{ heroKicker }}</p>
          <div class="hero-divider"></div>
          <h1 v-if="heroTitle" class="display-3 mb-4" style="color: var(--gold-light);">{{ heroTitle }}</h1>
          <p v-if="heroSubtitle" class="lead opacity-75 mb-0" style="color: var(--white);">{{ heroSubtitle }}</p>
          <div class="scroll-indicator">
            <span>Discover More</span>
            <i class="bi bi-chevron-down"></i>
          </div>
        </div>
        <div v-else class="hero-content hero-coming">
          <p class="section-kicker hero-coming-kicker">The Atelier Is Preparing</p>
          <div class="hero-divider"></div>
          <h1 class="display-3 mb-3 hero-coming-title"><span>Coming</span> <em>Soon</em></h1>
          <p class="lead hero-coming-copy">Our story is being crafted with care. An elegant introduction to Aubun World is on its way — please check back shortly.</p>
          <div class="hero-coming-meta">
            <span><i class="bi bi-gem"></i>Heritage</span>
            <span class="dot" aria-hidden="true"></span>
            <span><i class="bi bi-stars"></i>Craftsmanship</span>
            <span class="dot" aria-hidden="true"></span>
            <span><i class="bi bi-heart"></i>Passion</span>
          </div>
        </div>
      </div>
      <div class="hero-ornament hero-ornament-1"></div>
      <div class="hero-ornament hero-ornament-2"></div>
    </section>

    <section class="about-section py-5" data-reveal-section>
      <div class="container">
        <div v-if="hasMission" class="row align-items-center g-5">
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
              <p v-if="missionKicker" class="section-kicker">{{ missionKicker }}</p>
              <h2 v-if="missionTitle" class="mb-4">{{ missionTitle }}</h2>
            </div>
            <div v-if="hasMissionBody" class="section-content">
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
        <div v-else class="about-coming-card">
          <div class="about-coming-inner">
            <p class="about-coming-kicker">Our Purpose · In Preparation</p>
            <h3 class="about-coming-title"><span>Coming</span> <em>Soon</em></h3>
            <div class="about-coming-divider" aria-hidden="true">
              <span class="line"></span>
              <span class="diamond"></span>
              <span class="line"></span>
            </div>
            <p class="about-coming-copy">
              Our mission story is being refined. A thoughtful look at our purpose,
              craft and promises will appear here soon.
            </p>
            <div class="about-coming-meta">
              <span><i class="bi bi-compass"></i>Purpose</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-award"></i>Craft</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-globe2"></i>Care</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-section values-section py-5" data-reveal-section>
      <div class="container">
        <div v-if="hasValuesHeader" class="section-title section-heading">
          <h2 v-if="valuesTitle">{{ valuesTitle }}</h2>
          <p v-if="valuesSubtitle" class="text-muted">{{ valuesSubtitle }}</p>
        </div>
        <div v-if="hasValues" class="row g-4 section-content">
          <div v-for="(value, index) in displayValues" :key="value.title + '-' + index" class="col-md-6 col-lg-3">
            <div
              class="value-card surface text-center p-4 hover-lift card-stagger"
              :style="{ transitionDelay: `${120 + index * 100}ms` }"
            >
              <div class="value-icon-wrap">
                <i :class="value.icon || 'bi bi-star-fill'"></i>
              </div>
              <h5>{{ value.title }}</h5>
              <p v-if="value.description" class="mb-0 text-muted small">{{ value.description }}</p>
            </div>
          </div>
        </div>
        <div v-else class="about-coming-card">
          <div class="about-coming-inner">
            <p class="about-coming-kicker">Our Values · In Preparation</p>
            <h3 class="about-coming-title"><span>Coming</span> <em>Soon</em></h3>
            <div class="about-coming-divider" aria-hidden="true">
              <span class="line"></span>
              <span class="diamond"></span>
              <span class="line"></span>
            </div>
            <p class="about-coming-copy">
              The principles that guide every creation are being gathered.
              Our values will be unveiled here shortly.
            </p>
            <div class="about-coming-meta">
              <span><i class="bi bi-scissors"></i>Craft</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-stars"></i>Vision</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-clock-history"></i>Heritage</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-section py-5" data-reveal-section>
      <div class="container">
        <div v-if="hasTeamHeader" class="section-title section-heading">
          <h2 v-if="teamTitle">{{ teamTitle }}</h2>
          <p v-if="teamSubtitle" class="text-muted">{{ teamSubtitle }}</p>
        </div>
        <div v-if="hasTeam" class="row g-4 section-content">
          <div
            v-for="(member, index) in displayTeam"
            :key="member.name + '-' + index"
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
              <p v-if="member.role" class="role-badge">{{ member.role }}</p>
              <div class="team-social">
                <a href="#" class="team-social-link" @click.prevent><i class="bi bi-linkedin"></i></a>
                <a href="#" class="team-social-link" @click.prevent><i class="bi bi-envelope-fill"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="about-coming-card">
          <div class="about-coming-inner">
            <p class="about-coming-kicker">Our Team · In Preparation</p>
            <h3 class="about-coming-title"><span>Coming</span> <em>Soon</em></h3>
            <div class="about-coming-divider" aria-hidden="true">
              <span class="line"></span>
              <span class="diamond"></span>
              <span class="line"></span>
            </div>
            <p class="about-coming-copy">
              Meet the makers behind the brand — our team introductions
              are being prepared and will appear here soon.
            </p>
            <div class="about-coming-meta">
              <span><i class="bi bi-people"></i>Atelier</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-palette"></i>Designers</span>
              <span class="dot" aria-hidden="true"></span>
              <span><i class="bi bi-bag-heart"></i>Care</span>
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

const aboutSettings = ref(null);
const valuesList = ref([]);
const teamList = ref([]);

const hasText = (value) => typeof value === "string" && value.trim() !== "";

const heroKicker = computed(() => (aboutSettings.value?.heroKicker || "").trim());
const heroTitle = computed(() => (aboutSettings.value?.heroTitle || "").trim());
const heroSubtitle = computed(() => (aboutSettings.value?.heroSubtitle || "").trim());
const missionKicker = computed(() => (aboutSettings.value?.missionKicker || "").trim());
const missionTitle = computed(() => (aboutSettings.value?.missionTitle || "").trim());
const missionLead = computed(() => (aboutSettings.value?.missionLead || "").trim());
const missionBody1 = computed(() => (aboutSettings.value?.missionBody1 || "").trim());
const missionBody2 = computed(() => (aboutSettings.value?.missionBody2 || "").trim());
const missionImageUrl = computed(() => (aboutSettings.value?.missionImageUrl || "").trim());
const valuesTitle = computed(() => (aboutSettings.value?.valuesTitle || "").trim());
const valuesSubtitle = computed(() => (aboutSettings.value?.valuesSubtitle || "").trim());
const teamTitle = computed(() => (aboutSettings.value?.teamTitle || "").trim());
const teamSubtitle = computed(() => (aboutSettings.value?.teamSubtitle || "").trim());

const hasHero = computed(() => hasText(heroKicker.value) || hasText(heroTitle.value) || hasText(heroSubtitle.value));
const hasMissionBody = computed(() => hasText(missionLead.value) || hasText(missionBody1.value) || hasText(missionBody2.value));
const hasMission = computed(
  () =>
    hasText(missionKicker.value) ||
    hasText(missionTitle.value) ||
    hasMissionBody.value ||
    hasText(missionImageUrl.value),
);

const displayValues = computed(() =>
  (valuesList.value || []).filter((v) => v && v.isActive !== false && hasText(v.title)),
);
const displayTeam = computed(() =>
  (teamList.value || []).filter((m) => m && m.isActive !== false && hasText(m.name)),
);

const hasValues = computed(() => displayValues.value.length > 0);
const hasValuesHeader = computed(() => hasText(valuesTitle.value) || hasText(valuesSubtitle.value));
const hasTeam = computed(() => displayTeam.value.length > 0);
const hasTeamHeader = computed(() => hasText(teamTitle.value) || hasText(teamSubtitle.value));

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

/* ---------- Coming Soon (luxury, per section) ---------- */
.hero-coming {
  max-width: 44rem;
  margin: 0 auto;
}

.hero-coming-kicker {
  color: rgba(254, 181, 17, 0.9) !important;
  font-size: 0.72rem;
  letter-spacing: 0.38em;
  text-transform: uppercase;
  font-weight: 600;
}

.hero-coming-title {
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--gold-light);
  font-size: clamp(2.6rem, 7vw, 4.6rem);
  line-height: 1.05;
}

.hero-coming-title span {
  font-weight: 400;
}

.hero-coming-title em {
  font-style: normal;
  background: linear-gradient(180deg, #fff6c8, var(--gold) 65%, #b57e06);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  font-weight: 700;
}

.hero-coming-copy {
  max-width: 34rem;
  margin: 0 auto;
  color: rgba(255, 241, 184, 0.82);
  font-size: clamp(0.95rem, 1.6vw, 1.08rem);
  line-height: 1.8;
}

.hero-coming-meta {
  margin-top: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.9rem 1.1rem;
  font-size: 0.74rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(255, 241, 184, 0.66);
}

.hero-coming-meta span {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.hero-coming-meta i {
  color: var(--gold);
  font-size: 0.95rem;
}

.hero-coming-meta .dot {
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.5);
}

.about-coming-card {
  position: relative;
  max-width: 58rem;
  margin: 0 auto;
  border-radius: var(--radius-xl);
  padding: 1px;
  background: linear-gradient(135deg, rgba(254, 181, 17, 0.9), rgba(254, 181, 17, 0.15) 30%, rgba(254, 181, 17, 0.15) 70%, rgba(254, 181, 17, 0.9));
  box-shadow: var(--shadow-xl);
  overflow: hidden;
}

.about-coming-card::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 50% 0%, rgba(254, 181, 17, 0.28), transparent 55%),
    radial-gradient(circle at 88% 90%, rgba(254, 181, 17, 0.16), transparent 50%),
    radial-gradient(circle at 8% 90%, rgba(255, 241, 184, 0.12), transparent 50%);
  pointer-events: none;
}

.about-coming-inner {
  position: relative;
  border-radius: calc(var(--radius-xl) - 1px);
  background:
    linear-gradient(180deg, rgba(108, 24, 35, 0.98), rgba(77, 16, 24, 0.99) 55%, rgba(46, 8, 13, 0.99));
  color: var(--gold-light);
  text-align: center;
  padding: clamp(2.5rem, 6vw, 4rem) clamp(1.5rem, 5vw, 3.5rem);
  overflow: hidden;
}

.about-coming-inner::before,
.about-coming-inner::after {
  content: "";
  position: absolute;
  width: 14rem;
  height: 14rem;
  border-radius: 999px;
  border: 1px solid rgba(254, 181, 17, 0.18);
  pointer-events: none;
}

.about-coming-inner::before {
  top: -7rem;
  left: -7rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.14), transparent 70%);
}

.about-coming-inner::after {
  bottom: -7rem;
  right: -7rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.14), transparent 70%);
}

.about-coming-kicker {
  font-size: 0.7rem;
  letter-spacing: 0.34em;
  text-transform: uppercase;
  color: rgba(254, 181, 17, 0.9);
  font-weight: 600;
  margin-bottom: 1rem;
}

.about-coming-title {
  font-size: clamp(2.2rem, 6vw, 3.8rem);
  line-height: 1.05;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.4rem;
  color: var(--gold-light);
}

.about-coming-title span {
  font-weight: 400;
}

.about-coming-title em {
  font-style: normal;
  background: linear-gradient(180deg, #fff6c8, var(--gold) 65%, #b57e06);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  font-weight: 700;
}

.about-coming-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.85rem;
  margin: 1.25rem auto 1.15rem;
  max-width: 20rem;
}

.about-coming-divider .line {
  height: 1px;
  flex: 1;
  background: linear-gradient(90deg, transparent, rgba(254, 181, 17, 0.8), transparent);
}

.about-coming-divider .diamond {
  width: 0.6rem;
  height: 0.6rem;
  transform: rotate(45deg);
  background: var(--gold);
  box-shadow: 0 0 14px rgba(254, 181, 17, 0.9);
  flex-shrink: 0;
}

.about-coming-copy {
  max-width: 32rem;
  margin: 0 auto 1.5rem;
  color: rgba(255, 241, 184, 0.82);
  font-size: clamp(0.92rem, 1.5vw, 1.02rem);
  line-height: 1.8;
}

.about-coming-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.9rem 1.1rem;
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(255, 241, 184, 0.66);
}

.about-coming-meta span {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.about-coming-meta i {
  color: var(--gold);
  font-size: 0.9rem;
}

.about-coming-meta .dot {
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.5);
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

  .about-coming-card {
    border-radius: var(--radius-lg);
  }

  .about-coming-inner {
    border-radius: calc(var(--radius-lg) - 1px);
  }

  .about-coming-kicker,
  .hero-coming-kicker {
    letter-spacing: 0.22em;
  }

  .about-coming-title,
  .hero-coming-title {
    letter-spacing: 0.05em;
  }

  .about-coming-meta,
  .hero-coming-meta {
    flex-direction: column;
    gap: 0.6rem;
  }

  .about-coming-meta .dot,
  .hero-coming-meta .dot {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .about-hero,
  .about-section,
  .section-heading,
  .section-content,
  .card-stagger,
  .about-hero,
  .about-coming-card,
  .about-coming-inner {
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
