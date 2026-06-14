export function useAppUrl() {
  const config = useRuntimeConfig()
  const appUrl = String(config.public.appUrl || 'http://localhost').replace(/\/$/, '')

  return { appUrl }
}
