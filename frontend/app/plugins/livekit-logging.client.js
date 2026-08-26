import {LogLevel, setLogLevel} from 'livekit-client'

export default defineNuxtPlugin(() => {
	setLogLevel(import.meta.dev ? LogLevel.info : LogLevel.warn)
})
