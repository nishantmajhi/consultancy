function handleFiles(files) {
  const fileList = Array.from(files);
  const addedFiles = new Set();
  const nonImages = [];
  const previewContainer = document.getElementById("thumbnails");

  if (fileList.length > 0) {
    showPreviewContainer(previewContainer);
  }

  fileList.forEach((file) => {
    if (file.type.startsWith("image/")) {
      if (!addedFiles.has(file.name)) {
        addImagePreview(file, previewContainer, addedFiles);
      }
    } else {
      nonImages.push(file.name);
    }
  });

  handleNonImageFiles(nonImages);
}

function showPreviewContainer(previewContainer) {
  const containerPadding = 3;
  const gapBetweenImages = 1.5;
  const safetyBet = 4;
  const remToPx = parseFloat(getComputedStyle(document.documentElement).fontSize);
  const aside = document.querySelector("aside");
  const isAsideVisible = window.getComputedStyle(aside).display !== "none";

  let childrenWidth;
  let styles;

  if (isAsideVisible) {
    childrenWidth =
      (document.body.offsetWidth -
        aside.offsetWidth -
        2 * containerPadding * remToPx -
        4 * gapBetweenImages * remToPx -
        safetyBet * remToPx) /
      5;

    styles = `
      display: grid;
      gap: ${gapBetweenImages}rem;
      grid-template-columns: repeat(5, ${Math.floor(childrenWidth)}px);
    `;
  } else {
    styles = `
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: ${gapBetweenImages}rem;
    `;
  }

  previewContainer.style.cssText = styles;
}

function addImagePreview(file, previewContainer, addedFiles) {
  const figureContainer = createFigureContainer(file, addedFiles);
  previewContainer.appendChild(figureContainer);
  addedFiles.add(file.name);
}

function createFigureContainer(file, addedFiles) {
  const figureContainer = document.createElement("figure");
  figureContainer.classList.add("image_preview");

  const previewContainer = document.createElement("div");
  previewContainer.classList.add("preview_container");
  const img = createImageElement(file);
  const removeButton = createRemoveButton(file, figureContainer, addedFiles);

  previewContainer.appendChild(removeButton);
  previewContainer.appendChild(img);

  const imageCaption = document.createElement("figcaption");
  imageCaption.textContent = file.name;

  figureContainer.appendChild(previewContainer);
  figureContainer.appendChild(imageCaption);

  return figureContainer;
}

function createImageElement(file) {
  const img = document.createElement("img");
  img.src = URL.createObjectURL(file);
  img.alt = file.name;
  return img;
}

function createRemoveButton(file, figureContainer, addedFiles) {
  const removeButton = document.createElement("button");
  removeButton.classList.add("remove_img");
  removeButton.setAttribute("type", "button");
  removeButton.setAttribute("aria-label", "Remove Image");
  removeButton.addEventListener("click", () => {
    URL.revokeObjectURL(file.src);
    figureContainer.remove();
    addedFiles.delete(file.name);
    togglePreviewContainerVisibility();
  });
  return removeButton;
}

function togglePreviewContainerVisibility() {
  const previewContainer = document.getElementById("thumbnails");
  if (document.querySelectorAll(".image_preview").length === 0) {
    previewContainer.style.display = "none";
  }
}

function handleNonImageFiles(nonImages) {
  if (nonImages.length === 1) {
  toastDialog("error", 
      `Can't upload ${nonImages}. Please make sure it's an image.`
    );
  } else if (nonImages.length > 1) {
    let message = "Can't upload the following files:";

    message += '<ul style="font-size: 0.8rem;">';
    nonImages.forEach((image) => {
      message += `<li>${image}</li>`;
    });
    message += "</ul>";

    message += '<p style="font-style: italic;">Please make sure the files you are trying to upload are all images!</p>';
    alertDialog("Alert", message);
  }
}

function setupFileDropZone() {
  const dropZone = document.getElementById("drop_zone");
  const fileInput = document.getElementById("document_files");

  setupDragAndDropEvents(dropZone);
  setupDropEvent(dropZone);
  setupClickEvent(dropZone, fileInput);
  setupFileInputEvent(fileInput);
}

function setupDragAndDropEvents(dropZone) {
  ["dragenter", "dragover", "dragleave", "drop"].forEach((event) => {
    dropZone.addEventListener(event, (e) => {
      e.preventDefault();
      e.stopPropagation();
    });
  });

  ["dragenter", "dragover"].forEach((event) => {
    dropZone.addEventListener(event, () => dropZone.classList.add("dragover"));
  });

  ["dragleave", "drop"].forEach((event) => {
    dropZone.addEventListener(event, () =>
      dropZone.classList.remove("dragover")
    );
  });
}

function setupDropEvent(dropZone) {
  dropZone.addEventListener("drop", (event) => {
    const files = event.dataTransfer.files;
    handleFiles(files);
  });
}

function setupClickEvent(dropZone, fileInput) {
  dropZone.addEventListener("click", () => {
    fileInput.click();
  });
}

function setupFileInputEvent(fileInput) {
  fileInput.addEventListener("change", (event) => {
    const files = event.target.files;
    handleFiles(files);
  });
}
