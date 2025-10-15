function submitSearchQuery(event) {
  event.preventDefault();
  const searchQuery = document.getElementById("search_bar").value;

  if (document.getElementById("job").checked) {
    fetch(
      `${window.location.origin}/api/search/jobs.php?position=${searchQuery}`
    )
      .then((response) => response.json())
      .then((data) => {
        jobs = data.jobs;
      })
      .catch((error) => {
        console.error("Error fetching jobs:", error);
        jobs = [];
      });
  } else if (document.getElementById("client").checked) {
    fetch(
      `${window.location.origin}/api/search/archivedClients.php?client=${searchQuery}`
    )
      .then((response) => response.json())
      .then((data) => {
        clients = data.clients;
      })
      .catch((error) => {
        console.error("Error fetching clients:", error);
        clients = [];
      });
  }
}
