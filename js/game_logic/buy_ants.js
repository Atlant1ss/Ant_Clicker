
const base_prices = {
    worker: 10,
    queen: 50,
    soldier: 100,
    explorer: 200,
    witch: 500,
    corporate: 1000
};

const price_multiplier = 1.1;

export function get_ant_price(type, ants_count) {
    if (!(type in base_prices)) {
        return 0;
    }

    return Math.floor(
        base_prices[type] * Math.pow(price_multiplier, ants_count[type])
    );
}

export default function buy_ant(type, ants_data, ants_count) {
    const current_price = get_ant_price(type, ants_count);

    if (ants_data.value < current_price) {
        alert("Not enough ants to buy this type!");
        return false;
    }

    ants_data.value -= current_price;
    ants_count[type]++;

    document.getElementById("ant_count").textContent =
        `Ants: ${ants_data.value}`;

    update_ant_display(type, ants_count);

    return true;
}

export function update_ant_display(type, ants_count) {
    const current_price = get_ant_price(type, ants_count);

    const price_element = document.getElementById(`${type}_price`);

    if (price_element) {
        price_element.textContent =
            `${current_price}$ | ${ants_count[type]}`;
    }
}
