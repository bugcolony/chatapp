export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore();
  const { bootstrap } = useAuthBootstrap();

  if (!auth.isResolved) {
    bootstrap();
  }
});
