function removeDialog(uuid) {
  confirmDialog(
    "Confirm",
    "Are you sure you want to remove this client?",
    (confirmed) => {
      if(confirmed) removeClient(uuid);
    }
  );
}

async function removeClient(uuid) {
  try {
    const params = new URLSearchParams({ uuid });

    const response = await fetch(
      `${window.location.origin}/api/delete/client.php?${params.toString()}`,
      {
        method: "DELETE",
      }
    );

    if (response.ok) {
      clients = clients.filter(client => client.id !== uuid);
      renderTable(currentPage);
      toastDialog("success", "Client removed successfully!");
    } else {
      toastDialog("error", "Failed to remove the client!");
    }
  } catch (error) {
    toastDialog("error", "An error occurred while removing the client.");
    console.error("Error:", error);
  }
}