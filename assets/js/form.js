// Gestion des focus/blur/keyup pour les inputs (informations contact)
const inputs = document.querySelectorAll("#form-contact .form-control");
console.log(inputs);
inputs.forEach(function (input) {
  input.addEventListener("focusin", function (e) {
    console.log(e.target.value);
    if (e.target.value.length != 0) {
      e.target.closest(".form-group").classList.add("show-label");
    } else {
      e.target.closest(".form-group").classList.remove("show-label");
    }
  });
});
inputs.forEach(function (input) {
  input.addEventListener("focusout", function (e) {
    console.log(e.target.value);
    if (e.target.value.length != 0) {
      e.target.closest(".form-group").classList.add("show-label");
    } else {
      e.target.closest(".form-group").classList.remove("show-label");
    }
  });
});
inputs.forEach(function (input) {
  input.addEventListener("keyup", function (e) {
    console.log(e.target.value);
    if (e.target.value.length != 0) {
      e.target.closest(".form-group").classList.add("show-label");
    } else {
      e.target.closest(".form-group").classList.remove("show-label");
    }
  });
});

//Bootstrap 5 validation
document
  .querySelectorAll(
    "#form-contact input, #form-contact textarea, #form-contact select",
  )
  .forEach((input) => {
    input.addEventListener("input", function () {
      // Champ vide : reset
      if (this.value === "") {
        this.classList.remove("is-valid", "is-invalid");
        return;
      }

      // Vérifier la validité HTML5
      if (this.checkValidity()) {
        this.classList.remove("is-invalid");
        this.classList.add("is-valid");
      } else {
        this.classList.remove("is-valid");
        this.classList.add("is-invalid");
      }
    });

    // Blur quand on quitte le champ : si le champ n'est pas vide et invalide, on ajoute la classe is-invalid
    input.addEventListener("blur", function () {
      if (this.value !== "" && !this.checkValidity()) {
        this.classList.add("is-invalid");
      }
    });
  });
