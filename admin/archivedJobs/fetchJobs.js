let jobs = [];

async function fetchJobs() {
  try {
    const response = await fetch(
      window.location.origin + "/api/fetch/archivedJobs.php"
    );
    const data = await response.json();
    jobs = data.jobs;
  } catch (error) {
    console.error("Error fetching clients:", error);
    jobs = [];
  }
}

async function tryFetchingJobs(retries = 3) {
  try {
    await fetchJobs();
  } catch (error) {
    if (retries > 0) {
      setTimeout(() => tryFetchingJobs(retries - 1), 5000);
    }
  }
}
