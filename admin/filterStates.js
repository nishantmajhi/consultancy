function filterAndRenderTable(query) {
  const table = document.querySelector("table");

  const existingTbody = table.querySelector("tbody");
  if (existingTbody) {
    table.removeChild(existingTbody);
  }
  const existingTfoot = table.querySelector("tfoot");
  if (existingTfoot) {
    table.removeChild(existingTfoot);
  }

  const filteredData = (query && (document.getElementById("job").checked))
    ? states.filter((info) =>
        info.position.toLowerCase().includes(query.toLowerCase())
      )
    : (query && (document.getElementById("client").checked))
    ? states.filter((info) =>
        info.name.toLowerCase().includes(query.toLowerCase())
      )
    : states;

  currentPage = 1;
  const startIndex = (currentPage - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, filteredData.length);
  const dataToRender = filteredData.slice(startIndex, endIndex);

  const tableBody = document.createElement("tbody");
  dataToRender.forEach((info) => {
    const row = document.createElement("tr");
    row.id = info.submissionID;

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
        }" onclick="changeApplicationState('${info.id}')">
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
        }" onclick="changePaymentState('${info.id}')">
          ${info.paymentState}
        </button>
      </td>
    `;
    
    tableBody.appendChild(row);
  });
  table.appendChild(tableBody);

  const tableFooter = document.createElement("tfoot");
  const footerRow = document.createElement("tr");

  footerRow.innerHTML = `
    <td id="previous_page">
      <button
        type="button"
        aria-label="Previous Page"
        onclick="changePage('prev')"
        class="blue_button ${currentPage === 1 ? "disabled-link" : ""}"
      >
        &lt;&lt;&ensp;Prev
      </button>
    </td>
    <td colspan="2"></td>
    <td id="next_page">
      <button
        type="button"
        aria-label="Next Page"
        onclick="changePage('next')"
        class="blue_button ${
          currentPage * rowsPerPage >= states.length ? "disabled-link" : ""
        }"
      >
        Next&ensp;&gt;&gt;
      </button>
    </td>
  `;

  tableFooter.appendChild(footerRow);
  table.appendChild(tableFooter);
}