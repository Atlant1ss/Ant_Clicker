
export default async function save_game_state(cur_ants, ants_count) {
    try {
        const response = await fetch("../api/save_game_state/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                cur_ants: cur_ants,
                ants_count: ants_count
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Could not save game.");
        }

        console.log(result.message);

        return true;

    } catch (error) {
        console.error("Save error:", error.message);

        return false;
    }
}
