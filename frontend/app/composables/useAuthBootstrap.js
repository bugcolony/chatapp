let bootstrapPromise = null;

export function useAuthBootstrap() {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();

  async function bootstrap() {
    if (bootstrapPromise) {
      return bootstrapPromise;
    }

    bootstrapPromise = (async () => {
      try {
        const { $apiFetch } = useNuxtApp();
        const user = await $apiFetch("/me", {
          timeout: config.public.authTimeout,
        });
        authStore.setAuthenticated(user);
      } catch (error) {
        console.error("Error occurred while fetching user data:", error);
        authStore.setGuest();
      } finally {
        bootstrapPromise = null;
      }
    })();

    return bootstrapPromise;
  }

  return { bootstrap };
}
