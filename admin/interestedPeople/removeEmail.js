function removeDialog(emailID) {
  confirmDialog(
    "Prompt",
    "Are you sure you want to remove this email?",
    (confirmation) => {
      if (confirmation)
        (async () => {
          try {
            const response = await fetch(
              `${window.location.origin}/api/email/remove.php`,
              {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `email=${encodeURIComponent(emailID)}`,
              }
            );

            const data = await response.json();

            if (response.ok) {
              emails = emails.filter((address) => address.email !== emailID);
              renderTable(currentPage);
              alertDialog("Success", data.status);
            } else {
              alertDialog("Error", data.error);
            }
          } catch (error) {
            console.error("Fetch Error:", error);
            alertDialog("Error", "Something went wrong.");
          }
        })();
    }
  );
}
