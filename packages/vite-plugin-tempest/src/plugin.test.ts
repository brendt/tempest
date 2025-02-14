import fs from 'node:fs'
import path from 'node:path'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import tempest from './plugin'
import { mockTempestConfiguration } from './test-utils'

describe('tempest', () => {
	beforeEach(() => {
		process.env.TEMPEST_BYPASS_ENV_CHECK = 'true'
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	it('it returns a Vite plugin', () => {
		mockTempestConfiguration()

		const plugin = tempest()

		expect(plugin).not.toBeNull()
	})

	it('it outputs a config', async () => {
		mockTempestConfiguration({
			build_directory: 'build/custom',
			bridge_file_name: 'custom-bridge',
			manifest: 'custom-manifest.json',
			entrypoints: ['src/main.ts'],
		})

		const plugin = tempest()

		// @ts-expect-error typing
		const config = await plugin.config({}, { command: 'build' })

		expect(config.base).toBe('/build/custom/')
		expect(config.build.manifest).toBe('custom-manifest.json')
		expect(config.build.rollupOptions).toEqual({ input: [
			'src/main.ts',
		] })
	})

	it("respects the user's server.cors config", async () => {
		mockTempestConfiguration()

		const plugin = tempest()

		// @ts-expect-error typing
		const resolvedConfig = await plugin.config({
			server: {
				cors: true,
			},
		}, {
			mode: '',
			command: 'serve',
		})

		expect(resolvedConfig.server.cors).toBe(true)
	})

	it('configures default cors.origin values', async () => {
		mockTempestConfiguration()

		const test = (pattern: RegExp | string, value: string) => pattern instanceof RegExp ? pattern.test(value) : pattern === value
		fs.writeFileSync(path.join(__dirname, '.env'), 'APP_URL=http://example.com')

		const plugin = tempest()

		// @ts-expect-error typing
		const resolvedConfig = await plugin.config({ envDir: __dirname }, {
			mode: '',
			command: 'serve',
		})

		// Allowed origins...
		expect([
			// localhost
			'http://localhost',
			'https://localhost',
			'http://localhost:8080',
			'https://localhost:8080',
			// 127.0.0.1
			'http://127.0.0.1',
			'https://127.0.0.1',
			'http://127.0.0.1:8000',
			'https://127.0.0.1:8000',
			// *.test
			'http://tempest.test',
			'https://tempest.test',
			'http://tempest.test:8000',
			'https://tempest.test:8000',
			'http://my-app.test',
			'https://my-app.test',
			'http://my-app.test:8000',
			'https://my-app.test:8000',
			'https://my-app.test:8',
			// APP_URL
			'http://example.com',
			'https://subdomain.my-app.test',
		].some((url) => resolvedConfig.server.cors.origin.some((regex: RegExp) => test(regex, url)))).toBe(true)

		// Disallowed origins...
		expect([
			'http://tempest.com',
			'https://tempest.com',
			'http://tempest.com:8000',
			'https://tempest.com:8000',
			'http://128.0.0.1',
			'https://128.0.0.1',
			'http://128.0.0.1:8000',
			'https://128.0.0.1:8000',
			'https://example.com',
			'http://example.com:8000',
			'https://example.com:8000',
			'http://exampletest',
			'http://example.test:',
		].some((url) => resolvedConfig.server.cors.origin.some((regex: RegExp) => test(regex, url)))).toBe(false)

		fs.rmSync(path.join(__dirname, '.env'))
	})
})
