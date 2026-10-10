import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "node:path";

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: resolve(__dirname, "../install-dev/theme"),
    emptyOutDir: false,
    cssCodeSplit: false,
    assetsDir: "assets",
    rollupOptions: {
      input: resolve(__dirname, "src/main.js"),
      output: {
        format: "iife",
        name: "GregoshopInstallUi",
        inlineDynamicImports: true,
        entryFileNames: "js/install-app.js",
        assetFileNames: "assets/[name][extname]",
      },
    },
  },
});
