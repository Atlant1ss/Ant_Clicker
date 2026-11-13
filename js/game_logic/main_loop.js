
import buy_ant, { update_ant_display } from "./buy_ants.js";
import save_game_state from "./game_state/save_game.js";
import load_game_state from "./game_state/load_game.js";
import { get_crit_chance, get_crit_multiplier } from "./ants/soldier.js";

let cur_ants = 0;

let ants_count = {
    worker: 0,
    queen: 0,
    soldier: 0,
    explorer: 0,
    witch: 0,
    corporate: 0
};

let game_initialized = false;

function update_ant_balance() {
    document.getElementById("ant_count").textContent =
        `Ants: ${cur_ants}`;
}

function update_all_ant_displays() {
    for (const type in ants_count) {
        update_ant_display(type, ants_count);
    }
}

function hatch() {
    const base_gain = 1 + ants_count.queen;

    const crit_chance = get_crit_chance(ants_count.soldier);
    const crit_multiplier = get_crit_multiplier(ants_count.soldier);

    const is_crit = Math.random() < crit_chance;

    const gain = is_crit ? Math.floor(base_gain * crit_multiplier) : base_gain;

    cur_ants += gain;

    update_ant_balance();

    if (is_crit) {
        console.log(`CRITICAL! +${gain} ants!`);
    }

}

function purchase_ant(type) {
    const ants_data = {
        value: cur_ants
    };

    if (buy_ant(type, ants_data, ants_count)) {
        cur_ants = ants_data.value;

        update_ant_balance();
    }
}

async function save_current_game() {
    if (!game_initialized) {
        return;
    }

    await save_game_state(cur_ants, ants_count);
}

async function initialize_game() {
    console.log("Loading game...");

    const game_state = await load_game_state();

    if (!game_state) {
        console.error("Could not load game state. Starting as guest.");

        cur_ants = 0;
        ants_count = {
            worker: 0,
            queen: 0,
            soldier: 0,
            explorer: 0,
            witch: 0,
            corporate: 0
        };
    } else {
        cur_ants = Number(game_state.cur_ants);
        ants_count = game_state.ants_count;
    }

    update_ant_balance();
    update_all_ant_displays();

    const username_display =
        document.getElementById("username_display");

    if (username_display) {
        username_display.textContent =
            "Username: " + (localStorage.getItem("username") || "Unknown");
    }

    const hatch_button =
        document.getElementById("comum_ant_button");

    const save_game_button =
        document.getElementById("save_game_button");

    if (hatch_button) {
        hatch_button.addEventListener("click", hatch);
    }

    if (save_game_button) {
        save_game_button.addEventListener("click", save_current_game);
    }

    for (const type in ants_count) {
        const button = document.getElementById(`${type}_ant_button`);

        if (button) {
            button.addEventListener("click", () => {
                purchase_ant(type);
            });
        }
    }

    game_initialized = true;

    console.log("Game initialized successfully!");

    setInterval(save_current_game, 300000);
}

initialize_game();

function worker_production() {
    cur_ants += ants_count.worker;

    update_ant_balance();

}

setInterval(worker_production, 1000);