function reListDialog(uuid) {
  confirmDialog(
    "Confirm",
    "Are you sure you want to relist this client?",
    (confirmed) => {
      if(confirmed) reListClient(uuid);      
    }
  );
}

async function reListClient(uuid) {
  try {
    const params = new URLSearchParams({ uuid });

    const response = await fetch(
      `${window.location.origin}/api/relist/client.php?${params.toString()}`,
      {
        method: "PUT",
      }
    );

    if (response.ok) {
      clients = clients.filter(client => client.id !== uuid);
      renderTable(currentPage);
      toastDialog("success", "Client relisted successfully!");
    } else {
      toastDialog("error", "Failed to relist the client!");
    }
  } catch (error) {
    toastDialog("error", "An error occurred while relisting the client.");
    console.error("Error:", error);
  }
}