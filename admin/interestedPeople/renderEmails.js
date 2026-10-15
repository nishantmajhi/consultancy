function renderTable(page = 1) {
  table = document.querySelector("table");

  const existingTbody = table.querySelector("tbody");
  const existingTfoot = table.querySelector("tfoot");

  if (existingTbody) table.removeChild(existingTbody);
  if (existingTfoot) table.removeChild(existingTfoot);

  const tableBody = document.createElement("tbody");
  table.appendChild(tableBody);

  const startIndex = (page - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, emails.length);

  for (let i = startIndex; i < endIndex; i++) {
    const address = emails[i];
    const row = document.createElement("tr");
    row.id = address.email;

    row.innerHTML = `
      <td>${i + 1}</td>
      <td>${address.email}</td>
      <td>${address.submittedDate}</td>
      <td>
        <div class="action_buttons">
          <button class="reply-button positive_button transparent_button" title="Reply ${
            address.email
          }">✏️</button>
          <button class="remove-button red_button transparent_button" title="Remove ${
            address.email
          }">❌</button>
        </div>
      </td>
    `;

    row.querySelector(".reply-button").addEventListener("click", () => {
      const emailSubject = encodeURIComponent("Your Email Has Been Received");
      const emailBody = encodeURIComponent(
        `Hi,\n\nThank you for submitting your email!\n\nWith regards,\nHR Team`
      );
      const mailtoLink = `mailto:${address.email}?subject=${emailSubject}&body=${emailBody}`;
      window.open(mailtoLink, "_blank");
    });

    row.querySelector(".remove-button").addEventListener("click", () => {
      removeDialog(address.email);
    });

    tableBody.appendChild(row);
  }

  const tableFooter = document.createElement("tfoot");
  currentPage = page;

  const footerRow = document.createElement("tr");
  footerRow.innerHTML = generateFooterNavigation(
    emails.length,
    table.rows[0].cells.length
  );
  tableFooter.appendChild(footerRow);

  table.appendChild(tableFooter);
}
