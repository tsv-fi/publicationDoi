/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution');

// Same rules as the ui-library of OJS/OMP/OPS 3.4
module.exports = {
	root: true,
	extends: [
		'plugin:vue/essential',
		'eslint:recommended',
		'@vue/eslint-config-prettier',
	],
	parserOptions: {
		ecmaVersion: 'latest',
		sourceType: 'module',
	},
	globals: {
		$: true,
		pkp: true,
		__dirname: true,
	},
	rules: {
		'prettier/prettier': [
			'warn',
			{
				bracketSpacing: false,
				singleQuote: true,
				useTabs: true,
				htmlWhitespaceSensitivity: 'ignore',
			},
		],
		'no-unused-vars': ['error', {args: 'none'}],
		'vue/multi-word-component-names': 'off',
	},
	ignorePatterns: ['public/build/'],
};
