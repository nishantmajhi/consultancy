function paymentStateDialog(ID) {
  const paymentStates = ["Unknown", "Received", "Not Paid", "Cancelled"];

  const formContent = [
    {
      type: "select",
      name: "paymentState",
      options: paymentStates.map((state) => ({
        value: state,
        label: state,
      })),
    },
  ];

  formDialog(
    "Change State?",
    "Select from the list of all payment states.",
    formContent,
    (formData) => {
      if (formData) changePaymentState(formData, ID);
    }
  );
}

function changePaymentState(formData, ID) {
  const params = new URLSearchParams({ id: ID.match(/\d+/)[0] });
  const url = `${
    window.location.origin
  }/api/changeState/payment.php?${params.toString()}`;

  const options = {
    method: "PATCH",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      state: formData.paymentState,
    }),
  };

  (async () => {
    try {
      const response = await fetch(url, options);

      if (response.ok) {
        const button = document.querySelector(
          `#${ID} > td:nth-last-child(1) > button`
        );
        button.outerHTML = `<button class="${
          formData.paymentState === "Received"
            ? "green_button transparent_button"
            : formData.paymentState === "Cancelled"
            ? "red_button transparent_button"
            : formData.paymentState === "Unknown"
            ? "grey_button transparent_button"
            : "positive_button transparent_button"
        }" onclick="paymentStateDialog('${ID}')">
            ${formData.paymentState}
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
