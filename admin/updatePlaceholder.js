function updatePlaceholderOfSearchbar() {
  const searchBar = document.getElementById("search_bar");

  if (document.getElementById("job").checked) {
    searchBar.placeholder = "Type here to search by job title";
  } else if (document.getElementById("client").checked) {
    searchBar.placeholder = "Type here to search by client's name";
  }
}