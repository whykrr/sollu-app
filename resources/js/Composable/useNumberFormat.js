/**
 * Formats a number by hiding trailing decimals if whole,
 * and showing at most 2 decimal places if decimals exist.
 *
 * Examples (id-ID locale):
 * - 10        => "10"
 * - "10.00"   => "10"
 * - 10.5      => "10,5"
 * - 10.50     => "10,5"
 * - 10.567    => "10,57"
 * - null/''   => "0"
 *
 * @param {number|string} value
 * @param {Object} options
 * @param {number} [options.minDecimals=0]
 * @param {number} [options.maxDecimals=2]
 * @param {string} [options.locale='id-ID']
 * @param {string} [options.fallback='0']
 * @returns {string}
 */
export function formatNumber(value, options = {}) {
    const {
        minDecimals = 0,
        maxDecimals = 2,
        locale = 'id-ID',
        fallback = '0',
    } = options;

    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const num = Number(value);
    if (isNaN(num)) {
        return fallback;
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: minDecimals,
        maximumFractionDigits: maxDecimals,
    }).format(num);
}

/**
 * Backward compatibility function
 */
export function formatNumberID(num) {
    return formatNumber(num);
}

/**
 * Vue 3 Composable for Number Formatting
 */
export function useNumberFormat() {
    return {
        formatNumber,
        formatNumberID,
    };
}
