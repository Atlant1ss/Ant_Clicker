export function get_crit_chance(soldier_count, witch_multiplier = 1) {
    const base_chance = 0.5 * soldier_count / (soldier_count + 45);

    return Math.min(base_chance * witch_multiplier, 1);
}

export function get_crit_multiplier(soldier_count, witch_multiplier = 1) {
    const base_multiplier = 2 + soldier_count / (soldier_count + 50);

    return base_multiplier * witch_multiplier;
}
