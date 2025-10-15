let emails = [];

async function fetchEmails() {
  try {
    const response = await fetch(
      window.location.origin + "/api/email/fetch.php"
    );
    const data = await response.json();
    emails = data.emails || [];
  } catch (error) {
    console.error("Error fetching emails:", error);
    emails = [];
  }
}

async function tryFetchingEmails(retries = 3) {
  try {
    await fetchEmails();
  } catch (error) {
    if (retries > 0) {
      setTimeout(() => tryFetchingEmails(retries - 1), 5000);
    }
  }
}
