function searchArchivedOrganization(name) {
  fetch(`${window.location.origin}/api/search/archivedJobs.php?company=${name}`)
    .then((response) => response.json())
    .then((data) => {
      jobs = data.jobs;
    })
    .catch((error) => {
      console.error("Error fetching jobs:", error);
      jobs = [];
    });
}
