let currentPage = 1;
let rowsPerPage = (window.innerHeight < 800) ? 6 : (window.innerHeight >= 800 && window.innerHeight < 1000) ? 8 : 12;

function changePage(direction) {
  if (
    direction === "next" &&
    currentPage * rowsPerPage < states.length
  ) {
    currentPage++;
  } else if (direction === "prev" && currentPage > 1) {
    currentPage--;
  }
  
  renderTable(currentPage);
}