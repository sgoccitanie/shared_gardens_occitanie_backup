// assets/admin-menu.js
document.addEventListener("DOMContentLoaded", () => {
  document
    .querySelectorAll('#main-menu [data-bs-toggle="dropdown"]')
    .forEach((toggle) => {
      bootstrap.Dropdown.getOrCreateInstance(toggle, {
        popperConfig: (defaultConfig) => ({
          ...defaultConfig,
          strategy: "fixed",
        }),
      });
    });
});
