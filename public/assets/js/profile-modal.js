(function () {
  const overlay = document.getElementById("profileModalOverlay");
  if (!overlay) return;

  const openButtons = document.querySelectorAll("[data-open-profile-modal]");
  const closeButtons = document.querySelectorAll("[data-close-profile-modal]");

  const openModal = () => {
    overlay.classList.add("is-open");
    overlay.setAttribute("aria-hidden", "false");
    document.body.classList.add("settings-modal-open");
  };

  const closeModal = () => {
    overlay.classList.remove("is-open");
    overlay.setAttribute("aria-hidden", "true");
    document.body.classList.remove("settings-modal-open");
  };

  openButtons.forEach((button) => {
    button.addEventListener("click", openModal);
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && overlay.classList.contains("is-open")) {
      closeModal();
    }
  });
})();
