import { createApp } from "vue";
import { createPinia } from "pinia";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "./style.css";

// Defer Bootstrap JS — not needed for first paint (saves render-blocking + main-thread work)
import("bootstrap/dist/js/bootstrap.bundle.min.js");

import App from "./App.vue";
import router from "./router";

// document.addEventListener("contextmenu", function (e) {
//   e.preventDefault();
// });

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount("#app");
