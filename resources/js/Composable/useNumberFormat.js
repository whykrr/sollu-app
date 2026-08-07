export function formatNumberID(num) {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(
        Number(num),
    );
};
