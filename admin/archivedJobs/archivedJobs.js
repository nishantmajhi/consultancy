document.addEventListener("DOMContentLoaded", async () => {
  showLoadingSkeleton();

  await tryFetchingJobs(5);
  if (jobs.length !== 0) renderTable();

  disableMyLink();

  let debounceTimeout;
  nameInput = document.getElementById("organization_name");
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
      searchArchivedOrganization(event.target.value.trim());
      event.target.value = "";
      renderTable();
    }
  });

  document.getElementById("search_button").addEventListener("click", () => {
    searchArchivedOrganization(nameInput.value.trim());
    nameInput.value = "";
    renderTable();
  });
});

function disableMyLink() {
  const details = document.getElementById("archived_data");
  const anchor = details.querySelectorAll(".view-existing");
  const li = anchor[1].parentElement;
  li.classList.add("disabled-link");
}
