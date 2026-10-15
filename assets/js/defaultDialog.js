function generateDialogTemplate(title, msgInnerHTML) {
  const dialog = document.createElement("dialog");
  dialog.className = "modal";

  const titleDiv = document.createElement("div");
  titleDiv.id = "title";
  titleDiv.textContent = title;
  dialog.appendChild(titleDiv);

  const messageDiv = document.createElement("div");
  messageDiv.id = "message";
  messageDiv.innerHTML = msgInnerHTML;
  dialog.appendChild(messageDiv);

  return dialog;
}

function alertDialog(title, msgInnerHTML) {
  const dialog = generateDialogTemplate(title, msgInnerHTML);

  const actionsSection = document.createElement("section");
  actionsSection.id = "actions";

  const okButton = document.createElement("button");
  okButton.type = "button";
  okButton.textContent = "\u2002OK\u2002";
  okButton.classList.add("positive_button");
  okButton.classList.add("transparent_button");
  okButton.onclick = function () {
    document.body.removeChild(document.querySelector(".modal"));
  };
  actionsSection.appendChild(okButton);

  dialog.appendChild(actionsSection);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function confirmDialog(title, msgInnerHTML, confirmationStateHandler) {
  const dialog = generateDialogTemplate(title, msgInnerHTML);

  const actionsSection = document.createElement("section");
  actionsSection.id = "actions";

  const confirmButton = document.createElement("button");
  confirmButton.type = "button";
  confirmButton.textContent = "Confirm";
  confirmButton.classList.add("negative_button");
  confirmButton.classList.add("confirm_button");
  confirmButton.onclick = function () {
    document.body.removeChild(document.querySelector(".modal"));
    confirmationStateHandler(true);
  };
  actionsSection.appendChild(confirmButton);

  const cancelButton = document.createElement("button");
  cancelButton.type = "button";
  cancelButton.textContent = "Cancel";
  cancelButton.classList.add("transparent_button");
  cancelButton.onclick = function () {
    document.body.removeChild(document.querySelector(".modal"));
    confirmationStateHandler(false);
  };
  actionsSection.appendChild(cancelButton);

  dialog.appendChild(actionsSection);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function promptDialog(
  title,
  msgInnerHTML,
  inputPlaceholder = "",
  promptTextHandler
) {
  const formContent = [
    {
      type: "text",
      name: "promptInput",
      placeholder: inputPlaceholder,
    },
  ];

  formDialog(title, msgInnerHTML, formContent, (formData) => {
    if (formData) {
      promptTextHandler(formData.promptInput);
    } else {
      promptTextHandler("");
    }
  });
}
