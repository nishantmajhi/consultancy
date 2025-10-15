function searchJobByPosition(title) {
  fetch(
    `${window.location.origin}/api/search/jobs.php?position=${title}`
  )
    .then((response) => response.json())
    .then((data) => {
      jobs = data.jobs;
    })
    .catch((error) => {
      console.error("Error fetching jobs:", error);
      jobs = [];
    });
}
