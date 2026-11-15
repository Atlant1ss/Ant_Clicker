export function get_witch_bonus(witch_count) {
    return Math.min(
        0.5 * Math.sqrt(witch_count),
        20
    );
}

export function get_witch_multiplier(witch_count) {
    return 1 + get_witch_bonus(witch_count) / 100;
}
