function searchArchivedClient(name) {
  fetch(
    `${window.location.origin}/api/search/archivedClients.php?client=${name}`
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
