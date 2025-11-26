// Dark/light theme toggle logic for "Stop de Ontkokking"
(function () {
  const storageKey = "sdo-theme";
  const toggle = document.querySelector(".toggle-mode");
  if (!toggle) return;

  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)");

  const safeStorage = (function () {
    try {
      const testKey = "__sdo_storage_test__";
      window.localStorage.setItem(testKey, testKey);
      window.localStorage.removeItem(testKey);
      return window.localStorage;
    } catch (error) {
      return null;
    }
  })();

  const getStoredTheme = () =>
    safeStorage ? safeStorage.getItem(storageKey) : null;

  const setStoredTheme = (theme) => {
    if (!safeStorage) return;
    safeStorage.setItem(storageKey, theme);
  };

  const applyTheme = (theme) => {
    const isDark = theme === "dark";
    document.body.classList.toggle("dark-mode", isDark);
    toggle.textContent = isDark ? "Licht" : "Donker";
    toggle.setAttribute("aria-pressed", String(isDark));
  };

  const initialTheme = (() => {
    const stored = getStoredTheme();
    if (stored === "dark" || stored === "light") {
      return stored;
    }
    return prefersDark.matches ? "dark" : "light";
  })();

  applyTheme(initialTheme);

  toggle.addEventListener("click", () => {
    const nextTheme = document.body.classList.contains("dark-mode")
      ? "light"
      : "dark";
    applyTheme(nextTheme);
    setStoredTheme(nextTheme);
  });

  prefersDark.addEventListener("change", (event) => {
    const stored = getStoredTheme();
    if (!stored) {
      applyTheme(event.matches ? "dark" : "light");
    }
  });
})();


