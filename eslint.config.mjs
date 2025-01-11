import defineEslintConfig, { jsonc } from '@innocenzi/eslint-config'

export default defineEslintConfig(
	{ jsonc: false },
	jsonc({
		files: ['package.json', '**/package.json'],
		overrides: {
			'jsonc/indent': ['error', 2],
		},
	}),
	jsonc({
		files: ['composer.json', '**/composer.json'],
		overrides: {
			'jsonc/indent': ['error', 4],
		},
	}),
)
