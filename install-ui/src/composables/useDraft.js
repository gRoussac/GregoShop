/**
 * sessionStorage draft helpers (never store passwords).
 * Session-scoped only: cleared when the browser tab/session ends.
 */

const PASSWORD_DRAFT_KEYS = [
  "admin_password",
  "admin_password_confirm",
  "dbPassword",
];

function stripPasswordKeys(draft) {
  const cleaned = { ...draft };
  PASSWORD_DRAFT_KEYS.forEach((key) => {
    delete cleaned[key];
  });
  return cleaned;
}

export function loadDraft(key) {
  try {
    return stripPasswordKeys(
      JSON.parse(sessionStorage.getItem(key) || "{}") || {},
    );
  } catch {
    return {};
  }
}

export function saveDraft(key, partial, omitKeys = []) {
  try {
    const draft = stripPasswordKeys({ ...loadDraft(key), ...partial });
    omitKeys.forEach((omitKey) => {
      delete draft[omitKey];
    });
    sessionStorage.setItem(key, JSON.stringify(draft));
  } catch {
    // Ignore quota / private mode.
  }
}

/** Drop all installer drafts (e.g. index.php?restart=true). */
export function clearInstallDrafts() {
  try {
    const keys = [];
    for (let i = 0; i < sessionStorage.length; i += 1) {
      const key = sessionStorage.key(i);
      if (key && key.startsWith("gregoshop.install.")) {
        keys.push(key);
      }
    }
    keys.forEach((key) => sessionStorage.removeItem(key));
  } catch {
    // Ignore.
  }
}

export function isBlankInstallValue(value) {
  return (
    value === null ||
    value === undefined ||
    String(value).trim() === "" ||
    value === "0"
  );
}
