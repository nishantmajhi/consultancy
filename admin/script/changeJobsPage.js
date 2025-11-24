let currentPage = 1;
let rowsPerPage = Math.floor(window.innerHeight / 100);

function changePage(direction) {
  
  if (direction === "next" && currentPage * rowsPerPage < jobs.length) {
    currentPage++;
  } else if (direction === "prev" && currentPage > 1) {
    currentPage--;
  }
  
  renderTable(currentPage);
}