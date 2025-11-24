let clients = [];

async function fetchClients() {
  try {
    const response = await fetch(
      window.location.origin + "/api/fetch/clients.php"
    );
    const data = await response.json();
    clients = data.clients;
  } catch (error) {
    console.error("Error fetching clients:", error);
    clients = [];
  }
}

async function tryFetchingClients(retries = 3) {
  try {
    await fetchClients();
  } catch (error) {
    if (retries > 0) {
      setTimeout(() => tryFetchingClients(retries - 1), 5000);
    }
  }
}
