function formDialog(title, msgInnerHTML, formContent, formHandler) {
    const dialog = generateDialogTemplate(title, msgInnerHTML);
  
    const form = document.createElement("form");
    form.onsubmit = function (event) {
      event.preventDefault();
      document.body.removeChild(document.querySelector(".modal"));
  
      const formData = new FormData(form);
      const formValues = Object.fromEntries(formData.entries());
      formHandler(formValues);
    };
  
    const mainSection = document.createElement("main");
    mainSection.style.display = "flex";
  
    if (Array.isArray(formContent)) {
      formContent.forEach((inputConfig) => {
        const inputField = document.createElement(
          inputConfig.type === "select" ? "select" : "input"
        );
  
        if (inputConfig.type === "select") {
          inputConfig.options.forEach((option) => {
            const optionElement = document.createElement("option");
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            inputField.appendChild(optionElement);
          });
        } else {
          inputField.type = inputConfig.type || "text";
          inputField.placeholder = inputConfig.placeholder || "";
          inputField.style.width = inputConfig.placeholder.length + "ch";
        }
  
        inputField.name = inputConfig.name;
        inputField.style.flexGrow = "1";
        inputField.style.margin = "5px";
        mainSection.appendChild(inputField);
      });
    }
  
    form.appendChild(mainSection);
  
    const actionsSection = document.createElement("section");
    actionsSection.id = "actions";
  
    const confirmButton = document.createElement("button");
    confirmButton.type = "submit";
    confirmButton.textContent = "Submit";
    confirmButton.classList.add("negative_button");
    confirmButton.classList.add("confirm_button");
    actionsSection.appendChild(confirmButton);
  
    const cancelButton = document.createElement("button");
    cancelButton.type = "button";
    cancelButton.textContent = "Cancel";
    cancelButton.classList.add("transparent_button");
    cancelButton.onclick = function () {
      document.body.removeChild(document.querySelector(".modal"));
      formHandler(null);
    };
    actionsSection.appendChild(cancelButton);
  
    form.appendChild(actionsSection);
    dialog.appendChild(form);
    document.body.appendChild(dialog);
    dialog.showModal();
  }