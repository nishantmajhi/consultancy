document.addEventListener("DOMContentLoaded", async () => {
  showLoadingSkeleton();

  await tryFetchingEmails(5);
  if (emails.length !== 0) renderTable();

  disableMyLink();

  const logo = document.querySelector("nav > img");
  if (logo) {
    logo.addEventListener("click", () => {
      window.location.href = window.location.origin;
    });
  }
});

function disableMyLink() {
  const details = document.getElementById("home");
  const anchor = details.querySelectorAll(".view-existing");
  const li = anchor[1].parentElement;
  li.classList.add("disabled-link");
}
