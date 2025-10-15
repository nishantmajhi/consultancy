function renderTable(page = 1) {
  const table = document.querySelector("table");

  const existingTbody = table.querySelector("tbody");
  const existingTfoot = table.querySelector("tfoot");

  if (existingTbody) table.removeChild(existingTbody);
  if (existingTfoot) table.removeChild(existingTfoot);

  const tableBody = document.createElement("tbody");
  table.appendChild(tableBody);

  const startIndex = (page - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, states.length);

  for (let i = startIndex; i < endIndex; i++) {
    const info = states[i];
    const row = document.createElement("tr");
    row.id = "id" + info.id;

    row.innerHTML = `
    <td>${info.position} @ ${info.companyName}</td>
    <td>${info.name}</td>
    <td>
    <button class="${
      info.applicationState === "Applied"
        ? "theme_button transparent_button"
        : info.applicationState === "Accepted"
        ? "green_button transparent_button"
        : info.applicationState === "Rejected"
        ? "red_button transparent_button"
        : info.applicationState === "Unknown"
        ? "grey_button transparent_button"
        : "positive_button transparent_button"
    }" title="Change Application State" onclick="applicationStateDialog('${
      "id" + info.id
    }')">
    ${info.applicationState}
    </button>
    </td>
    <td>
    <button class="${
      info.paymentState === "Received"
        ? "green_button transparent_button"
        : info.paymentState === "Cancelled"
        ? "red_button transparent_button"
        : info.paymentState === "Unknown"
        ? "grey_button transparent_button"
        : "positive_button transparent_button"
    }" title="Change Payment State" onclick="paymentStateDialog('${
      "id" + info.id
    }')">
    ${info.paymentState}
    </button>
    </td>
    `;

    tableBody.appendChild(row);
  }

  const tableFooter = document.createElement("tfoot");
  currentPage = page;

  const footerRow = document.createElement("tr");
  footerRow.innerHTML = generateFooterNavigation(
    states.length,
    table.rows[0].cells.length
  );
  tableFooter.appendChild(footerRow);

  table.appendChild(tableFooter);
}
