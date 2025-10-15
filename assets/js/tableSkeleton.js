function showLoadingSkeleton() {
  const table = document.querySelector("table");
  const tbody = document.createElement("tbody");

  for (let i = 0; i <= rowsPerPage; i++) {
    const row = document.createElement("tr");
    for (let j = 0; j < table.rows[0].cells.length; j++) {
      const cell = document.createElement("td");
      cell.classList.add("skeleton-container");

      const div = document.createElement("div");
      div.classList.add("skeleton");
      div.textContent = "\u00A0";

      cell.appendChild(div);
      row.appendChild(cell);
    }
    tbody.appendChild(row);
  }

  table.appendChild(tbody);
}
