(() => {
  const btn = document.getElementById("back-to-top");
  if (!btn) return;

  const THRESHOLD = 400; // px avant apparition
  let ticking = false;

  const update = () => {
    const show = window.scrollY > THRESHOLD;
    btn.hidden = !show;
    btn.classList.toggle("is-visible", show);
    ticking = false;
  };

  window.addEventListener(
    "scroll",
    () => {
      if (!ticking) {
        window.requestAnimationFrame(update);
        ticking = true;
      }
    },
    { passive: true },
  );

  btn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
    // remet le focus au début du document pour le clavier
    document.querySelector("header, main, body").focus?.();
  });

  update();
})();
