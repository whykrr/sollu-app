/** @type {import('tailwindcss').Config} */
import plugin from 'tailwindcss/plugin';

export default {
    content: [
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                'dosis': ['"Dosis"', ...'sans'],
                'dosis-bold': ['"Dosis Bold"', ...'sans'],
                'merriweather': ['Merriweather', 'sans-serif'],
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeOut: {
                    '0%': { opacity: '1' },
                    '100%': { opacity: '0' },
                },
            },
            animation: {
                fadeIn: 'fadeIn 0.5s ease-in-out',
                fadeOut: 'fadeOut 0.5s ease-in-out',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        plugin(function ({ addComponents, theme }) {
            const colors = theme('colors');
            for (const [colorName, shades] of Object.entries(colors)) {
                addComponents(addColorClasses(colorName));
                // console.log(colorName);
            }
        }),
    ],
}

function addColorClasses(color) {
    const colorClasses = {};

    const colorVar = `var(--color-${color})`;

    const btn = `.btn-${color}`;
    const btnOutline = `.btn-outline-${color}`;
    const btnHighlight = `.btn-highlight-${color}`;
    const badge = `.badge-${color}`;
    const alert = `.alert-${color}`;
    const widget = `.widget-${color}`;

    // BTN
    colorClasses[btn] = {
        'background-color': colorVar,
        'color': '#ffffff',
        '&:disabled': {
            'background-color': '#e5e7eb', // gray-200
            'color': '#9ca3af',            // gray-400
            'cursor': 'not-allowed',
        },
        '&:disabled:hover': {
            'background-color': '#e5e7eb',
            'filter': 'none',
        }
    };

    // BTN OUTLINE
    colorClasses[btnOutline] = {
        'border-width': '1px',
        'border-color': `${colorVar} !important`,
        'color': colorVar,
        'background-color': 'transparent',
        '&:disabled': {
            'border-color': '#D1D5DB', // gray-300
            'color': '#9ca3af',        // gray-400
            'cursor': 'not-allowed',
        },
        '&:disabled:hover': {
            'background-color': 'transparent',
            'filter': 'none',
        }
    };

    // BTN HIGHLIGHT
    colorClasses[btnHighlight] = {
        'background-color': `--alpha(${colorVar} / 20%)`,
        'color': colorVar,
        '&:disabled': {
            'background-color': 'var(--color-gray-200)', // gray-200
            'color': '#9ca3af',
            'cursor': 'not-allowed',
        },
        '&:disabled:hover': {
            'background-color': 'var(--color-gray-200)',
            'color': '#9ca3af',
            'filter': 'none',
        }
    };

    // Hover & active states
    const hoverStyle = {
        'background-color': colorVar,
        'color': '#ffffff',
    };

    colorClasses[`button${btnOutline}:hover, a${btnOutline}:hover, button${btnOutline}.active, a${btnOutline}.active`] = hoverStyle;
    colorClasses[`button${btnHighlight}:hover, a${btnHighlight}:hover, button${btnHighlight}.active, a${btnHighlight}.active`] = hoverStyle;
    colorClasses[`.form-check-btn:checked + ${btnOutline}`] = hoverStyle;
    colorClasses[`.form-check-btn:checked + ${btnHighlight}`] = hoverStyle;

    // Badge
    colorClasses[badge] = {
        'color': colorVar,
        'background-color': `--alpha(${colorVar} / 7.5%)`,
    };

    // Alert
    colorClasses[alert] = {
        'color': colorVar,
        'border-color': `--alpha(${colorVar} / 30%)`,
        'background-color': `--alpha(${colorVar} / 15%)`,
    };

    colorClasses[`${widget} .widget-icon `] = {
        'color': colorVar,
        'background-color': `--alpha(${colorVar} / 20%)`,
    };
    colorClasses[`${widget} .widget-bar `] = {
        'background-color': `--alpha(${colorVar} / 30%)`,
    };
    colorClasses[`${widget} .widget-bar .widget-value `] = {
        'background-color': colorVar,
    };
    colorClasses[`${widget} .canvas `] = {
        'color': `--alpha(${colorVar} / 50%)`,
    };
    return colorClasses;
}

