export const useApiFetch = createUseFetch((currentOptions) => ({
	...currentOptions,
	$fetch: useNuxtApp().$apiFetch,
}))