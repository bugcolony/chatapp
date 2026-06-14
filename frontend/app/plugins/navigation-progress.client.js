import { useNProgress } from '@vueuse/integrations/useNProgress'

export default defineNuxtPlugin((nuxtApp) => {
	const progress = useNProgress(null, {
		minimum: 0.08,
		showSpinner: false,
		trickleSpeed: 120,
	})

	nuxtApp.hook('page:loading:start', () => {
		progress.start()
	})

	nuxtApp.hook('page:loading:end', () => {
		progress.done()
	})

	nuxtApp.hook('app:error', () => {
		progress.done(true)
	})
})
