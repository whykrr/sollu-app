import globals from 'globals'
import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import prettierConfig from 'eslint-config-prettier'

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
                '_': 'readonly',
                '$': 'readonly',
                'document': 'readonly',
                'window': 'readonly',
                'confirm': 'readonly',
                'alert': 'readonly',
                'setTimeout': 'readonly',
                'FormData': 'readonly',
                'FileReader': 'readonly',
                'fetch': 'readonly',
            },
        },
        rules: {
            indent: ['error', 4],
            quotes: ['warn', 'single'],
            semi: ['warn', 'never'],
            'object-curly-spacing': ['error', 'always'],
            'no-unused-vars': ['error', { vars: 'all', args: 'after-used', ignoreRestSiblings: true }],
            'comma-dangle': ['warn', 'always-multiline'],
            'vue/multi-word-component-names': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/no-v-html': 'off',
            'vue/require-default-prop': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/html-indent': 'off',
            'vue/html-self-closing': [
                'warn',
                {
                    html: {
                        void: 'always',
                        normal: 'always',
                        component: 'always',
                    },
                },
            ],
            'vue/no-v-text-v-html-on-component': 'off',
        },
    },
    prettierConfig,
]
