function filterAndRenderTable(query) {
    const table = document.querySelector("#list_client table");
  
    const existingTbody = table.querySelector("tbody");
    if (existingTbody) {
      table.removeChild(existingTbody);
    }
    const existingTfoot = table.querySelector("tfoot");
    if (existingTfoot) {
      table.removeChild(existingTfoot);
    }
  
    const filteredClients = query ? clients.filter((client) =>
      client.name.toLowerCase().includes(query.toLowerCase())
    ) : clients;
  
    currentPage = 1;
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, filteredClients.length);
    const clientsToRender = filteredClients.slice(startIndex, endIndex);
  
    const tableBody = document.createElement("tbody");
    clientsToRender.forEach((client) => {
      const row = document.createElement("tr");
      row.id = client.id;
  
      row.innerHTML = `
        <td>${client.name}</td>
        <td>${client.address}</td>
        <td>${client.mobileNumber}</td>
        <td>${client.jobPreferences.join(", ")}</td>
        <td>
          <div class="action_buttons">
            <button class="positive_button transparent_button" title="View Client" onclick="showClient('${
              client.id
            }')">👀</button>
            <button class="positive_button transparent_button" title="Remove Client" onclick="archiveDialog('${
              client.id
            }')">❌</button>
          </div>
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
      <td colspan="3"></td>
      <td id="next_page">
        <button
          type="button"
          aria-label="Next Page"
          onclick="changePage('next')"
          class="blue_button ${
            currentPage * rowsPerPage >= filteredClients.length
              ? "disabled-link"
              : ""
          }"
        >
          Next&ensp;&gt;&gt;
        </button>
      </td>
    `;
    
    tableFooter.appendChild(footerRow);
    table.appendChild(tableFooter);
  }