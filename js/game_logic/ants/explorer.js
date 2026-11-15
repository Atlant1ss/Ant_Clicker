let explorer_interval = null;

export function start_explorer(ants_count, activate_click_buff, get_efficiency_multiplier) {
    if (explorer_interval !== null) {
        clearInterval(explorer_interval);
    }

    explorer_interval = setInterval(() => {
        if (ants_count.explorer <= 0) {
            return;
        }

        const efficiency_multiplier = get_efficiency_multiplier();

        const spawn_chance = Math.min(ants_count.explorer * 0.01 * efficiency_multiplier, 0.25);

        if (Math.random() < spawn_chance) {
            spawn_sugar_lump(activate_click_buff);
        }
    }, 1000);
}

function spawn_sugar_lump(activate_click_buff) {

    if (document.getElementById("sugar_lump")) {
        return;
    }

    const sugar_lump = document.createElement("button");

    sugar_lump.id = "sugar_lump";
    sugar_lump.className = "sugar_lump";
    sugar_lump.type = "button";
    sugar_lump.setAttribute("aria-label", "Torrão de açúcar");

    const max_x = Math.max(0, window.innerWidth - 55);
    const max_y = Math.max(0, window.innerHeight - 55);

    sugar_lump.style.left = `${Math.random() * max_x}px`;
    sugar_lump.style.top = `${Math.random() * max_y}px`;

    sugar_lump.addEventListener("click", () => {
        sugar_lump.remove();
        activate_click_buff();
    });

    document.body.appendChild(sugar_lump);

    setTimeout(() => {
    sugar_lump.remove();
    }, 10000);

}
