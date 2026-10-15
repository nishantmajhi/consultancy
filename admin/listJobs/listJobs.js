document.addEventListener("DOMContentLoaded", async () => {
  showLoadingSkeleton();

  await Promise.all([tryFetchingJobs(5), tryFetchingClients(5)]);
  if (jobs.length !== 0 && clients.length !== 0) renderTable();

  sortByDate("descending");
  disableMyLink();

  const ascendingDateButton = document.getElementById("ascending_date");
  const descendingDateButton = document.getElementById("descending_date");
  ascendingDateButton.addEventListener("click", () => sortByDate("ascending"));
  descendingDateButton.addEventListener("click", () =>
    sortByDate("descending")
  );

  const ascendingSalaryButton = document.getElementById("ascending_salary");
  const descendingSalaryButton = document.getElementById("descending_salary");
  ascendingSalaryButton.addEventListener("click", () =>
    sortBySalary("ascending")
  );
  descendingSalaryButton.addEventListener("click", () =>
    sortBySalary("descending")
  );

  let debounceTimeout;
  const jobInput = document.getElementById("job_input");
  jobInput.addEventListener("input", function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      filterAndRenderTable(this.value.trim());
    }, 300);
  });

  document.querySelector("nav > img").addEventListener("click", () => {
    window.location.href = window.location.origin;
  });

  jobInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      searchJobByPosition(event.target.value.trim());
      event.target.value = "";
      renderTable();
    }
  });

  const searchButton = document.getElementById("search_button");
  searchButton.addEventListener("click", () => {
    searchJobByPosition(jobInput.value.trim());
    jobInput.value = "";
    renderTable();
  });
});

function disableMyLink() {
  const details = document.getElementById("available_jobs");
  const anchor = details.querySelector(".view-existing");
  const li = anchor.parentElement;
  li.classList.add("disabled-link");
}