const PALETTE = [
  'ff6b6b', 'ff8e53', 'ffb347', 'ffd93d', 'f6e05e',
  '9ae66e', '48bb78', '26a69a', '14b8a6', '22d3ee',
  '38bdf8', '3b82f6', '6366f1', '7c3aed', 'a855f7',
  'd946ef', 'ec4899', 'f43f5e', 'fb7185', 'e879f9',
  '00d4ff', 'ff006e', '8338ec', '3a86ff', 'fb5607',
  'ffbe0b', '06ffa5', 'ff4d6d', '9d4edd', '4cc9f0',
].join(',')

export function serverAvatarSrc(server) {
  if (!server) return ''
  return server.image ?? `https://api.dicebear.com/9.x/icons/svg?scale=70&backgroundColor=${PALETTE}&backgroundType=gradientLinear&seed=${server.id}`
}

export function fallbackAvatarSrc(name) {
  return `https://api.dicebear.com/9.x/initials/svg?seed=${name}&scale=70&backgroundType=gradientLinear`
}

export function useServerAvatar() {
  return { serverAvatarSrc, fallbackAvatarSrc }
}
