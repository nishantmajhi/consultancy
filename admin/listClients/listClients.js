document.addEventListener("DOMContentLoaded", async () => {
  showLoadingSkeleton();

  await Promise.all([tryFetchingJobs(5), tryFetchingClients(5)]);
  if (jobs.length !== 0 && clients.length !== 0) renderTable();

  sortByDate("descending");
  disableMyLink();

  document
    .getElementById("ascending_date")
    .addEventListener("click", () => sortByDate("ascending"));
  document
    .getElementById("descending_date")
    .addEventListener("click", () => sortByDate("descending"));
  document
    .getElementById("ascending_name")
    .addEventListener("click", () => sortByName("ascending"));
  document
    .getElementById("descending_name")
    .addEventListener("click", () => sortByName("descending"));

  let debounceTimeout;
  const nameInput = document.getElementById("name_input");
  nameInput.addEventListener("input", function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      filterAndRenderTable(this.value.trim());
    }, 300);
  });

  document.querySelector("nav > img").addEventListener("click", () => {
    window.location.href = window.location.origin;
  });

  nameInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      searchClientByName(event.target.value.trim());
      event.target.value = "";
      renderTable();
    }
  });

  document.getElementById("search_button").addEventListener("click", () => {
    searchClientByName(nameInput.value.trim());
    nameInput.value = "";
    renderTable();
  });
});

function disableMyLink() {
  const details = document.getElementById("job_seekers");
  const anchor = details.querySelector(".view-existing");
  const li = anchor.parentElement;
  li.classList.add("disabled-link");
}