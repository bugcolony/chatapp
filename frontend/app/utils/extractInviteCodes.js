const INVITE_LINK_PATTERN = /(?:^|\s)(https?:\/\/[^\s]+|\/invite\/[A-Za-z0-9]+\/?)(?=$|\s)/gi
const INVITE_PATH_PATTERN = /^\/invite\/([A-Za-z0-9]+)\/?$/

export function extractInviteCodes(content, appUrl) {
  let baseUrl

  try {
    baseUrl = new URL(appUrl)
  } catch {
    return []
  }

  const candidates = [...String(content ?? '').matchAll(INVITE_LINK_PATTERN)]
    .map((match) => match[1])

  return [...new Set(candidates.flatMap((candidate) => {
    try {
      const url = new URL(candidate, baseUrl)

      if (url.origin !== baseUrl.origin || url.search || url.hash) {
        return []
      }

      const match = url.pathname.match(INVITE_PATH_PATTERN)

      return match ? [match[1]] : []
    } catch {
      return []
    }
  }))]
}
