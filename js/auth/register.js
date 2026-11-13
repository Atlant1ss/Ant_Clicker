
let register_form = document.getElementById("register_form");
let register_message = document.getElementById("register_message");

register_form.addEventListener("submit", async (event) => {
    event.preventDefault();

    let username = document.getElementById("username").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value;

    let register_button = register_form.querySelector("button");

    register_button.disabled = true;
    register_message.textContent = "Registering...";

    try {
        let response = await fetch("../api/register/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                username: username,
                email: email,
                password: password
            })
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.message);
        }

        localStorage.setItem("username", username);

        window.location.href = "../jogo/";

    } catch (error) {
        register_message.textContent = error.message;
    } finally {
        register_button.disabled = false;
    }
});
