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
  const endIndex = Math.min(startIndex + rowsPerPage, clients.length);

  for (let i = startIndex; i < endIndex; i++) {
    const client = clients[i];
    const row = document.createElement("tr");
    row.id = client.id;

    row.innerHTML = `
    <td>${client.name}</td>
    <td>${client.address}</td>
    <td>${client.mobileNumber}</td>
    <td>${client.jobPreferences.join(", ")}</td>
    <td>
    <div class="action_buttons">
    <button class="green_button transparent_button" title="Relist ${
      client.name
    }" onclick="reListDialog('${client.id}')">♻️</button>
    <button class="red_button transparent_button" title="Delete ${
      client.name
    }" onclick="removeDialog('${client.id}')">❌</button>
    </div>
    </td>
    `;

    tableBody.appendChild(row);
  }

  const tableFooter = document.createElement("tfoot");
  currentPage = page;

  const footerRow = document.createElement("tr");
  footerRow.innerHTML = generateFooterNavigation(
    clients.length,
    table.rows[0].cells.length
  );
  tableFooter.appendChild(footerRow);

  table.appendChild(tableFooter);
}
