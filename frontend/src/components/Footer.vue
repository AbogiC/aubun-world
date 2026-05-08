<template>
  <footer class="luxury-footer text-white py-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 mb-4">
          <h3 class="brand-text mb-3">AUBUN WORLD</h3>
          <p class="opacity-75">
            Crafting timeless elegance through exceptional design and unparalleled quality since
            2010.
          </p>
          <div class="d-flex gap-3 mt-3">
            <a href="https://www.instagram.com/aubunworld?igsh=bWd1N2tzZzEzdXVz" class="text-white"
              ><i class="bi bi-instagram fs-5"></i
            ></a>
            <a href="https://www.tiktok.com/@aubunworld?_r=1&_t=ZS-966Xm9zhcuI" class="text-white"
              ><i class="bi bi-tiktok fs-5"></i
            ></a>
            <a href="https://www.threads.com/@aubunworld" class="text-white"
              ><i class="bi bi-threads fs-5"></i
            ></a>
            <a
              href="https://x.com/aubunworld?s=21&t=7AODALjFtnvF_lIj8QCQ0Q&fbclid=PAb21jcARmnmRleHRuA2FlbQIxMQBzcnRjBmFwcF9pZA81NjcwNjczNDMzNTI0MjcAAad4FmRRVUkBiKQpI78ZOUzpDsNKu9BnRD47YgyVGdT_S-MwevTg4ECmHfH4Fw_aem_XuhvQes5mEef4y23U8nKrA"
              class="text-white"
              ><i class="bi bi-twitter-x fs-5"></i
            ></a>
          </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">Shop</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">New Arrivals</a>
            </li>
            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Dresses</a></li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Outerwear</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Accessories</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">About</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Our Story</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Sustainability</a>
            </li>
            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Careers</a></li>
            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Press</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase mb-3">Help</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Contact Us</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Shipping Info</a>
            </li>
            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Returns</a></li>
            <li class="mb-2">
              <a href="#" class="text-white-50 text-decoration-none">Size Guide</a>
            </li>
          </ul>
        </div>

        <div class="col-lg-2 mb-4">
          <h5 class="text-uppercase mb-3">Contact</h5>
          <p class="opacity-75 small">
            <i class="bi bi-geo-alt me-2"></i>123 Fashion Ave<br />New York, NY 10001
          </p>
          <p class="opacity-75 small"><i class="bi bi-telephone me-2"></i>+1 (555) 123-4567</p>
          <p class="opacity-75 small"><i class="bi bi-envelope me-2"></i>info@aubunworld.com</p>
        </div>
      </div>

      <hr class="my-4 opacity-25" />

      <div class="row">
        <div class="col-md-6 text-center text-md-start">
          <p class="mb-0 small opacity-50">&copy; 2026 Aubun World. All rights reserved.</p>
        </div>
        <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
          <i class="bi bi-credit-card fs-5 me-2 opacity-50"></i>
          <i class="bi bi-paypal fs-5 me-2 opacity-50"></i>
          <i class="bi bi-stripe fs-5 opacity-50"></i>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.luxury-footer {
  position: relative;
  background:
    radial-gradient(circle at top left, rgba(254, 181, 17, 0.16), transparent 35%),
    linear-gradient(180deg, #6c1823 0%, #4d1018 100%);
  border-top: 1px solid rgba(254, 181, 17, 0.16);
}

.brand-text {
  font-family: Georgia, "Times New Roman", serif;
  letter-spacing: 0.2em;
}

.luxury-footer a {
  transition:
    opacity 220ms ease,
    transform 220ms ease;
}

.luxury-footer a:hover {
  opacity: 1 !important;
  transform: translateY(-1px);
}
</style>
