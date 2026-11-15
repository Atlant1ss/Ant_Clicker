async function logout() {
    try {
        let response = await fetch("../../api/logout/", {
            method: "POST",
            credentials: "same-origin"
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.message);
        }

        localStorage.removeItem("username");

        window.location.href = "/";

    } catch (error) {
        console.error("Logout error:", error.message);
    }
}

logout();