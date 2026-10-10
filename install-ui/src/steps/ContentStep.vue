<script setup>
import { inject, ref, watch } from "vue";
import { loadDraft, saveDraft } from "../composables/useDraft.js";

const ACTION_SELECTED = "selected";
const ACTION_NONE = "none";
const ACTION_ALL = "all";
const ACTION_BO = "bo";
const ACTION_FO = "fo";
const DRAFT_KEY = "gregoshop.install.content";
const bootstrap = inject("bootstrap");
const data = bootstrap.stepData || {};

/**
 * Token values (none/all/selected/bo/fo). Never use numeric 0 for "all":
 * HTML/Vue drop empty-looking values and the POST became missing → default none.
 */
function normalizeModuleAction(value) {
  const allowed = new Set([
    ACTION_NONE,
    ACTION_ALL,
    ACTION_SELECTED,
    ACTION_BO,
    ACTION_FO,
  ]);
  if (value === undefined || value === null || value === "") {
    return ACTION_NONE;
  }
  const asString = String(value);
  if (allowed.has(asString)) {
    return asString;
  }
  const legacy = {
    0: ACTION_ALL,
    1: ACTION_SELECTED,
    2: ACTION_NONE,
    3: ACTION_BO,
    4: ACTION_FO,
  };
  return legacy[asString] || ACTION_NONE;
}

function allModuleNames() {
  return (data.categories || []).flatMap((category) =>
    category.modules.map((module) => module.name),
  );
}

function boModuleNames() {
  if (Array.isArray(data.boModules) && data.boModules.length > 0) {
    return [...data.boModules];
  }
  return (data.categories || [])
    .filter((category) => category.name === "Administration")
    .flatMap((category) => category.modules.map((module) => module.name));
}

function foModuleNames() {
  if (Array.isArray(data.foModules) && data.foModules.length > 0) {
    return [...data.foModules];
  }
  const bo = new Set(boModuleNames());
  return allModuleNames().filter((name) => !bo.has(name));
}

function selectedDefaultNames() {
  if (
    Array.isArray(data.selectedDefaultModules) &&
    data.selectedDefaultModules.length > 0
  ) {
    return [...data.selectedDefaultModules];
  }
  return allModuleNames();
}

function modulesForAction(action) {
  if (action === ACTION_NONE) {
    return [];
  }
  if (action === ACTION_BO) {
    return boModuleNames();
  }
  if (action === ACTION_FO) {
    return foModuleNames();
  }
  if (action === ACTION_SELECTED) {
    // Config: install-dev/data/module_selection_defaults.php
    return selectedDefaultNames();
  }
  // all: everything checked
  return allModuleNames();
}

const draft = loadDraft(DRAFT_KEY);
const serverAction = normalizeModuleAction(data.moduleAction);
const moduleAction = ref(
  draft.moduleAction !== undefined &&
    draft.moduleAction !== null &&
    draft.moduleAction !== ""
    ? normalizeModuleAction(draft.moduleAction)
    : serverAction,
);

function initialSelectedModules() {
  const action = moduleAction.value;
  if (Array.isArray(draft.selectedModules)) {
    // Draft may be [] (NONE / unchecked all) — still trust it.
    return [...draft.selectedModules];
  }
  const fromSession = Array.isArray(data.selectedModules)
    ? [...data.selectedModules]
    : null;
  if (fromSession !== null) {
    return fromSession;
  }
  return modulesForAction(action);
}

const selectedModules = ref(initialSelectedModules());
const theme = ref(data.theme || (data.themes?.[0]?.name ?? ""));
const selectAll = ref(
  selectedModules.value.length > 0 &&
    selectedModules.value.length === allModuleNames().length,
);

function persistDraft() {
  saveDraft(DRAFT_KEY, {
    moduleAction: moduleAction.value,
    selectedModules: [...selectedModules.value],
    theme: theme.value,
  });
}

function applyActionModules(action) {
  // Switching radio: load the preset for that mode (user can then refine).
  selectedModules.value = modulesForAction(action);
  const allNames = allModuleNames();
  selectAll.value =
    allNames.length > 0 &&
    allNames.every((name) => selectedModules.value.includes(name));
}

watch(moduleAction, (action) => {
  applyActionModules(action);
  persistDraft();
});

watch(
  selectedModules,
  () => {
    persistDraft();
  },
  { deep: true },
);

watch(theme, () => {
  persistDraft();
});

function visibleCategories() {
  return (data.categories || []).filter(
    (category) => (category.modules || []).length > 0,
  );
}

function toggleSelectAll() {
  if (selectAll.value) {
    selectedModules.value = allModuleNames();
  } else {
    selectedModules.value = [];
  }
}

function onModuleToggle() {
  const allNames = allModuleNames();
  selectAll.value =
    allNames.length > 0 &&
    allNames.every((name) => selectedModules.value.includes(name));
}
</script>

<template>
  <div id="contentInfosBlock" class="modules-step">
    <header class="modules-step__header">
      <h2>{{ data.title }}</h2>
    </header>

    <input
      v-if="(data.themes || []).length === 1"
      type="hidden"
      name="theme"
      :value="theme"
    />

    <div v-else class="field clearfix theme-selection">
      <label class="aligned">
        {{ data.themeLabel }}
        <p class="theme-infos userInfos aligned">{{ data.themeHelp }}</p>
      </label>
      <div class="contentinput">
        <div class="themes-container">
          <div v-for="item in data.themes" :key="item.name" class="theme-card">
            <label>
              <input
                v-model="theme"
                type="radio"
                name="theme"
                :value="item.name"
                style="vertical-align: top"
                autocomplete="off"
              />
              {{ item.displayName }}
              <div class="image-block">
                <img :src="'../' + item.preview" :alt="item.displayName" />
              </div>
              <p class="theme-version">
                <strong>{{ data.versionLabel }}</strong> {{ item.version }}
              </p>
            </label>
          </div>
        </div>
      </div>
    </div>

    <ul class="modules-mode-list">
      <li v-for="option in data.moduleOptions" :key="option.value">
        <label>
          <input
            v-model="moduleAction"
            type="radio"
            name="module-action"
            :value="normalizeModuleAction(option.value)"
            autocomplete="off"
          />
          <span>{{ option.label }}</span>
        </label>
      </li>
    </ul>

    <div id="modules-container">
      <section
        v-for="(category, index) in visibleCategories()"
        :key="category.name"
        class="modules-category"
      >
        <div class="modules-category__head">
          <h3 class="modules-category__title">{{ category.label }}</h3>
          <label v-if="index === 0" class="modules-step__select-all">
            <input
              v-model="selectAll"
              type="checkbox"
              name="select-all"
              @change="toggleSelectAll"
            />
            <span>{{ data.selectAllLabel }}</span>
          </label>
        </div>
        <ul class="modules-grid">
          <li v-for="module in category.modules" :key="module.name">
            <label>
              <input
                v-model="selectedModules"
                type="checkbox"
                name="modules[]"
                :value="module.name"
                autocomplete="off"
                @change="onModuleToggle"
              />
              <span>{{ module.displayName }}</span>
            </label>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
