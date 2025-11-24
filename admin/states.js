document.addEventListener("DOMContentLoaded", async () => {
  showLoadingSkeleton();

  await tryFetchingStates(5);
  if (states.length !== 0) renderTable();

  updatePlaceholderOfSearchbar();

  document
    .getElementById("job")
    .addEventListener("change", updatePlaceholderOfSearchbar);
  document
    .getElementById("client")
    .addEventListener("change", updatePlaceholderOfSearchbar);

  let debounceTimeout;
  const textInput = document.getElementById("search_bar");
  textInput.addEventListener("input", function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      filterAndRenderTable(this.value.trim());
    }, 300);
  });

  document.querySelector("nav > img").addEventListener("click", () => {
    window.location.href = window.location.origin;
  });

  textInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      submitSearchQuery(event.target.value.trim());
    }
  });

  const searchButton = document.getElementById("search_button");
  searchButton.addEventListener("click", () => {
    submitSearchQuery(textInput.value.trim());
  });
});
