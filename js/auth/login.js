
let login_button = document.getElementById("login_button");
let login_message = document.getElementById("login_message");

login_button.addEventListener("click", async () => {
    let username = document.getElementById("username").value.trim();
    let password = document.getElementById("password").value;

    if (!username || !password) {
        login_message.textContent = "Please fill in all fields.";
        return;
    }

    login_button.disabled = true;
    login_message.textContent = "Logging in...";

    try {
        let response = await fetch("../api/login/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                username: username,
                password: password
            })
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.message);
        }

        localStorage.setItem("username", result.username);

        window.location.href = "../jogo/";

    } catch (error) {
        login_message.textContent = error.message;
    } finally {
        login_button.disabled = false;
    }
});
