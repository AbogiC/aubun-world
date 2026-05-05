import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import { useAuthStore } from "../stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: HomeView,
    },
    {
      path: "/products",
      name: "products",
      component: () => import("../views/ProductsView.vue"),
    },
    {
      path: "/product/:id",
      name: "product-detail",
      component: () => import("../views/ProductDetail.vue"),
    },
    {
      path: "/cart",
      name: "cart",
      component: () => import("../views/CartView.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/checkout",
      name: "checkout",
      component: () => import("../views/CheckoutView.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/login",
      name: "login",
      component: () => import("../views/LoginView.vue"),
      meta: { guestOnly: true },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("../views/RegisterView.vue"),
      meta: { guestOnly: true },
    },
    {
      path: "/about",
      name: "about",
      component: () => import("../views/AboutView.vue"),
    },
    {
      path: "/dashboard",
      name: "dashboard",
      redirect: "/dashboard/products",
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/dashboard/products",
      name: "product-manage",
      component: () => import("../views/ProductManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/dashboard/shipping",
      name: "shipping-settings",
      component: () => import("../views/ShippingSettingsView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/profile",
      name: "profile",
      component: () => import("../views/ProfileView.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/verify-email",
      name: "verify-email",
      component: () => import("../views/VerifyEmailView.vue"),
    },
  ],
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();
  await authStore.initialize();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: "login",
      query: { redirect: to.fullPath },
    };
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return to.query.redirect || "/cart";
  }

  if (to.meta.roles?.length && !to.meta.roles.includes(authStore.user?.role)) {
    return "/";
  }
});

export default router;
