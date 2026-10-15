function generateFooterNavigation(noOfData, noOfColumns) {
  return `
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
    <td colspan="${noOfColumns - 2}"></td>
    <td id="next_page">
      <button
        type="button"
        aria-label="Next Page"
        onclick="changePage('next')"
        class="blue_button ${
          currentPage * rowsPerPage >= noOfData ? "disabled-link" : ""
        }"
      >
        Next&ensp;&gt;&gt;
      </button>
    </td>
  `;
}
