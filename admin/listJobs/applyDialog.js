function applyTo(jobID) {
  const formContent = [
    {
      type: "select",
      name: "jobSelection",
      options: clients.map((client) => ({
        value: client.id,
        label: `${client.name} ~~ ${client.address}`,
      })),
    },
  ];

  formDialog(
    "Apply For Job?",
    "Select from the list of all Offers.",
    formContent,
    (formData) => {
      if(formData) {
        submitJobApplication(jobID, formData.jobSelection);
      }
    }
  );
}