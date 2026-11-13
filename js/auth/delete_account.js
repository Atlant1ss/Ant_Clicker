let delete_account_form = document.getElementById("delete_account_form");
let delete_message = document.getElementById("delete_message");

delete_account_form.addEventListener("submit", async (event) => {
    event.preventDefault();

    let password = document.getElementById("password").value;

    let confirmed = confirm(
        "Are you sure you want to permanently delete your account?"
    );

    if (!confirmed) {
        return;
    }

    let delete_button = delete_account_form.querySelector("button");

    delete_button.disabled = true;
    delete_message.textContent = "Deleting account...";

    try {
        let response = await fetch("../../api/delete_account/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                password: password
            })
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.message);
        }

        localStorage.removeItem("username");

        alert(result.message);

        window.location.href = "../../";

    } catch (error) {
        delete_message.textContent = error.message;
    } finally {
        delete_button.disabled = false;
    }
});