
export default async function load_game_state() {
    try {
        const response = await fetch("../../api/load_game_state/", {
            method: "GET",
            credentials: "same-origin"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Could not load game.");
        }

        console.log("Game loaded successfully.");

        return result.game_state;

    } catch (error) {
        console.error("Load error:", error.message);

        return null;
    }
}
