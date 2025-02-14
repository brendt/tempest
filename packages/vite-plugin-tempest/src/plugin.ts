import fs from 'node:fs'
import type { AddressInfo } from 'node:net'
import os from 'node:os'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import colors from 'picocolors'
import type { Plugin, ResolvedConfig, UserConfig } from 'vite'
import { loadEnv } from 'vite'
import type { InputOption } from 'rollup'
import type { DevelopmentServerUrl, TempestViteConfiguration } from './types'
import { loadTempestConfiguration } from './config'
import { isIpv6 } from './utils'

const TEMPEST_ORIGIN_PLACEHOLDER = 'http://__tempest_placeholder__.test'

let exitHandlersBound = false

export default function tempest(): Plugin {
	let viteDevServerUrl: DevelopmentServerUrl
	let bridgeFilePath: string
	let resolvedConfig: ResolvedConfig
	let userConfig: UserConfig
	let tempestConfig: TempestViteConfiguration

	const defaultAliases: Record<string, string> = {
		'@': '/src',
	}

	return {
		name: 'tempest',
		enforce: 'post',
		config: async (config, { command, mode }) => {
			tempestConfig = await loadTempestConfiguration()
			userConfig = config

			const ssr = !!userConfig.build?.ssr
			const env = loadEnv(mode, userConfig.envDir || process.cwd(), '')
			const assetUrl = env.ASSET_URL ?? ''
			const serverConfig = command === 'serve'
				? (resolveDevelopmentEnvironmentServerConfig() ?? resolveEnvironmentServerConfig(env))
				: undefined

			ensureCommandShouldRunInEnvironment(command, env)

			return {
				base: userConfig.base ?? (command === 'build' ? resolveBase(tempestConfig, assetUrl) : ''),
				publicDir: userConfig.publicDir ?? false,
				build: {
					manifest: userConfig.build?.manifest ?? (ssr ? false : tempestConfig.manifest),
					outDir: userConfig.build?.outDir ?? resolveOutDir(tempestConfig),
					rollupOptions: {
						input: userConfig.build?.rollupOptions?.input ?? resolveInput(tempestConfig),
					},
					assetsInlineLimit: userConfig.build?.assetsInlineLimit ?? 0,
				},
				server: {
					origin: userConfig.server?.origin ?? TEMPEST_ORIGIN_PLACEHOLDER,
					cors: userConfig.server?.cors ?? {
						origin: userConfig.server?.origin ?? [
							env.BASE_URI,
							/^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/,
							/^https?:\/\/.*\.test(:\d+)?$/,
						].filter(Boolean),
					},
					...(serverConfig
						? {
								host: userConfig.server?.host ?? serverConfig.host,
								hmr: userConfig.server?.hmr === false
									? false
									: {
											...serverConfig.hmr,
											...(userConfig.server?.hmr === true ? {} : userConfig.server?.hmr),
										},
								https: userConfig.server?.https ?? serverConfig.https,
							}
						: undefined),
				},
				resolve: {
					alias: Array.isArray(userConfig.resolve?.alias)
						? [
								...userConfig.resolve?.alias ?? [],
								...Object.keys(defaultAliases).map((alias) => ({
									find: alias,
									replacement: defaultAliases[alias],
								})),
							]
						: {
								...defaultAliases,
								...userConfig.resolve?.alias,
							},
				},
			}
		},
		configResolved(config) {
			resolvedConfig = config
		},
		transform(code) {
			if (resolvedConfig.command === 'serve') {
				return code.replaceAll(TEMPEST_ORIGIN_PLACEHOLDER, viteDevServerUrl)
			}
		},
		configureServer(server) {
			const envDir = resolvedConfig.envDir || process.cwd()
			const appUrl = loadEnv(resolvedConfig.mode, envDir, 'BASE_URI').BASE_URI

			server.httpServer?.once('listening', () => {
				const address = server.httpServer?.address()

				const isAddressInfo = (x: string | AddressInfo | null | undefined): x is AddressInfo => typeof x === 'object'
				if (isAddressInfo(address)) {
					bridgeFilePath = path.join('public', tempestConfig.bridge_file_name)
					viteDevServerUrl = userConfig.server?.origin
						? userConfig.server.origin as DevelopmentServerUrl
						: resolveDevServerUrl(address, server.config)

					fs.writeFileSync(bridgeFilePath, JSON.stringify({
						url: `${viteDevServerUrl}${server.config.base.replace(/\/$/, '')}`,
					}))

					setTimeout(() => {
						server.config.logger.info(`\n  ${colors.magenta(`${colors.bold('TEMPEST')} ${tempestVersion()}`)}  ${colors.dim('plugin')} ${colors.bold(`v${pluginVersion()}`)}`)
						server.config.logger.info('')

						if (appUrl) {
							server.config.logger.info(`  ${colors.green('➜')}  ${colors.bold('URL')}: ${colors.cyan(appUrl.replace(/:(\d+)/, (_, port) => `:${colors.bold(port)}`))}`)
						} else {
							server.config.logger.info(`  ${colors.magenta('➜')}  ${colors.yellow(`No ${colors.bold('BASE_URI')} specified in ${colors.bold('.env')}`)}.`)
						}

						if (typeof resolvedConfig.server.https === 'object' && typeof resolvedConfig.server.https.key === 'string') {
							if (resolvedConfig.server.https.key.startsWith(herdMacConfigPath()) || resolvedConfig.server.https.key.startsWith(herdWindowsConfigPath())) {
								server.config.logger.info(`  ${colors.green('➜')}  Using Herd certificate to secure Vite.`)
							}

							if (resolvedConfig.server.https.key.startsWith(valetMacConfigPath()) || resolvedConfig.server.https.key.startsWith(valetLinuxConfigPath())) {
								server.config.logger.info(`  ${colors.green('➜')}  Using Valet certificate to secure Vite.`)
							}
						}
					}, 100)
				}
			})

			if (!exitHandlersBound) {
				const clean = () => {
					if (fs.existsSync(bridgeFilePath)) {
						fs.rmSync(bridgeFilePath)
					}
				}

				process.on('exit', clean)
				process.on('SIGINT', () => process.exit())
				process.on('SIGTERM', () => process.exit())
				process.on('SIGHUP', () => process.exit())

				exitHandlersBound = true
			}

			return () => server.middlewares.use((req, res, next) => {
				if (req.url === '/index.html') {
					res.writeHead(302, { Location: appUrl })
					res.end()
				}

				next()
			})
		},
	}
}

/**
 * Validate the command can run in the given environment.
 */
function ensureCommandShouldRunInEnvironment(command: 'build' | 'serve', env: Record<string, string>): void {
	if (command === 'build' || [1, '1', true, 'true'].includes(env.TEMPEST_BYPASS_ENV_CHECK)) {
		return
	}

	if (typeof env.CI !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server in CI environments. You should build your assets for production instead. To disable this ENV check you may set TEMPEST_BYPASS_ENV_CHECK=true')
	}

	if (typeof env.LARAVEL_FORGE !== 'undefined') {
		throw new TypeError('You should not run the Vite HMR server in your Forge deployment script. You should build your assets for production instead. To disable this ENV check you may set TEMPEST_BYPASS_ENV_CHECK=true')
	}
}

/**
 * The version of Tempest being run.
 */
function tempestVersion(): string {
	try {
		const composer = JSON.parse(fs.readFileSync('composer.lock').toString())
		return composer.packages?.find((composerPackage: { name: string }) => composerPackage.name === 'tempest/framework')?.version ?? ''
	} catch {
		return ''
	}
}

/**
 * The version of the Tempest Vite plugin being run.
 */
function pluginVersion(): string {
	try {
		return JSON.parse(fs.readFileSync(path.join(dirname(), '../package.json')).toString())?.version
	} catch {
		return ''
	}
}

/**
 * Resolve the Vite base option from the configuration.
 */
function resolveBase(config: TempestViteConfiguration, assetUrl: string): string {
	return `${assetUrl + (!assetUrl.endsWith('/') ? '/' : '') + config.build_directory}/`
}

/**
 * Resolve the Vite input path from the configuration.
 */
function resolveInput(config: TempestViteConfiguration): InputOption | undefined {
	return config.entrypoints
}

/**
 * Resolve the Vite outDir path from the configuration.
 */
function resolveOutDir(config: TempestViteConfiguration): string | undefined {
	return path.join('public', config.build_directory)
}

/**
 * Resolve the dev server URL from the server address and configuration.
 */
function resolveDevServerUrl(address: AddressInfo, config: ResolvedConfig): DevelopmentServerUrl {
	const configHmrProtocol = typeof config.server.hmr === 'object' ? config.server.hmr.protocol : null
	const clientProtocol = configHmrProtocol ? (configHmrProtocol === 'wss' ? 'https' : 'http') : null
	const serverProtocol = config.server.https ? 'https' : 'http'
	const protocol = clientProtocol ?? serverProtocol

	const configHmrHost = typeof config.server.hmr === 'object' ? config.server.hmr.host : null
	const configHost = typeof config.server.host === 'string' ? config.server.host : null
	const serverAddress = isIpv6(address) ? `[${address.address}]` : address.address
	const host = configHmrHost ?? configHost ?? serverAddress

	const configHmrClientPort = typeof config.server.hmr === 'object' ? config.server.hmr.clientPort : null
	const port = configHmrClientPort ?? address.port

	return `${protocol}://${host}:${port}`
}

/**
 * Resolve the server config from the environment.
 */
function resolveEnvironmentServerConfig(env: Record<string, string>): {
	hmr?: { host: string }
	host?: string
	https?: { cert: Buffer, key: Buffer }
} | undefined {
	if (!env.VITE_DEV_SERVER_KEY && !env.VITE_DEV_SERVER_CERT) {
		return
	}

	if (!fs.existsSync(env.VITE_DEV_SERVER_KEY) || !fs.existsSync(env.VITE_DEV_SERVER_CERT)) {
		throw new Error(`Unable to find the certificate files specified in your environment. Ensure you have correctly configured VITE_DEV_SERVER_KEY: [${env.VITE_DEV_SERVER_KEY}] and VITE_DEV_SERVER_CERT: [${env.VITE_DEV_SERVER_CERT}].`)
	}

	const host = resolveHostFromEnv(env)

	if (!host) {
		throw new Error(`Unable to determine the host from the environment's BASE_URI: [${env.BASE_URI}].`)
	}

	return {
		hmr: { host },
		host,
		https: {
			key: fs.readFileSync(env.VITE_DEV_SERVER_KEY),
			cert: fs.readFileSync(env.VITE_DEV_SERVER_CERT),
		},
	}
}

/**
 * Resolve the host name from the environment.
 */
function resolveHostFromEnv(env: Record<string, string>): string | undefined {
	if (env.VITE_DEV_SERVER_KEY) {
		return env.VITE_DEV_SERVER_KEY
	}

	try {
		return new URL(env.BASE_URI).host
	} catch {
	}
}

/**
 * Resolve the Herd or Valet server config for the given host.
 */
function resolveDevelopmentEnvironmentServerConfig(): {
	hmr?: { host: string }
	host?: string
	https?: { cert: string, key: string }
} | undefined {
	const configPath = determineDevelopmentEnvironmentConfigPath()

	if (!configPath) {
		return
	}

	const host = resolveDevelopmentEnvironmentHost(configPath)

	if (!host) {
		return
	}

	const keyPath = path.resolve(configPath, 'Certificates', `${host}.key`)
	const certPath = path.resolve(configPath, 'Certificates', `${host}.crt`)

	if (!fs.existsSync(keyPath) || !fs.existsSync(certPath)) {
		const tip = configPath === herdMacConfigPath() || configPath === herdWindowsConfigPath()
		 ? 'Ensure you have secured the site via the Herd UI.'
		 : `Ensure you have secured the site by running \`valet secure ${host}\`.`

		 console.warn(`Unable to find certificate files for your host [${host}] in the [${configPath}/Certificates] directory. ${tip}`)
		 return
	}

	return {
		hmr: { host },
		host,
		https: {
			key: keyPath,
			cert: certPath,
		},
	}
}

/**
 * Resolve the path to the Herd or Valet configuration directory.
 */
function determineDevelopmentEnvironmentConfigPath(): string | undefined {
	if (fs.existsSync(herdMacConfigPath())) {
		return herdMacConfigPath()
	}

	if (fs.existsSync(herdWindowsConfigPath())) {
		return herdWindowsConfigPath()
	}

	if (fs.existsSync(valetMacConfigPath())) {
		return valetMacConfigPath()
	}

	if (fs.existsSync(valetLinuxConfigPath())) {
		return valetLinuxConfigPath()
	}
}

/**
 * Resolves the Herd or Valet host for the current directory.
 */
function resolveDevelopmentEnvironmentHost(configPath: string): string | undefined {
	const configFile = path.resolve(configPath, 'config.json')

	if (!fs.existsSync(configFile)) {
		return
	}

	const config: { tld: string } = JSON.parse(fs.readFileSync(configFile, 'utf-8'))

	return `${path.basename(process.cwd())}.${config.tld}`
}

/**
 * The directory of the current file.
 */
function dirname(): string {
	return fileURLToPath(new URL('.', import.meta.url))
}

/**
 * Herd's Mac configuration directory.
 */
function herdMacConfigPath(): string {
	return path.resolve(os.homedir(), 'Library', 'Application Support', 'Herd', 'config', 'valet')
}

/**
 * Herd's Windows configuration directory.
 */
function herdWindowsConfigPath(): string {
	return path.resolve(os.homedir(), '.config', 'herd', 'config', 'valet')
}

/**
 * Valet's Mac configuration directory.
 */
function valetMacConfigPath(): string {
	return path.resolve(os.homedir(), '.config', 'valet')
}

/**
 * Valet Linux's configuration directory.
 */
function valetLinuxConfigPath(): string {
	return path.resolve(os.homedir(), '.valet')
}
