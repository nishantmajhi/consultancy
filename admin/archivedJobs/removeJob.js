function removeDialog(jobID) {
  confirmDialog(
    "Confirm",
    "Are you sure you want to remove this job?",
    (confirmed) => {
      if(confirmed) removeJob(jobID);
    }
  );
}

async function removeJob(jobID) {
  try {
    const params = new URLSearchParams({ jobID });

    const response = await fetch(
      `${window.location.origin}/api/delete/job.php?${params.toString()}`,
      {
        method: "DELETE",
      }
    );

    if (response.ok) {
      jobs = jobs.filter(job => job.id !== jobID);
      renderTable(currentPage);
      toastDialog("success", "Job removed successfully!");
    } else {
      toastDialog("error", "Failed to remove the job!");
    }
  } catch (error) {
    toastDialog("error", "An error occurred while removing the job.");
    console.error("Error:", error);
  }
}
