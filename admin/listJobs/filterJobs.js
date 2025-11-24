function filterAndRenderTable(query) {
  const table = document.querySelector("#list_job table");

  const existingTbody = table.querySelector("tbody");
  if (existingTbody) {
    table.removeChild(existingTbody);
  }
  const existingTfoot = table.querySelector("tfoot");
  if (existingTfoot) {
    table.removeChild(existingTfoot);
  }

  const filteredJobs = query
    ? jobs.filter((job) =>
        job.position.toLowerCase().includes(query.toLowerCase())
      )
    : jobs;

  currentPage = 1;
  const startIndex = (currentPage - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, filteredJobs.length);
  const jobsToRender = filteredJobs.slice(startIndex, endIndex);

  const tableBody = document.createElement("tbody");
  jobsToRender.forEach((job) => {
    const row = document.createElement("tr");
    row.id = job.id;

    row.innerHTML = `
      <td>${job.position}</td>
      <td>${job.minimumSalary}</td>
      <td>${job.companyName} ~~ ${job.address}</td>
      <td>${job.deadline}</td>
      <td>
        <div class="action_buttons">
          <button class="positive_button transparent_button" title="View Job" onclick="applyTo('${job.id}')">✏️</button>
          <button class="positive_button transparent_button" title="Remove Job" onclick="archiveDialog('${job.id}')">❌</button>
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
          currentPage * rowsPerPage >= jobs.length ? "disabled-link" : ""
        }"
      >
        Next&ensp;&gt;&gt;
      </button>
    </td>
  `;
  
  tableFooter.appendChild(footerRow);
  table.appendChild(tableFooter);
}