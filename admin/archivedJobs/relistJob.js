function reListDialog(jobID) {
  confirmDialog(
    "Confirm",
    "Are you sure you want to relist this job?",
    (confirmed) => {
      if(confirmed) reListJob(jobID);
    }
  );
}

async function reListJob(jobID) {
  try {
    const params = new URLSearchParams({ jobID });

    const response = await fetch(
      `${window.location.origin}/api/relist/job.php?${params.toString()}`,
      {
        method: "PUT",
      }
    );

    if (response.ok) {
      jobs = jobs.filter(job => job.id !== jobID);
      renderTable(currentPage);
      toastDialog("success", "Job relisted successfully!");
    } else {
      toastDialog("error", "Failed to relist the job!");
    }
  } catch (error) {
    toastDialog("error", "An error occurred while relisting the job.");
    console.error("Error:", error);
  }
}