async function submitJobApplication(jobID, uuid) {
    const params = new URLSearchParams({ jobID, uuid });
    const url = `${window.location.origin}/api/submitApplication/?${params.toString()}`;

    try {
        const response = await fetch(url);

        if (response.ok) {
            toastDialog("success", "Application submitted successfully!");
        } else {
            toastDialog("error", "Failed to submit the application!");
        }
    } catch (error) {
        toastDialog("error", "An error occurred while submitting the application.");
        console.error("Error:", error);
    }
}