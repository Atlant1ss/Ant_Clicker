
import buy_ant from "./buy_ants.js";

let cur_ants = 0;
let cur_queens = 0;

function hatch() {
    cur_ants += 1 + cur_queens;

    document.getElementById("ant_count").textContent =
        `Ants: ${cur_ants}`;
}


const hatch_button = document.getElementById("comum_ant_button");
const buy_queen_button = document.getElementById("queen_ant_button");
const username_display = document.getElementById("username_display");

username_display.textContent = "Username: " + localStorage.getItem("username");

hatch_button.addEventListener("click", hatch);

buy_queen_button.addEventListener("click", () => {
    const ants_data = { value: cur_ants };

    if (buy_ant("queen", ants_data)) {
        cur_ants = ants_data.value;
        cur_queens++;
    }
});
