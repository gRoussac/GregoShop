/**
 * Installer Vue entry (built into install-dev/theme/js/install-app.js).
 */

import { createApp } from "vue";
import App from "./App.vue";
import { clearInstallDrafts } from "./composables/useDraft.js";
import { getInstallPayload } from "./lib/installPayload.js";

if (new URLSearchParams(window.location.search).get("restart") === "true") {
  clearInstallDrafts();
}

createApp(App, { bootstrap: getInstallPayload() }).mount("#install-app");
