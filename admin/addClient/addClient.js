document.addEventListener("DOMContentLoaded", () => {
  document.querySelector("nav > img").addEventListener("click", () => {
    window.location.href = window.location.origin;
  });

  setupFileDropZone();
  disableMyLink();
});

function disableMyLink() {
  const details = document.getElementById("job_seekers");
  const anchor = details.querySelector(".add-new");
  const li = anchor.parentElement;
  li.classList.add("disabled-link");
}
