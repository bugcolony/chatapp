import Emoji, {
  emojis,
  shortcodeToEmoji,
} from '@tiptap/extension-emoji'

function withoutFallbackImage(item) {
  const nativeItem = { ...item }
  Reflect.deleteProperty(nativeItem, 'fallbackImage')
  return nativeItem
}

function humanizeEmojiName(name) {
  return name
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

function getSearchAliases(item) {
  return (item.emoticons ?? []).flatMap(emoticon =>
    emoticon.startsWith(':')
      ? [emoticon, emoticon.slice(1)]
      : [emoticon],
  )
}

const nativeEmojis = emojis.map(withoutFallbackImage)

export const messageEmojiMenuItems = nativeEmojis.map(item => ({
  ...item,
  name: humanizeEmojiName(item.name),
  searchAliases: getSearchAliases(item),
}))

const NativeEmoji = Emoji.extend({
  renderMarkdown(node) {
    const item = shortcodeToEmoji(node.attrs.name, nativeEmojis)
    return item?.emoji ?? `:${node.attrs.name}:`
  },
})

export const messageEmojiExtension = NativeEmoji.configure({
  emojis: nativeEmojis,
  enableEmoticons: true,
  HTMLAttributes: {
    class: 'message-emoji',
  },
  suggestion: {
    shouldShow: () => false,
  },
})
