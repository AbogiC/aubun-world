<template>
  <div class="about-page">
    <section class="about-hero text-white py-5">
      <div class="container text-center py-5">
        <p class="section-kicker text-white-50">Our Story</p>
        <h1 class="display-3 mb-4">Our Story</h1>
        <p class="lead opacity-75">Crafting elegance since 2010</p>
      </div>
    </section>

    <section class="py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6 mb-4">
            <div
              class="about-visual surface d-flex align-items-center justify-content-center subtle-glow"
              style="height: 400px"
            >
              <i class="bi bi-building text-muted" style="font-size: 5rem"></i>
            </div>
          </div>
          <div class="col-lg-6">
            <h2 class="mb-4">Our Mission</h2>
            <p class="lead mb-3">
              To create timeless pieces that transcend trends and become cherished wardrobe staples.
            </p>
            <p>
              At Noir Elegance, we believe that true luxury lies in the details. Every stitch, every
              fabric choice, and every design element is carefully considered to create garments
              that not only look exceptional but feel extraordinary to wear.
            </p>
            <p>
              Our commitment to quality craftsmanship and sustainable practices ensures that each
              piece is not just a purchase, but an investment in enduring style.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-light">
      <div ref="statsSectionRef" class="container">
        <div class="row text-center">
          <div v-for="(stat, index) in stats" :key="stat.label" class="col-md-3 mb-3">
            <h3 class="display-4 fw-bold mb-2">{{ displayedStats[index] }}</h3>
            <p class="text-muted text-uppercase">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5">
      <div class="container">
        <div class="section-title mb-5">
          <h2>Our Team</h2>
        </div>
        <div class="row">
          <div v-for="member in team" :key="member.name" class="col-md-4 mb-4">
            <div class="text-center team-card surface p-4 hover-lift">
              <div
                class="team-avatar mx-auto mb-3 d-flex align-items-center justify-content-center"
                style="width: 150px; height: 150px"
              >
                <i class="bi bi-person-circle text-muted" style="font-size: 4rem"></i>
              </div>
              <h5>{{ member.name }}</h5>
              <p class="text-muted">{{ member.role }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

const stats = [
  { value: "14+", label: "Years of Excellence" },
  { value: "50+", label: "Countries Worldwide" },
  { value: "100k+", label: "Happy Clients" },
  { value: "250+", label: "Exclusive Designs" },
];

const statsSectionRef = ref(null);
const displayedStats = ref(stats.map(() => "0"));
let statsObserver;
let animationFrameId;
let hasAnimatedStats = false;

const team = [
  { name: "Sophia Laurent", role: "Founder & Creative Director" },
  { name: "Marcus Chen", role: "Head of Design" },
  { name: "Isabella Rossi", role: "Marketing Director" },
  { name: "James Whitmore", role: "Production Manager" },
  { name: "Olivia Park", role: "Sustainability Officer" },
  { name: "David Thompson", role: "Customer Experience" },
];

const parseStatValue = (value) => {
  const match = value.match(/^(\d+)([a-zA-Z]*)(\+?)$/);

  if (!match) {
    return {
      numericValue: Number.parseInt(value, 10) || 0,
      suffix: "",
      plus: "",
    };
  }

  return {
    numericValue: Number.parseInt(match[1], 10),
    suffix: match[2] || "",
    plus: match[3] || "",
  };
};

const formatAnimatedStat = (currentValue, stat) => {
  const { suffix, plus } = parseStatValue(stat.value);
  return `${currentValue}${suffix}${plus}`;
};

const startStatsAnimation = () => {
  if (hasAnimatedStats) {
    return;
  }

  hasAnimatedStats = true;

  const duration = 1800;
  const startTime = performance.now();

  const tick = (currentTime) => {
    const progress = Math.min((currentTime - startTime) / duration, 1);
    const easedProgress = 1 - Math.pow(1 - progress, 3);

    displayedStats.value = stats.map((stat) => {
      const { numericValue } = parseStatValue(stat.value);
      const currentValue = Math.round(numericValue * easedProgress);
      return formatAnimatedStat(currentValue, stat);
    });

    if (progress < 1) {
      animationFrameId = window.requestAnimationFrame(tick);
      return;
    }

    displayedStats.value = stats.map((stat) => stat.value);
  };

  animationFrameId = window.requestAnimationFrame(tick);
};

onMounted(() => {
  statsObserver = new IntersectionObserver(
    (entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) {
        return;
      }

      startStatsAnimation();
      statsObserver?.disconnect();
    },
    {
      threshold: 0.35,
    },
  );

  if (statsSectionRef.value) {
    statsObserver.observe(statsSectionRef.value);
  }
});

onBeforeUnmount(() => {
  statsObserver?.disconnect();

  if (animationFrameId) {
    window.cancelAnimationFrame(animationFrameId);
  }
});
</script>

<style scoped>
.about-hero {
  background:
    linear-gradient(180deg, rgba(77, 16, 24, 0.98), rgba(108, 24, 35, 0.94)),
    radial-gradient(circle at top, rgba(254, 181, 17, 0.12), transparent 45%);
}

.about-visual,
.team-avatar {
  background:
    linear-gradient(145deg, rgba(255, 241, 184, 0.94), rgba(254, 181, 17, 0.82)),
    radial-gradient(circle at top right, rgba(77, 16, 24, 0.1), transparent 35%);
}

.team-card {
  border: 1px solid rgba(77, 16, 24, 0.08);
}
</style>
