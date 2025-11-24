function renderTable(page = 1) {
  const table = document.querySelector("table");

  const existingTbody = table.querySelector("tbody");
  if (existingTbody) {
    table.removeChild(existingTbody);
  }

  const existingTfoot = table.querySelector("tfoot");
  if (existingTfoot) {
    table.removeChild(existingTfoot);
  }

  const tableBody = document.createElement("tbody");
  table.appendChild(tableBody);

  const startIndex = (page - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, jobs.length);

  for (let i = startIndex; i < endIndex; i++) {
    const job = jobs[i];
    const row = document.createElement("tr");
    row.id = job.id;

    row.innerHTML = `
    <td>${job.position}</td>
    <td>${job.minimumSalary}</td>
    <td>${job.companyName} ~~ ${job.address}</td>
      <td>${job.deadline}</td>
      <td>
      <div class="action_buttons">
      <button class="green_button transparent_button" title="Relist ${job.position} at ${job.companyName}" onclick="reListDialog('${job.id}')">♻️</button>
      <button class="red_button transparent_button" title="Delete ${job.position} at ${job.companyName}" onclick="removeDialog('${job.id}')">❌</button>
      </div>
      </td>
      `;

    tableBody.appendChild(row);
  }

  const tableFooter = document.createElement("tfoot");
  currentPage = page;

  const footerRow = document.createElement("tr");
  footerRow.innerHTML = generateFooterNavigation(
    jobs.length,
    table.rows[0].cells.length
  );
  tableFooter.appendChild(footerRow);

  table.appendChild(tableFooter);
}
