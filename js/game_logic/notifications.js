export function show_notification(message, type = "info") {
    let notification_container = document.getElementById("notification_container");

    if (!notification_container) {
        notification_container = document.createElement("div");
        notification_container.id = "notification_container";
        notification_container.setAttribute("aria-live", "polite");
        document.body.appendChild(notification_container);
    }

    const notification = document.createElement("div");
    notification.className = `game_notification ${type}`;
    notification.textContent = message;

    notification_container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add("notification_hide");

        notification.addEventListener("transitionend", () => {
            notification.remove();
        }, { once: true });

        setTimeout(() => notification.remove(), 500);
    }, 2500);
}
