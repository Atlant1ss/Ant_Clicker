let change_username_form = document.getElementById("change_username_form");
let change_message = document.getElementById("change_message");

change_username_form.addEventListener("submit", async (event) => {
    event.preventDefault();

    let password = document.getElementById("password_u").value;
    let new_username = document.getElementById("username").value;

    let confirmed = confirm(
        "Are you sure you want to change your username?"
    );

    if (!confirmed) {
        return;
    }

    let change_button = change_username_form.querySelector("button");
    change_button.disabled = true;

    change_message.textContent = "Changing username...";

    try {

        let respose = await fetch("../../api/change_username/", { 
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                password: password,
                new_username: new_username
            })
        });

        let result = await response.json();

        if (!response.ok)  {
            throw new Error(result.message);
        }

        alert(result.message);

        localStorage.setItem("username", new_username);

        window.location.href = "../jogo";

    } catch (error) {
        change_message.textContent = error.message;
    } finally {
        change_button.disabled = false;
    }
});