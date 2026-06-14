export default defineNuxtPlugin(() => {
	const config = useRuntimeConfig()
	const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

	function refreshCsrfToken() {
		return $fetch('/sanctum/csrf-cookie', {
			baseURL: apiBase,
			credentials: 'include',
		})
	}

	const $apiFetch = $fetch.create({
		baseURL: `${apiBase}/api/v1`,
		credentials: 'include',
		headers: {
			Accept: 'application/json',
		},

		onRequest({options}) {
			const method = (options.method ?? 'GET').toUpperCase()

			if (method !== 'GET' && method !== 'HEAD') {
				const xsrf = useCookie('XSRF-TOKEN').value

				if (xsrf) {
					options.headers.set('X-XSRF-TOKEN', decodeURIComponent(xsrf))
				}
			}
		},

		async onResponseError({request, response, options}) {
			if (response.status === 419 && !options._csrfRetry) {
				await refreshCsrfToken()
				options._csrfRetry = true
				return $apiFetch(request, options)
			}

			if (response.status === 401) {
				const auth = useAuthStore()

				if (auth.isAuthenticated) {
					auth.setGuest()
					await navigateTo('/login')
				}
			}
		},
	})

	return {
		provide: {
			apiFetch: $apiFetch,
		},
	}
})
