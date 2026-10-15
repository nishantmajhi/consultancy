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
    clients.sort((a, b) => {
      const timestampA = a.id.split("_")[1];
      const dateA = timestampA.split("-")[0];
      const timeA = timestampA.split("-")[1];

      const timestampB = b.id.split("_")[1];
      const dateB = timestampB.split("-")[0];
      const timeB = timestampB.split("-")[1];

      if (dateA !== dateB) {
        return parseInt(dateA) - parseInt(dateB);
      }
      return parseInt(timeA) - parseInt(timeB);
    });
  } else if (order === "descending") {
    document.getElementById("descending_date").classList.add("disabled-link");
    clients.sort((a, b) => {
      const timestampA = a.id.split("_")[1];
      const dateA = timestampA.split("-")[0];
      const timeA = timestampA.split("-")[1];

      const timestampB = b.id.split("_")[1];
      const dateB = timestampB.split("-")[0];
      const timeB = timestampB.split("-")[1];

      if (dateA !== dateB) {
        return parseInt(dateB) - parseInt(dateA);
      }
      return parseInt(timeB) - parseInt(timeA);
    });
  }

  renderTable();
}

function sortByName(order) {
  resetFilterButtons();
  if (order === "ascending") {
    document.getElementById("ascending_name").classList.add("disabled-link");
    clients.sort((a, b) => {
      return a.name.localeCompare(b.name);
    });
  } else if (order === "descending") {
    document.getElementById("descending_name").classList.add("disabled-link");
    clients.sort((a, b) => {
      return b.name.localeCompare(a.name);
    });
  }
  renderTable();
}
