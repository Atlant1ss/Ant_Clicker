import buy_ant, { update_ant_display } from "./buy_ants.js";
import save_game_state from "./game_state/save_game.js";
import load_game_state from "./game_state/load_game.js";
import { get_crit_chance, get_crit_multiplier } from "./ants/soldier.js";
import { start_explorer } from "./ants/explorer.js";
import { get_witch_multiplier } from "./ants/witch.js";
import { start_corporate } from "./ants/corporate.js";
import { show_notification } from "./notifications.js";

let cur_ants = 0;
let click_multiplier = 1;
let click_buff_timeout = null;

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

window.addEventListener("message", async (event) => {
    if (event.origin !== window.location.origin) {
        return;
    }

    const las_vegas_frame = document.getElementById("las_vegas_frame");

    if ( !las_vegas_frame || event.source !== las_vegas_frame.contentWindow) {
        return;
    }

    if (event.data?.type === "mines_balance_sync_request") {
        const saved = await save_game_state(cur_ants, ants_count);

        event.source.postMessage({
            type: "mines_balance_sync_response",
            success: saved,
            balance: String(cur_ants),
            message: saved
                ? "Balance synchronized."
                : "Could not save the current balance."
        }, window.location.origin);

        return;
    }

    if (event.data?.type !== "mines_balance_update") {
        return;
    }

    const new_balance = event.data.balance;

    if (!/^\d+$/.test(new_balance)) {
        return;
    }

    cur_ants = Number(new_balance);
    update_ant_balance();
});

function update_all_ant_displays() {
    for (const type in ants_count) {
        update_ant_display(type, ants_count);
    }
}

function activate_click_buff() {
    const explorer_count = ants_count.explorer;

    click_multiplier = Math.min(
        1 + Math.floor(Math.sqrt(explorer_count)),
        10
    );

    const buff_duration = Math.min(
        15 + Math.floor(Math.sqrt(Math.max(0, explorer_count - 1))) * 2,
        30
    );

    if (click_buff_timeout !== null) {
        clearTimeout(click_buff_timeout);
    }

    let buff_display = document.getElementById("click_buff_display");

    if (!buff_display) {
        buff_display = document.createElement("div");
        buff_display.id = "click_buff_display";
        buff_display.className = "click_buff_display";
        document.body.appendChild(buff_display);
    }

    buff_display.textContent =
        `Sugar Rush! Clique ${click_multiplier}x por ${buff_duration} segundos!`;

    buff_display.hidden = false;

    click_buff_timeout = setTimeout(() => {
        click_multiplier = 1;
        buff_display.hidden = true;
        click_buff_timeout = null;

        console.log("Click buff expired!");
    }, buff_duration * 1000);

    console.log(
        `Sugar Rush activated! ${click_multiplier}x for ${buff_duration}s`
    );
}


function hatch() {
    const witch_multiplier = get_witch_multiplier(ants_count.witch);

    const base_gain = Math.floor((1 + ants_count.queen * witch_multiplier) * click_multiplier);

    const crit_chance = get_crit_chance(ants_count.soldier, witch_multiplier);
    const crit_multiplier = get_crit_multiplier(ants_count.soldier, witch_multiplier);
    const is_crit = Math.random() < crit_chance;

    const gain = is_crit ? Math.floor(base_gain * crit_multiplier) : base_gain;

    cur_ants += gain;

    update_ant_balance();

    if (is_crit) {
        show_notification(`CRÍTICO! +${gain} formigas!`, "critical");

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
    start_explorer(ants_count, activate_click_buff, () => get_witch_multiplier(ants_count.witch));
    start_corporate(ants_count, () => cur_ants, (amount) => {
        cur_ants += amount;
        update_ant_balance();
    }
);

    console.log("Game initialized successfully!");

    setInterval(save_current_game, 300000);
}

initialize_game();

function worker_production() {
    const witch_multiplier = get_witch_multiplier(ants_count.witch);

    cur_ants += Math.floor(ants_count.worker * witch_multiplier);

    update_ant_balance();

}

setInterval(worker_production, 1000);
