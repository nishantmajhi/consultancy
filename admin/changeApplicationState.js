function applicationStateDialog(ID) {
  const applicationStates = [
    "Unknown",
    "Applied",
    "Interview",
    "Accepted",
    "Rejected",
  ];

  const formContent = [
    {
      type: "select",
      name: "applicationState",
      options: applicationStates.map((state) => ({
        value: state,
        label: state,
      })),
    },
  ];

  formDialog(
    "Change State?",
    "Select from the list of all application states.",
    formContent,
    (formData) => {
      if (formData) changeApplicationState(formData, ID);
    }
  );
}

function changeApplicationState(formData, ID) {
  const params = new URLSearchParams({ id: ID.match(/\d+/)[0] });
  const url = `${
    window.location.origin
  }/api/changeState/application.php?${params.toString()}`;

  const options = {
    method: "PATCH",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      state: formData.applicationState,
    }),
  };

  (async () => {
    try {
      const response = await fetch(url, options);

      if (response.ok) {
        const button = document.querySelector(
          `#${ID} > td:nth-last-child(2) > button`
        );
        button.outerHTML = `<button class="${
          formData.applicationState === "Applied"
            ? "theme_button transparent_button"
            : formData.applicationState === "Accepted"
            ? "green_button transparent_button"
            : formData.applicationState === "Rejected"
            ? "red_button transparent_button"
            : formData.applicationState === "Unknown"
            ? "grey_button transparent_button"
            : "positive_button transparent_button"
        }" onclick="applicationStateDialog('${ID}')">
            ${formData.applicationState}
          </button>`;
        toastDialog("success", "State changed successfully!");
      } else {
        toastDialog("error", "Failed to change the state!");
      }
    } catch (error) {
      toastDialog("error", "An error occurred while changing the state.");
      console.error("Error:", error);
    }
  })();
}
