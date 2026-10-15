document.addEventListener("DOMContentLoaded", () => {
  const img = document.getElementById("company_logo");
  img.addEventListener("click", () => {
    window.location.href = window.location.origin;
  });
  img.title = "Back to home page";

  document.getElementById("logout_button").addEventListener("click", () => {
    window.location.href = window.location.origin + "/logout.php";
  });
});
