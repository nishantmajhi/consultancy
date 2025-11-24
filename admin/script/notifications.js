let notifications = [];

document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("notifications_menu");
  container.innerHTML = "";

  const h2 = document.createElement("h2");
  h2.textContent = "Notifications";
  container.appendChild(h2);

  const section = document.createElement("section");
  container.appendChild(section);

  const button = document.getElementById("notification_bell");
  const overlay = document.querySelector(".notification-overlay");

  button.addEventListener("click", (e) => {
    e.stopPropagation();
    button.classList.toggle("active");
  });

  if (overlay) {
    overlay.addEventListener("click", () => {
      button.classList.remove("active");
    });
  }

  document.addEventListener("click", (e) => {
    const container = document.getElementById("notification_container");
    if (!container.contains(e.target)) {
      button.classList.remove("active");
    }
  });

  fetch(`${window.location.origin}/api/notifications/fetch.php`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success" && Array.isArray(data.notifications)) {
        notifications = data.notifications;

        notifications.forEach((notification) => {
          renderNotification(notification, false);
        });
      } else {
        console.error("Unexpected response format:", data);
      }
    })
    .catch((error) => {
      console.error("Error fetching notifications:", error);
      notifications = [];
    })
    .finally(() => {
      startSSE();
    });

  function startSSE() {
    const eventSource = new EventSource(
      `${window.location.origin}/api/notifications/SSE.php`
    );

    eventSource.addEventListener("notification", (event) => {
      const notification = JSON.parse(event.data);
      notifications.push(notification);
      renderNotification(notification, true);
    });

    eventSource.addEventListener("keepalive", () => {
      setTimeout(startSSE, 3000);
    });

    eventSource.onerror = () => {
      eventSource.close();
    };
  }

  function renderNotification(notification, addOnTop = true) {
    const dl = document.createElement("dl");

    const dt = document.createElement("dt");
    dt.textContent = notification.title;
    dl.appendChild(dt);

    const dd = document.createElement("dd");
    dd.textContent = notification.message;
    dl.appendChild(dd);

    if (addOnTop) {
      section.prepend(dl);
    } else {
      section.appendChild(dl);
    }
  }
});
