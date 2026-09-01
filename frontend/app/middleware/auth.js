export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore();

  await until(() => auth.isResolved).toBe(true);

  if (!auth.isAuthenticated) {
    return navigateTo("/login", { replace: true });
  }

  if (auth.needsOnboarding && to.path !== "/onboarding") {
    return navigateTo("/onboarding", { replace: true });
  }

  if (!auth.needsOnboarding && to.path === "/onboarding") {
    return navigateTo("/app", { replace: true });
  }
});
