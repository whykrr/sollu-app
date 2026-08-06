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

    const focusRing = {
        'outline': 'none',
        'box-shadow': `0 0 0 4px color-mix(in srgb, ${colorVar} 10%, transparent)`,
        'border-color': colorVar,
    };

    // BTN
    colorClasses[btn] = {
        'background-color': colorVar,
        'color': '#ffffff',
        '&:hover:not(:disabled)': {
            'background-color': `color-mix(in srgb, ${colorVar} 90%, black)`,
        },
        '&:focus': focusRing,
        '&:disabled': {
            'background-color': '#e5e7eb', // gray-200
            'color': '#9ca3af',            // gray-400
            'cursor': 'not-allowed',
        }
    };

    // BTN OUTLINE
    colorClasses[btnOutline] = {
        'border-width': '1px',
        'border-color': `${colorVar} !important`,
        'color': colorVar,
        'background-color': 'transparent',
        '&:focus': focusRing,
        '&:disabled': {
            'border-color': '#D1D5DB !important', // gray-300
            'color': '#9ca3af',        // gray-400
            'cursor': 'not-allowed',
        }
    };

    // BTN HIGHLIGHT
    colorClasses[btnHighlight] = {
        'background-color': `color-mix(in srgb, ${colorVar} 20%, transparent)`,
        'color': colorVar,
        '&:focus': focusRing,
        '&:disabled': {
            'background-color': 'var(--color-gray-200)', // gray-200
            'color': '#9ca3af',
            'cursor': 'not-allowed',
        }
    };

    // Hover & active states (for Outline and Highlight variants)
    const hoverStyle = {
        'background-color': colorVar,
        'color': '#ffffff',
    };

    colorClasses[`button${btnOutline}:hover:not(:disabled), a${btnOutline}:hover, button${btnOutline}.active, a${btnOutline}.active`] = hoverStyle;
    colorClasses[`button${btnHighlight}:hover:not(:disabled), a${btnHighlight}:hover, button${btnHighlight}.active, a${btnHighlight}.active`] = hoverStyle;
    colorClasses[`.form-check-btn:checked + ${btnOutline}`] = hoverStyle;
    colorClasses[`.form-check-btn:checked + ${btnHighlight}`] = hoverStyle;

    // Badge
    colorClasses[badge] = {
        'color': colorVar,
        'background-color': `color-mix(in srgb, ${colorVar} 7.5%, transparent)`,
    };

    // Alert
    colorClasses[alert] = {
        'color': colorVar,
        'border-color': `color-mix(in srgb, ${colorVar} 30%, transparent)`,
        'background-color': `color-mix(in srgb, ${colorVar} 15%, transparent)`,
    };

    colorClasses[`${widget} .widget-icon `] = {
        'color': colorVar,
        'background-color': `color-mix(in srgb, ${colorVar} 20%, transparent)`,
    };
    colorClasses[`${widget} .widget-bar `] = {
        'background-color': `color-mix(in srgb, ${colorVar} 30%, transparent)`,
    };
    colorClasses[`${widget} .widget-bar .widget-value `] = {
        'background-color': colorVar,
    };
    colorClasses[`${widget} .canvas `] = {
        'color': `color-mix(in srgb, ${colorVar} 50%, transparent)`,
    };
    return colorClasses;
}

