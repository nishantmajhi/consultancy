function toastDialog(type = "info", message, duration = 3000) {
  let toastContainer = document.querySelector(".toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.classList.add("toast-container");
    document.body.appendChild(toastContainer);
  }

  const newToast = document.createElement("div");
  newToast.classList.add("toast-notification");

  const borderThickness = "0.5rem";
  if (type === "success") newToast.style.borderLeft = `${borderThickness} solid var(--green-color)`;
  else if (type === "warning") newToast.style.borderLeft = `${borderThickness} solid var(--orange-color)`;
  else if (type === "error") newToast.style.borderLeft = `${borderThickness} solid var(--red-color)`;
  else newToast.style.borderLeft = `${borderThickness} solid var(--placeholder-color)`;

  newToast.innerHTML = `
    <span>${message}</span>
    <button class="toast-close">&times;</button>
  `;
  
  toastContainer.appendChild(newToast);

  newToast.querySelector(".toast-close").addEventListener("click", () => {
    removeToast(newToast);
  });

  setTimeout(() => {
    removeToast(newToast);
  }, duration);
}

function removeToast(toast) {
  toast.classList.add("fade-out");
  setTimeout(() => {
    toast.remove();
  }, 500);
}
