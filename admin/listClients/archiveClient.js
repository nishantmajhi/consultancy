function archiveDialog(uuid) {
  const formContent = [
    {
      type: "text",
      name: "promptInput",
      placeholder: "not compulsory but highly recommended",
    },
  ];

  formDialog(
    "Prompt",
    "Reason for archiving the client:",
    formContent,
    (formData) => {
      if (formData) archiveClient(uuid, formData.promptInput);
    }
  );
}

function archiveClient(uuid, reason) {
  const params = new URLSearchParams({ uuid });
  const url = `${
    window.location.origin
  }/api/archive/client.php?${params.toString()}`;

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
        clients = clients.filter((client) => client.id !== uuid);
        renderTable(currentPage);
        toastDialog("success", "Client archived successfully!");
      } else {
        toastDialog("error", "Failed to archive the client!");
      }
    } catch (error) {
      toastDialog("error", "An error occurred while archiving the client.");
      console.error("Error:", error);
    }
  })();
}
