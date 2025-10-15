function applyForJob(uuid) {
    const formContent = [
      {
        type: "select",
        name: "jobSelection",
        options: jobs.map((job) => ({
          value: job.id,
          label: `${job.position} - ${job.companyName}`,
        })),
      },
    ];
  
    formDialog(
      "Apply For Job?",
      "Select from the list of all Offers.",
      formContent,
      (formData) => {
        if (formData) {
          submitJobApplication(formData.jobSelection, uuid);
        }
      }
    );
  }