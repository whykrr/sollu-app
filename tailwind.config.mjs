/** @type {import('tailwindcss').Config} */
import plugin from 'tailwindcss/plugin'
import forms from '@tailwindcss/forms'

export default {
    content: [
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                dark: '#222222',
                light: '#F0F0F0',
                main: {
                    DEFAULT: '#004AAD',
                    light: '#3372D1',
                    lighter: '#6697E4',
                    dark: '#003080',
                    darker: '#002060',
                },
                secondary: {
                    DEFAULT: '#5DE0E6',
                    light: '#8AEDEF',
                    lighter: '#B6F4F8',
                    dark: '#36AEB2',
                    darker: '#22888D',
                },
                clay: {
                    DEFAULT: '#AD5B00',
                    light: '#C97C33',
                    dark: '#8B3E00',
                    muted: '#F5A623',
                },
                teal: {
                    DEFAULT: '#0072AD',
                    light: '#009ED1',
                    dark: '#005B80',
                    muted: '#4FD6DB',
                },
                turquoise: {
                    DEFAULT: '#36C7C9',
                    light: '#5EDFE1',
                    dark: '#28A0A2',
                    muted: '#8BE3E4',
                },
                indigo: {
                    DEFAULT: '#002AAD',
                    light: '#4C61D1',
                    dark: '#001B80',
                    muted: '#A7B2E0',
                },
                neutral: {
                    light: '#F0F0F0',
                    lighter: '#F9F9F9',
                    DEFAULT: '#E0E0E0',
                    dark: '#3A3A3A',
                    darker: '#1F1F1F',
                    muted: '#B0B0B0',
                },
                danger: {
                    DEFAULT: '#E63946',
                    light: '#F28B90',
                    dark: '#A52733',
                    muted: '#FF6F61',
                },
                warning: {
                    DEFAULT: '#FFB703',
                    light: '#FFCF66',
                    dark: '#CC9302',
                    muted: '#FFD54F',
                },
                success: {
                    DEFAULT: '#2A9D8F',
                    light: '#63D1BB',
                    dark: '#1D6F64',
                    muted: '#B8E0D7',
                },
                info: {
                    DEFAULT: '#219EBC',
                    light: '#6FD2E3',
                    dark: '#157182',
                    muted: '#B0E0E9',
                },
                purple: {
                    DEFAULT: '#6A0DAD',
                    light: '#A45BB5',
                    dark: '#4B0082',
                    muted: '#D6A7D9',
                },
                pink: {
                    DEFAULT: '#FF6F91',
                    light: '#FF9EB1',
                    dark: '#C04565',
                    muted: '#F1A7C5',
                },
                brown: {
                    DEFAULT: '#7B4B3A',
                    light: '#A76D5B',
                    dark: '#4A2C28',
                    muted: '#C0B2A3',
                },
                gray: {
                    DEFAULT: '#B0B0B0',
                    light: '#D0D0D0',
                    dark: '#7D7D7D',
                },
                'web-main': {
                    DEFAULT: '#0071B3',
                },
            },
            fontFamily: {
                dosis: ['"Dosis"', 'sans-serif'],
                'dosis-bold': ['"Dosis Bold"', 'sans-serif'],
                merriweather: ['Merriweather', 'sans-serif'],
            },
            borderWidth: {
                1: '1px',
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
        forms,
        plugin(function ({ addComponents, theme }) {
            const colors = theme('colors')
            const colorClasses = {}

            const specialColors = ['inherit', 'current', 'transparent']

            for (const [colorName, shades] of Object.entries(colors)) {
                if (typeof shades === 'object') {
                    for (const [shade, value] of Object.entries(shades)) {
                        if (typeof value === 'string' && isHexColor(value)) {
                            Object.assign(
                                colorClasses,
                                addColorClasses(colorName, shade, value)
                            )
                        }
                    }
                } else if (typeof shades === 'string' && isHexColor(shades)) {
                    Object.assign(
                        colorClasses,
                        addColorClasses(colorName, null, shades)
                    )
                }
            }

            addComponents(colorClasses)
        }),
    ],
}

// Valid HEX checker
function isHexColor(str) {
    return typeof str === 'string' && /^#([A-Fa-f0-9]{6})$/.test(str)
}

// HEX to RGBA converter
function hexToRgba(hex, alpha) {
    const r = parseInt(hex.slice(1, 3), 16)
    const g = parseInt(hex.slice(3, 5), 16)
    const b = parseInt(hex.slice(5, 7), 16)
    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

// Dynamic class generator
function addColorClasses(colorName, shade, colorValue) {
    const classKey = (prefix) =>
        `.${prefix}-${colorName}${shade && shade !== 'DEFAULT' ? `-${shade}` : ''}`

    return {
        [classKey('btn')]: {
            'background-color': colorValue,
            color: '#ffffff',
        },
        [classKey('btn-outline')]: {
            'border-width': '1px',
            'border-color': `${colorValue} !important`,
            color: colorValue,
        },
        [classKey('btn-highlight')]: {
            'background-color': hexToRgba(colorValue, 0.075),
            color: colorValue,
        },
        [`button${classKey('btn-highlight')}:hover, a${classKey(
            'btn-highlight'
        )}:hover, button${classKey('btn-highlight')}.active, a${classKey(
            'btn-highlight'
        )}.active`]: {
            'background-color': colorValue,
            color: '#ffffff',
        },
        [`button${classKey('btn-outline')}:hover, a${classKey(
            'btn-outline'
        )}:hover, button${classKey('btn-outline')}.active, a${classKey(
            'btn-outline'
        )}.active`]: {
            'background-color': colorValue,
            color: '#ffffff',
        },
        [`.btn-check:checked + ${classKey('btn-outline')}`]: {
            'background-color': colorValue,
            color: '#ffffff',
        },
        [`.btn-check:checked + ${classKey('btn-highlight')}`]: {
            'background-color': colorValue,
            color: '#ffffff',
        },
        [classKey('badge')]: {
            color: colorValue,
            'background-color': hexToRgba(colorValue, 0.1),
        },
        [classKey('alert')]: {
            color: colorValue,
            'border-color': colorValue,
            'background-color': hexToRgba(colorValue, 0.15),
        },
    }
}
