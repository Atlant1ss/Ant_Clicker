import { show_notification } from "../notifications.js";

export function get_corporate_interest(corporate_count) {
    return Math.min(corporate_count * 0.01, 0.20);
}

let corporate_interval = null;

export function start_corporate(
    ants_count,
    get_current_ants,
    add_ants
) {
    if (corporate_interval !== null) {
        clearInterval(corporate_interval);
    }

    corporate_interval = setInterval(() => {
        if (ants_count.corporate <= 0) {
            return;
        }

        const current_ants = get_current_ants();

        if (current_ants <= 0) {
            return;
        }

        const interest_rate = get_corporate_interest(
            ants_count.corporate
        );

        const interest = Math.floor(
            current_ants * interest_rate
        );

        if (interest <= 0) {
            return;
        }

        add_ants(interest);
        show_notification(`JUROS! +${interest} formigas (${(interest_rate * 100).toFixed(0)}%)`,"interest");

        console.log(
            `Corporate interest: +${interest} ants (${interest_rate * 100}%)`
        );
    }, 120000);
}