function resetFilterButtons() {
  const filterButtons = document.querySelectorAll("#filters button");
  filterButtons.forEach((button) => {
    if (button.classList.contains("disabled-link"))
      button.classList.remove("disabled-link");
  });
}

function sortByDate(order) {
  resetFilterButtons();
  if (order === "ascending") {
    document.getElementById("ascending_date").classList.add("disabled-link");
    jobs.sort((a, b) => {
      const [dateA, timeA] = a.id.split("_")[2].split("-");
      const [dateB, timeB] = b.id.split("_")[2].split("-");
      return dateA === dateB
        ? parseInt(timeA) - parseInt(timeB)
        : parseInt(dateA) - parseInt(dateB);
    });
  } else if (order === "descending") {
    document.getElementById("descending_date").classList.add("disabled-link");
    jobs.sort((a, b) => {
      const [dateA, timeA] = a.id.split("_")[2].split("-");
      const [dateB, timeB] = b.id.split("_")[2].split("-");
      return dateA === dateB
        ? parseInt(timeB) - parseInt(timeA)
        : parseInt(dateB) - parseInt(dateA);
    });
  }
  renderTable();
}

function sortBySalary(order) {
  resetFilterButtons();
  if (order === "ascending") {
    document.getElementById("ascending_salary").classList.add("disabled-link");
    jobs.sort((a, b) => {
      const salaryA = parseInt(a.id.split("_")[1]);
      const salaryB = parseInt(b.id.split("_")[1]);
      return salaryA - salaryB;
    });
  } else if (order === "descending") {
    document.getElementById("descending_salary").classList.add("disabled-link");
    jobs.sort((a, b) => {
      const salaryA = parseInt(a.id.split("_")[1]);
      const salaryB = parseInt(b.id.split("_")[1]);
      return salaryB - salaryA;
    });
  }
  renderTable();
}
