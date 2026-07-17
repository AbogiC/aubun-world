<template>
  <section class="hero-section position-relative">
    <div class="container-fluid px-0">
      <div class="row g-0">
        <div class="col-lg-6 hero-copy position-relative">
          <div class="hero-content p-5">
            <p class="section-kicker mb-3" style="color: rgba(254, 181, 17, 0.7);">New Collection 2024</p>
            <h1 class="display-3 fw-bold mb-4 reveal">Autumn Winter<br />Elegance</h1>
            <p class="lead mb-5 opacity-75 reveal stagger-1">
              Discover refined silhouettes, immaculate tailoring, and the quiet confidence of true
              luxury.
            </p>
            <div class="d-flex flex-wrap gap-3 reveal stagger-2">
              <router-link to="/products" class="btn btn-luxury btn-lg px-5 text-uppercase">
                Shop Now
              </router-link>
              <router-link to="/about" class="btn btn-outline-luxury btn-lg text-uppercase">
                Our Story
              </router-link>
            </div>
            <div class="hero-metrics mt-5 reveal stagger-3">
              <div>
                <strong>250+</strong>
                <span>exclusive designs</span>
              </div>
              <div>
                <strong>14 yrs</strong>
                <span>craft heritage</span>
              </div>
              <div>
                <strong>100k+</strong>
                <span>loyal clients</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 hero-visual">
          <div class="hero-image">
            <div class="hero-orb orb-one"></div>
            <div class="hero-orb orb-two"></div>
            <div class="hero-orb orb-three"></div>
            <div class="hero-card surface subtle-glow">
              <div class="hero-card-top">
                <span>Signature edit</span>
                <span>01</span>
              </div>
              <div class="hero-card-figure">
                <i class="bi bi-award"></i>
              </div>
              <p>Precision tailoring, sculpted fabrics, and monochrome restraint.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  overflow: hidden;
}

.hero-content {
  padding: clamp(4rem, 8vw, 7rem);
  min-height: 720px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background:
    linear-gradient(135deg, rgba(77, 16, 24, 0.98), rgba(108, 24, 35, 0.9)),
    radial-gradient(circle at top right, rgba(254, 181, 17, 0.16), transparent 35%);
  color: var(--white);
}

.hero-copy::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(254, 181, 17, 0.08), transparent 36%);
  pointer-events: none;
}

.hero-visual {
  position: relative;
  min-height: 720px;
  background:
    radial-gradient(circle at 30% 30%, rgba(255, 241, 184, 0.98), rgba(254, 181, 17, 0.86) 34%, rgba(77, 16, 24, 0.22) 100%);
}

.hero-image {
  position: relative;
  height: 100%;
  min-height: 720px;
  overflow: hidden;
}

.hero-orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(10px);
  animation: float 8s ease-in-out infinite;
}

.orb-one {
  width: 280px;
  height: 280px;
  background: radial-gradient(circle, rgba(255, 241, 184, 0.95), rgba(77, 16, 24, 0.16));
  top: 10%;
  right: 10%;
}

.orb-two {
  width: 180px;
  height: 180px;
  background: radial-gradient(circle, rgba(77, 16, 24, 0.22), transparent 70%);
  left: 12%;
  bottom: 14%;
  animation-delay: -2.5s;
}

.orb-three {
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(254, 181, 17, 0.3), transparent 70%);
  top: 55%;
  right: 25%;
  animation-delay: -5s;
}

.hero-card {
  position: absolute;
  left: 10%;
  right: 10%;
  bottom: 12%;
  padding: 1.5rem;
  background: rgba(255, 248, 228, 0.85);
  border: 1px solid rgba(77, 16, 24, 0.12);
  border-radius: var(--radius-lg);
}

.hero-card-top {
  display: flex;
  justify-content: space-between;
  font-size: 0.72rem;
  letter-spacing: 0.26em;
  text-transform: uppercase;
  color: rgba(77, 16, 24, 0.72);
  margin-bottom: 1.25rem;
}

.hero-card-figure {
  width: 88px;
  height: 88px;
  border-radius: 999px;
  background: linear-gradient(135deg, #4d1018, #6c1823);
  display: grid;
  place-items: center;
  margin-bottom: 1.25rem;
  color: #feb511;
  font-size: 2rem;
}

.hero-card p {
  margin-bottom: 0;
  color: var(--primary-black);
  font-size: 1rem;
}

.hero-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.hero-metrics div {
  padding-top: 1rem;
  border-top: 1px solid rgba(254, 181, 17, 0.28);
}

.hero-metrics strong {
  display: block;
  font-family:
    "Playfair Display",
    Georgia,
    serif;
  font-size: 1.4rem;
  margin-bottom: 0.25rem;
}

.hero-metrics span {
  display: block;
  font-size: 0.75rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: rgba(254, 181, 17, 0.76);
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-18px); }
}

@media (max-width: 991.98px) {
  .hero-content,
  .hero-visual,
  .hero-image {
    min-height: auto;
  }

  .hero-content {
    padding-bottom: 4rem;
  }

  .hero-visual {
    min-height: 520px;
  }
}
</style>