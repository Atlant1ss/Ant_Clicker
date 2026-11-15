const mines_board = document.getElementById("mines_board");
const bet_amount = document.getElementById("bet_amount");
const mine_count = document.getElementById("mine_count");

const player_balance = document.getElementById("player_balance");
const current_multiplier = document.getElementById("current_multiplier");
const potential_prize = document.getElementById("potential_prize");

const sync_balance_button = document.getElementById("sync_balance_button");
const start_game_button = document.getElementById("start_game_button");
const cash_out_button = document.getElementById("cash_out_button");
const game_message = document.getElementById("game_message");

let game_active = false;
let game_loading = false;
let safe_count = 0;
let revealed_cells = [];
let game_buttons = [];

function update_balance(balance, notify_parent = true) {
    player_balance.textContent =
        `${Number(balance).toLocaleString("pt-BR")} formigas`;

    if (notify_parent && window.parent !== window) {
        window.parent.postMessage({
            type: "mines_balance_update",
            balance: String(balance)
        }, window.location.origin);
    }
}

function update_game_info(multiplier, prize) {
    current_multiplier.textContent =
        `${Number(multiplier).toFixed(2)}x`;

    potential_prize.textContent =
        `${Number(prize).toLocaleString("pt-BR")} formigas`;
}

function set_message(message) {
    game_message.textContent = message;
}

function set_loading(loading) {
    game_loading = loading;

    sync_balance_button.disabled = loading;
    start_game_button.disabled = loading || game_active;

    cash_out_button.disabled =
        loading || !game_active || safe_count <= 0;

    mines_board.querySelectorAll(".mine_cell").forEach((button) => {
        button.disabled =
            loading ||
            !game_active ||
            button.dataset.revealed === "true";
    });
}

async function sync_balance_from_server() {
    set_loading(true);
    set_message("Synchronizing balance...");

    try {
        const result = await api_request({
            action: "status"
        });

        update_balance(result.balance, false);
        set_message("Balance synchronized.");

    } catch (error) {
        set_message(error.message);
    } finally {
        set_loading(false);
    }
}

function sync_balance() {
    if (game_loading) {
        return;
    }

    if (window.parent !== window) {
        set_loading(true);
        set_message("Synchronizing balance...");

        window.parent.postMessage({
            type: "mines_balance_sync_request"
        }, window.location.origin);

        return;
    }

    sync_balance_from_server();
}

async function api_request(data) {
    const response = await fetch("../../api/bet/", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    });

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid server response.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

window.addEventListener("message", (event) => {
    if (
        event.origin !== window.location.origin ||
        event.source !== window.parent ||
        event.data?.type !== "mines_balance_sync_response"
    ) {
        return;
    }

    set_loading(false);

    if (!event.data.success) {
        set_message(event.data.message || "Could not synchronize balance.");
        return;
    }

    update_balance(event.data.balance);
    set_message("Balance synchronized.");
});

function create_board() {
    mines_board.innerHTML = "";
    game_buttons = [];

    for (let i = 0; i < 25; i++) {
        const cell_button = document.createElement("button");

        cell_button.type = "button";
        cell_button.className = "mine_cell";
        cell_button.dataset.cell = String(i);
        cell_button.dataset.revealed = "false";
        cell_button.textContent = "?";
        cell_button.setAttribute("aria-label", `Cell ${i + 1}`);

        cell_button.addEventListener("click", () => {
            reveal_cell(i);
        });

        mines_board.appendChild(cell_button);
        game_buttons.push(cell_button);
    }
}

function update_revealed_cells(revealed) {
    revealed_cells = revealed.map(Number);

    for (const cell of revealed_cells) {
        const cell_button = game_buttons[cell];

        if (!cell_button) {
            continue;
        }

        cell_button.dataset.revealed = "true";
        cell_button.textContent = "💎";
        cell_button.disabled = true;
    }
}

function show_mines(mines, revealed) {
    const revealed_set = new Set(revealed.map(Number));

    for (let i = 0; i < 25; i++) {
        const cell_button = game_buttons[i];

        if (mines.includes(i)) {
            cell_button.textContent = "💣";
            cell_button.dataset.revealed = "true";
            cell_button.classList.add("mine_hit");
        } else if (revealed_set.has(i)) {
            cell_button.textContent = "💎";
            cell_button.dataset.revealed = "true";
        }

        cell_button.disabled = true;
    }
}

function finish_game(message) {
    game_active = false;

    set_loading(false);
    set_message(message);
}

async function start_game() {
    if (game_loading || game_active) {
        return;
    }

    const bet = bet_amount.value;
    const mines = Number(mine_count.value);

    if (!/^[1-9]\d*$/.test(bet)) {
        set_message("Enter a valid bet.");
        return;
    }

    if (!Number.isInteger(mines) || mines < 1 || mines > 24) {
        set_message("Choose between 1 and 24 mines.");
        return;
    }

    set_loading(true);
    set_message("Starting game...");

    try {
        const result = await api_request({
            action: "start",
            bet: bet,
            mine_count: mines
        });

        update_balance(result.balance);

        if (result.game) {
            safe_count = Number(result.game.safe_count);

            create_board();
            update_revealed_cells(result.game.revealed);

            update_game_info(
                result.game.multiplier,
                result.game.prize
            );

            set_message("Active game restored!");
        } else {
            safe_count = 0;

            create_board();
            update_game_info(1, 0);

            set_message("Good luck! Choose a cell.");
        }

        game_active = true;
        set_loading(false);

    } catch (error) {
        set_loading(false);
        set_message(error.message);
    }
}

async function reveal_cell(cell) {
    if (!game_active || game_loading || revealed_cells.includes(cell)) {
        return;
    }

    set_loading(true);
    set_message("Revealing cell...");

    try {
        const result = await api_request({
            action: "reveal",
            cell: cell
        });

        update_balance(result.balance);

        if (result.result === "lost") {
            update_revealed_cells(result.revealed);
            show_mines(result.mines, result.revealed);
            update_game_info(0, 0);

            finish_game("💥 You hit a mine! You lost your bet.");
            return;
        }

        if (result.result === "cashed_out") {
            update_revealed_cells(result.revealed);
            show_mines(result.mines, result.revealed);

            update_game_info(
                result.multiplier,
                result.prize
            );

            finish_game(
                `🏆 All safe cells found! Prize: ${Number(result.prize).toLocaleString("pt-BR")} ants!`
            );

            return;
        }

        safe_count = Number(result.safe_count);

        update_revealed_cells(result.revealed);

        update_game_info(
            result.multiplier,
            result.prize
        );

        set_message("💎 Safe cell! Continue or cash out.");

        set_loading(false);

    } catch (error) {
        set_loading(false);
        set_message(error.message);
    }
}

async function cash_out() {
    if (!game_active || game_loading || safe_count <= 0) {
        return;
    }

    set_loading(true);
    set_message("Processing cash out...");

    try {
        const result = await api_request({
            action: "cash_out"
        });

        update_balance(result.balance);

        show_mines(result.mines, result.revealed);

        update_game_info(
            result.multiplier,
            result.prize
        );

        finish_game(
            `💰 You collected ${Number(result.prize).toLocaleString("pt-BR")} ants!`
        );

    } catch (error) {
        set_loading(false);
        set_message(error.message);
    }
}

async function restore_game() {
    set_loading(true);
    set_message("Checking previous game...");

    try {
        const result = await api_request({
            action: "status"
        });

        update_balance(result.balance, false);

        if (result.game) {
            safe_count = Number(result.game.safe_count);

            create_board();
            update_revealed_cells(result.game.revealed);

            update_game_info(
                result.game.multiplier,
                result.game.prize
            );

            game_active = true;

            set_message("Game restored! Continue playing.");
        } else {
            game_active = false;
            safe_count = 0;

            update_game_info(1, 0);

            set_message("Choose your bet and start playing.");
        }

        set_loading(false);

    } catch (error) {
        set_loading(false);
        set_message(error.message);
    }
}

start_game_button.addEventListener("click", start_game);
cash_out_button.addEventListener("click", cash_out);
sync_balance_button.addEventListener("click", sync_balance);

create_board();
restore_game();
