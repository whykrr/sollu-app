export function formatQuantity(number) {
    if (isNaN(number) || number === null || number === '') return '0';
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(number));
}
