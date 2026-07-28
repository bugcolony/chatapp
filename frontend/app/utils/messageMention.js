import Mention from '@tiptap/extension-mention'

const MENTION_TOKEN = /^\[@([1-9]\d{0,17})]/
const MENTION_TOKEN_SEARCH = /\[@[1-9]\d{0,17}]/

function mentionAttributes(id, resolveLabel) {
  const normalizedId = String(id)

  return {
    id: normalizedId,
    label: resolveLabel(normalizedId) ?? null,
    mentionSuggestionChar: '@',
  }
}

export function createMessageMentionExtension(resolveLabel = () => null) {
  return Mention.extend({
    markdownTokenizer: {
      name: 'mention',
      level: 'inline',
      start: source => source.search(MENTION_TOKEN_SEARCH),
      tokenize: source => {
        const match = MENTION_TOKEN.exec(source)

        if (!match) return undefined

        return {
          type: 'mention',
          raw: match[0],
          mentionId: match[1],
        }
      },
    },

    parseMarkdown(token, helpers) {
      return helpers.createNode(
        'mention',
        mentionAttributes(token.mentionId, resolveLabel),
      )
    },

    renderMarkdown(node) {
      const id = String(node.attrs?.id ?? '')

      return /^\d+$/.test(id) ? `[@${id}]` : ''
    },

    addProseMirrorPlugins() {
      return []
    },
  }).configure({
    HTMLAttributes: {
      class: 'mention',
    },
  })
}

export function installMessageMentionMarkdown(md, resolveLabel = id => id) {
  md.inline.ruler.before('link', 'message_mention', (state, silent) => {
    const match = MENTION_TOKEN.exec(state.src.slice(state.pos))

    if (!match) return false

    if (!silent) {
      const token = state.push('message_mention', 'span', 0)
      token.meta = { id: match[1] }
    }

    state.pos += match[0].length

    return true
  })

  md.renderer.rules.message_mention = (tokens, index) => {
    const id = String(tokens[index].meta.id)
    const label = String(resolveLabel(id) ?? id)

    return `<span class="message-mention" data-user-id="${md.utils.escapeHtml(id)}">@${md.utils.escapeHtml(label)}</span>`
  }
}
