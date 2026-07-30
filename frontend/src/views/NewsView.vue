<template>
  <div ref="newsRootRef" class="news-page">
    <section class="news-hero" data-reveal-section>
      <div class="container text-center">
        <div class="hero-content">
          <p class="section-kicker" style="color: rgba(254, 181, 17, 0.7);">Journal</p>
          <div class="hero-divider"></div>
          <h1 class="display-3 mb-4" style="color: var(--gold-light);">Latest News</h1>
          <p class="lead opacity-75 mb-0" style="color: var(--white);">Stories, collections, and insights from Aubun World</p>
        </div>
      </div>
      <div class="hero-ornament hero-ornament-1"></div>
      <div class="hero-ornament hero-ornament-2"></div>
    </section>

    <section class="news-section py-5" data-reveal-section>
      <div class="container">
        <div class="filter-bar section-content">
          <button
            v-for="cat in categories"
            :key="cat"
            class="filter-chip"
            :class="{ active: activeCategory === cat }"
            @click="activeCategory = cat"
          >
            {{ cat }}
          </button>
        </div>
      </div>
    </section>

    <div v-if="regionDetected" class="region-bar">
      <div class="container d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-geo-alt-fill"></i>
        <span>Showing news for <strong>{{ userRegion }}</strong></span>
        <button class="region-bar-clear btn btn-sm" @click="clearRegion">Show all</button>
      </div>
    </div>

    <section class="news-section pb-5" data-reveal-section v-if="loading">
      <div class="container">
        <div class="text-center py-5">
          <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </section>

    <section class="news-section pb-5" data-reveal-section v-else-if="!articles.length">
      <div class="container">
        <div class="text-center py-5">
          <i class="bi bi-newspaper text-muted" style="font-size: 2.5rem;"></i>
          <p class="text-muted mt-2 mb-0">No articles yet. Check back soon for updates.</p>
        </div>
      </div>
    </section>

    <section class="news-section pb-5" data-reveal-section v-else-if="filteredArticles.length">
      <div class="container">
        <div class="featured-article surface" v-if="filteredArticles[0]">
          <div class="row g-0 align-items-stretch">
            <div class="col-lg-7">
              <div
                class="featured-image d-flex align-items-center justify-content-center"
                :style="{ background: filteredArticles[0].gradient }"
              >
                <i :class="filteredArticles[0].icon" class="featured-image-icon"></i>
              </div>
            </div>
            <div class="col-lg-5 d-flex">
              <div class="featured-body p-4 p-xl-5 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                  <span class="article-category">{{ filteredArticles[0].category }}</span>
                  <span class="article-meta">{{ formatDate(filteredArticles[0].publishedAt || filteredArticles[0].createdAt) }}</span>
                  <span class="article-meta">{{ filteredArticles[0].readTime }}</span>
                </div>
                <h3 class="mb-3">{{ filteredArticles[0].title }}</h3>
                <p class="mb-3">{{ filteredArticles[0].excerpt }}</p>
                <div class="d-flex align-items-center gap-2 mt-auto">
                  <div class="author-avatar-sm">{{ getInitials(filteredArticles[0].author) }}</div>
                  <span class="article-meta">By {{ filteredArticles[0].author }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="news-section pb-5" data-reveal-section v-if="remainingArticles.length">
      <div class="container">
        <div class="row g-4 section-content">
          <div
            v-for="(article, index) in remainingArticles"
            :key="article.id"
            class="col-md-6 col-lg-4"
          >
            <div
              class="article-card surface h-100 hover-lift card-stagger"
              :style="{ transitionDelay: `${120 + index * 80}ms` }"
            >
              <div
                class="article-card-image d-flex align-items-center justify-content-center"
                :style="{ background: article.gradient }"
              >
                <i :class="article.icon"></i>
              </div>
              <div class="article-card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                  <span class="article-category">{{ article.category }}</span>
                  <span class="article-meta">{{ formatDate(article.publishedAt || article.createdAt) }}</span>
                  <span class="article-meta">{{ article.readTime }}</span>
                </div>
                <h5 class="mb-2">{{ article.title }}</h5>
                <p class="small mb-3">{{ article.excerpt }}</p>
                <div class="d-flex align-items-center gap-2">
                  <div class="author-avatar-xs">{{ getInitials(article.author) }}</div>
                  <span class="article-meta small">By {{ article.author }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="news-section newsletter-cta py-5" data-reveal-section>
      <div class="container">
        <div class="cta-card surface-elevated text-center p-5">
          <div class="cta-icon-wrap">
            <i class="bi bi-envelope-paper-fill"></i>
          </div>
          <h3 class="mb-2">Stay Inspired</h3>
          <p class="text-muted mb-4 max-w-sm mx-auto">
            Subscribe to receive exclusive updates, collection previews, and brand stories straight to your inbox.
          </p>
          <form class="cta-form mx-auto" @submit.prevent="handleSubscribe">
            <div class="cta-input-group">
              <input
                v-model="email"
                type="email"
                class="form-control"
                placeholder="Enter your email"
                required
              />
              <button type="submit" class="btn btn-luxury" :disabled="subscribing">
                {{ subscribing ? "Sending…" : "Subscribe" }}
              </button>
            </div>
            <p v-if="subscribeMsg" class="small mt-2 mb-0" :class="subscribeSuccess ? 'text-success' : 'text-danger'">
              {{ subscribeMsg }}
            </p>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { api } from "../lib/api";

const newsRootRef = ref(null);
const activeCategory = ref("All");
const email = ref("");
const subscribing = ref(false);
const subscribeMsg = ref("");
const subscribeSuccess = ref(false);
let sectionObserver;

const categories = ["All", "Collection", "Behind the Scenes", "Sustainability", "Press", "Events"];
const articles = ref([]);
const loading = ref(true);
const userRegion = ref("");
const regionDetected = ref(false);

const filteredArticles = computed(() => {
  if (activeCategory.value === "All") return articles.value;
  return articles.value.filter((a) => a.category === activeCategory.value);
});

const remainingArticles = computed(() => filteredArticles.value.slice(1));

const getInitials = (name) => {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("");
};

const formatDate = (dateStr) => {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
};

const handleSubscribe = async () => {
  if (!email.value) return;
  subscribing.value = true;
  subscribeMsg.value = "";
  subscribeSuccess.value = false;
  try {
    await api.post("/auth/newsletter/subscribe", { email: email.value });
    subscribeMsg.value = "Thank you for subscribing!";
    subscribeSuccess.value = true;
    email.value = "";
  } catch {
    subscribeMsg.value = "Something went wrong. Please try again.";
  } finally {
    subscribing.value = false;
  }
};

const detectRegion = () => {
  return new Promise((resolve) => {
    if (!navigator.geolocation) { resolve(""); return; }
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`,
            { headers: { "User-Agent": "AubunWorld/1.0" } },
          );
          const data = await res.json();
          resolve(data?.address?.country || "");
        } catch {
          resolve("");
        }
      },
      () => resolve(""),
      { timeout: 5000 },
    );
  });
};

const fetchNews = async (region) => {
  const params = region ? `/news?region=${encodeURIComponent(region)}` : "/news";
  try {
    const data = await api.get(params);
    articles.value = data.articles || [];
  } catch {
    articles.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  const detected = await detectRegion();
  if (detected) {
    userRegion.value = detected;
    regionDetected.value = true;
  }
  await fetchNews(detected);

  await nextTick();

  const sections = newsRootRef.value?.querySelectorAll("[data-reveal-section]");
  if (!sections?.length) return;
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
});

const clearRegion = async () => {
  userRegion.value = "";
  regionDetected.value = false;
  loading.value = true;
  articles.value = [];
  await fetchNews("");
  await nextTick();
  const sections = newsRootRef.value?.querySelectorAll("[data-reveal-section]");
  sections?.forEach((section) => {
    section.classList.add("is-visible");
    sectionObserver?.unobserve(section);
  });
};

onBeforeUnmount(() => {
  sectionObserver?.disconnect();
});
</script>

<style scoped>
.news-page {
  background:
    radial-gradient(circle at top center, rgba(254, 181, 17, 0.2), transparent 28%),
    linear-gradient(180deg, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.62));
}

.news-hero,
.news-section {
  position: relative;
  opacity: 0;
  transform: translateY(44px);
  filter: blur(10px);
  transition:
    opacity 760ms cubic-bezier(0.22, 1, 0.36, 1),
    transform 760ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 760ms ease;
}

.news-hero.is-visible,
.news-section.is-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.section-content,
.card-stagger {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 620ms ease,
    transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.news-section.is-visible .section-content {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 120ms;
}

.news-section.is-visible .card-stagger {
  opacity: 1;
  transform: translateY(0);
}

.news-hero {
  min-height: 70vh;
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

.news-hero::before,
.news-hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

.news-hero::before {
  width: 22rem;
  height: 22rem;
  top: -7rem;
  right: -5rem;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.25), transparent 72%);
}

.news-hero::after {
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

.region-bar {
  background: rgba(77, 16, 24, 0.06);
  border-top: 1px solid rgba(77, 16, 24, 0.08);
  border-bottom: 1px solid rgba(77, 16, 24, 0.08);
  padding: 0.6rem 0;
  font-size: 0.85rem;
  color: var(--ink-soft);
}

.region-bar-clear {
  background: transparent;
  border: 1px solid rgba(77, 16, 24, 0.15);
  border-radius: 999px;
  padding: 0.1rem 0.7rem;
  font-size: 0.72rem;
  color: var(--ink-muted);
  cursor: pointer;
  transition: all var(--transition-base);
}

.region-bar-clear:hover {
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  justify-content: center;
  padding: 0.5rem 0;
}

.filter-chip {
  padding: 0.5rem 1.25rem;
  border-radius: 999px;
  border: 1px solid rgba(77, 16, 24, 0.14);
  background: rgba(255, 248, 228, 0.6);
  color: var(--ink-soft);
  font-size: 0.8rem;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition:
    background var(--transition-base),
    color var(--transition-base),
    border-color var(--transition-base),
    transform var(--transition-base);
  backdrop-filter: blur(8px);
}

.filter-chip:hover {
  background: rgba(255, 248, 228, 0.9);
  border-color: rgba(77, 16, 24, 0.3);
  transform: translateY(-1px);
}

.filter-chip.active {
  background: var(--primary-black);
  color: var(--gold);
  border-color: var(--primary-black);
}

.featured-article {
  border-radius: var(--radius-xl);
  overflow: hidden;
  border: 1px solid rgba(77, 16, 24, 0.1);
}

.featured-image {
  min-height: 340px;
  height: 100%;
  position: relative;
}

.featured-image-icon {
  font-size: 4rem;
  color: rgba(255, 248, 228, 0.6);
  animation: pulseIcon 3s ease-in-out infinite;
}

@keyframes pulseIcon {
  0%, 100% { transform: scale(1); opacity: 0.6; }
  50% { transform: scale(1.08); opacity: 0.9; }
}

.featured-body h3 {
  font-size: clamp(1.4rem, 2.5vw, 1.9rem);
}

.featured-body p:last-of-type {
  color: var(--ink-soft);
  line-height: 1.7;
}

.article-category {
  display: inline-block;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  background: rgba(254, 181, 17, 0.15);
  color: var(--ink-muted);
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  font-weight: 500;
}

.article-meta {
  color: var(--ink-soft);
  font-size: 0.82rem;
}

.author-avatar-sm {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Playfair Display", Georgia, "Times New Roman", serif;
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--primary-black);
}

.author-avatar-xs {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Playfair Display", Georgia, "Times New Roman", serif;
  font-size: 0.6rem;
  font-weight: 600;
  color: var(--primary-black);
  flex-shrink: 0;
}

.article-card {
  border-radius: var(--radius-xl);
  border: 1px solid rgba(77, 16, 24, 0.08);
  overflow: hidden;
  transition:
    transform 240ms ease,
    box-shadow 240ms ease,
    border-color 240ms ease;
}

.article-card:hover {
  transform: translateY(-8px);
  border-color: rgba(77, 16, 24, 0.22);
  box-shadow: 0 30px 60px rgba(77, 16, 24, 0.15);
}

.article-card-image {
  height: 200px;
  position: relative;
}

.article-card-image i {
  font-size: 2.5rem;
  color: rgba(255, 248, 228, 0.5);
}

.article-card-body h5 {
  font-size: clamp(1.1rem, 1.6vw, 1.3rem);
}

.article-card-body p {
  color: var(--ink-soft);
  line-height: 1.7;
}

.newsletter-cta {
  position: relative;
}

.newsletter-cta::before {
  content: "";
  position: absolute;
  inset: 1.25rem 2rem;
  border-radius: var(--radius-xl);
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.3), rgba(255, 241, 184, 0)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.06), transparent 42%);
  pointer-events: none;
}

.cta-card {
  max-width: 640px;
  margin: 0 auto;
  border-radius: var(--radius-xl);
  position: relative;
}

.cta-icon-wrap {
  width: 64px;
  height: 64px;
  margin: 0 auto 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  background: linear-gradient(145deg, rgba(255, 241, 184, 0.9), rgba(254, 181, 17, 0.6));
  font-size: 1.5rem;
  color: var(--primary-black);
}

.cta-form {
  max-width: 440px;
}

.cta-input-group {
  display: flex;
  gap: 0.5rem;
}

.cta-input-group .form-control {
  flex: 1;
}

.cta-input-group .btn {
  flex-shrink: 0;
  white-space: nowrap;
}

.max-w-sm {
  max-width: 28rem;
}

@media (max-width: 991.98px) {
  .news-hero {
    min-height: auto;
    padding-top: 5.5rem;
  }

  .featured-image {
    min-height: 240px;
  }
}

@media (max-width: 767.98px) {
  .newsletter-cta::before {
    inset: 0.75rem;
  }

  .cta-input-group {
    flex-direction: column;
  }

  .cta-input-group .btn {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .news-hero,
  .news-section,
  .section-content,
  .card-stagger {
    animation: none !important;
    transition: none !important;
    transform: none !important;
    filter: none !important;
    opacity: 1 !important;
  }

  .hero-ornament,
  .featured-image-icon {
    animation: none !important;
  }
}
</style>
