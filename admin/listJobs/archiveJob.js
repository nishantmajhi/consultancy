function archiveDialog(jobID) {
  const formContent = [
    {
      type: "text",
      name: "promptInput",
      placeholder: "not compulsory but highly recommended",
    },
  ];

  formDialog(
    "Prompt",
    "Reason for archiving the job:",
    formContent,
    (formData) => {
      if (formData) archiveJob(jobID, formData.promptInput);
    }
  );
}

function archiveJob(jobID, reason) {
  const params = new URLSearchParams({ jobID });
  const url = `${
    window.location.origin
  }/api/archive/job.php?${params.toString()}`;

  const options = {
    method: "PATCH",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      state: "archived",
      remarks: reason,
    }),
  };

  (async () => {
    try {
      const response = await fetch(url, options);

      if (response.ok) {
        jobs = jobs.filter((job) => job.id !== jobID);
        renderTable(currentPage);
        toastDialog("success", "Job archived successfully!");
      } else {
        toastDialog("error", "Failed to archive the job!");
      }
    } catch (error) {
      toastDialog("error", "An error occurred while archiving the job.");
      console.error("Error:", error);
    }
  })();
}
