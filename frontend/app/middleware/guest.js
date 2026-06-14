export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore();

  await until(() => auth.isResolved).toBe(true);

  if (auth.isAuthenticated) {
    return navigateTo("/app", { replace: true });
  }
});