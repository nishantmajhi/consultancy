document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("admin_logo").addEventListener("click", () => {
        window.location.href = window.location.origin + "/login/";
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("admin_logo").addEventListener("click", () => {
        window.location.href = window.location.origin + "/login/";
    });
});

async function submitEmail(event) {
    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

    if (!email) {
        alertDialog("Error", "Please enter a valid email.");
        return;
    }

    try {
        const response = await fetch(`${window.location.origin}/api/email/save.php`, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
        });

        const data = await response.json();

        if (response.ok) {
            alertDialog("Thank You", data.status);
        } else {
            alertDialog("Error", data.error);
        }
    } catch (error) {
        console.error("Fetch Error:", error);
        alertDialog("Error", "Something went wrong. Please try again later.");
    }
}
