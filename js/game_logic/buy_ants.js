
const base_prices = {
    worker: 10,
    queen: 50,
    soldier: 100,
    explorer: 200,
    witch: 500,
    corporate: 1000
};

const ants_count = {
    worker: 0,
    queen: 0,
    soldier: 0,
    explorer: 0,
    witch: 0,
    corporate: 0
};

const price_multiplier = 1.1;

export function get_ant_price(type) {
    return Math.floor(
        base_prices[type] * Math.pow(price_multiplier, ants_count[type])
    );
}

export default function buy_ant(type, ants_data) {
    const current_price = get_ant_price(type);

    if (ants_data.value >= current_price) {
        ants_data.value -= current_price;
        ants_count[type]++;

        document.getElementById("ant_count").textContent =
            `Ants: ${ants_data.value}`;

        update_ant_display(type);

        return true;
    }

    alert("Not enough ants to buy this type!");
    return false;
}

export function update_ant_display(type) {
    const current_price = get_ant_price(type);

    document.getElementById(`${type}_price`).textContent =
        `${current_price}$ | ${ants_count[type]}`;
}
