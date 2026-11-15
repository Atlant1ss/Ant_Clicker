
const leaderboard_body = document.getElementById("leaderboardBody");

async function load_leaderboard() {
    try {
        const response = await fetch("../../api/leaderboard/");

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not load leaderboard.");
        }

        leaderboard_body.innerHTML = "";

        if (data.leaderboard.length === 0) {
            leaderboard_body.innerHTML = `
                <tr>
                    <td colspan="3">No players found.</td>
                </tr>
            `;

            return;
        }

        data.leaderboard.forEach((player, index) => {
            const row = document.createElement("tr");

            const rank_cell = document.createElement("td");
            const username_cell = document.createElement("td");
            const score_cell = document.createElement("td");

            let rank = index + 1;

            if (rank === 1) {
                rank_cell.textContent = "🥇 1st";
            } else if (rank === 2) {
                rank_cell.textContent = "🥈 2nd";
            } else if (rank === 3) {
                rank_cell.textContent = "🥉 3rd";
            } else {
                rank_cell.textContent = `${rank}th`;
            }

            username_cell.textContent = player.username;

            score_cell.textContent = Number(player.score).toLocaleString("en-US");

            row.appendChild(rank_cell);
            row.appendChild(username_cell);
            row.appendChild(score_cell);

            leaderboard_body.appendChild(row);
        });

    } catch (error) {
        console.error("Leaderboard error:", error);

        leaderboard_body.innerHTML = `
            <tr>
                <td colspan="3">Could not load leaderboard.</td>
            </tr>
        `;
    }
}

load_leaderboard();
