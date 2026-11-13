export function get_crit_chance(soldier_count) {
    return 0.5 * soldier_count / (soldier_count + 45);
}

export function get_crit_multiplier(soldier_count) {
    return 2 + soldier_count / (soldier_count + 50);
}