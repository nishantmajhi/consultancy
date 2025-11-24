let states = [];

async function fetchStates() {
  try {
    const response = await fetch(
      window.location.origin + "/api/fetch/paymentsAndStatus.php"
    );
    const data = await response.json();
    states = data.states;
  } catch (error) {
    console.error("Error fetching states:", error);
    states = [];
  }
}

async function tryFetchingStates(retries = 3) {
  try {
    await fetchStates();
  } catch (error) {
    if (retries > 0) {
      setTimeout(() => tryFetchingStates(retries - 1), 5000);
    }
  }
}
