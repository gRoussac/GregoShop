<script setup>
import { inject, onMounted, ref, watch } from "vue";
import { loadDraft, saveDraft } from "../composables/useDraft.js";

const DRAFT_KEY = "gregoshop.install.license";
const bootstrap = inject("bootstrap");
const data = bootstrap.stepData || {};
const draft = loadDraft(DRAFT_KEY);

const agreed = ref(
  draft.licenceAgrement === true ||
    draft.licenceAgrement === 1 ||
    draft.licenceAgrement === "1" ||
    !!data.licenceAgrement,
);

function syncNext() {
  window.__INSTALL_UI__?.setNextDisabled(!agreed.value);
}

function persistDraft() {
  saveDraft(DRAFT_KEY, { licenceAgrement: !!agreed.value });
}

watch(agreed, () => {
  persistDraft();
  syncNext();
});
onMounted(syncNext);
</script>

<template>
  <h2 id="licenses-agreement">{{ data.title }}</h2>
  <p>
    <strong>{{ data.intro }}</strong>
  </p>
  <div class="infosLicense" v-html="data.licenseHtml"></div>

  <div class="checkLicense">
    <input
      id="set_license"
      v-model="agreed"
      type="checkbox"
      class="required"
      name="licence_agrement"
      value="1"
      style="margin-top: 2px"
    />
    <div>
      <label for="set_license">
        <strong>{{ data.agreeLabel }}</strong>
      </label>
    </div>
  </div>

  <div class="infosBlock">
    <h4>{{ data.privacyTitle }}</h4>
    <p v-html="data.privacyHtml"></p>
  </div>
</template>
