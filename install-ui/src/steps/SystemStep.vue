<script setup>
import { computed, inject } from "vue";

const bootstrap = inject("bootstrap");
const data = bootstrap.stepData || {};

/**
 * When every check is ok, CSS hides all li.ok (including category titles).
 * Rendering empty <ul>s between two <hr>s leaves a double divider under the
 * success banner — only show the lists when something remains visible.
 */
const showTestLists = computed(() => {
  if (!data.requiredSuccess) {
    return true;
  }
  const tests = data.tests || {};
  for (const group of Object.values(tests)) {
    const checks = group?.checks || {};
    for (const status of Object.values(checks)) {
      if (status !== "ok") {
        return true;
      }
    }
  }
  return false;
});
</script>

<template>
  <h2 id="install-system-title">{{ data.title }}</h2>

  <h3 v-if="data.requiredSuccess" class="okBlock">{{ data.okMessage }}</h3>
  <h3 v-else class="errorBlock">{{ data.errorMessage }}</h3>

  <template v-if="showTestLists">
    <hr />

    <template v-for="(categories, type) in data.testsRender" :key="type">
      <ul :id="type">
        <template v-for="(category, cIndex) in categories" :key="cIndex">
          <li class="title" :class="{ ok: category.success == 1 }">
            {{ category.title }}
          </li>
          <li
            v-for="(lang, id, index) in category.checks"
            :key="String(id) + '-' + index"
            class="required"
            :class="[
              index === 0 ? 'first' : '',
              data.tests?.[type]?.checks?.[id] || 'fail',
            ]"
          >
            <span v-html="lang"></span>
          </li>
        </template>
      </ul>
    </template>
  </template>

  <hr />
  <p>
    <input
      class="button button--secondary"
      type="submit"
      id="req_bt_refresh"
      :value="data.refreshLabel"
    />
  </p>
</template>
