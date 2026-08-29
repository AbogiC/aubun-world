import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = createRouter({
  history: createWebHistory("/admin/"),
  routes: [
    {
      path: "/",
      name: "admin-dashboard",
      redirect: "/products",
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/login",
      name: "admin-login",
      component: () => import("../views/LoginView.vue"),
      meta: { guestOnly: true, layout: "auth" },
    },
    {
      path: "/products",
      name: "admin-products",
      component: () => import("../views/ProductManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/orders",
      name: "admin-orders",
      component: () => import("../views/OrdersView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/shipping",
      name: "admin-shipping",
      component: () => import("../views/ShippingSettingsView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/vouchers",
      name: "admin-vouchers",
      component: () => import("../views/VoucherManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/news",
      name: "admin-news",
      component: () => import("../views/NewsManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/guidelines",
      name: "admin-guidelines",
      component: () => import("../views/GuidelineManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/stocklist",
      name: "admin-stocklist",
      component: () => import("../views/StocklistManageView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/home-customization",
      name: "admin-home-customization",
      component: () => import("../views/HomeCustomizationView.vue"),
      meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
      path: "/profile",
      name: "admin-profile",
      component: () => import("../views/ProfileView.vue"),
      meta: { requiresAuth: true },
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
      name: "admin-login",
      query: { redirect: to.fullPath },
    };
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return to.query.redirect || "/products";
  }

  if (to.meta.roles?.length && !to.meta.roles.includes(authStore.user?.role)) {
    return "/login";
  }
});

export default router;