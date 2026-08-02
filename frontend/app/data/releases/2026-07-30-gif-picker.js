import gifPickerImage from '~/data/images/gifpick.webp'
import releaseFeedImage from '~/data/images/releasefeed.webp'

export default {
  id: 'gif-picker',
  title: 'Find the right reaction',
  publishedAt: '2026-07-30',
  version: 'v0.4.0',
  description: 'Browse trending GIFs, search for the right response, and send it without leaving the conversation. This release also introduces a clearer home for product updates.',
  sections: [
    {
      component: 'ReleaseArticle',
      data: {
        title: 'GIFs in the composer',
        image: {
          src: gifPickerImage,
          alt: 'GIF picker open above the message composer',
        },
        paragraph: 'Browse trending GIFs or search for the right response directly from the composer. Preview a result, send it, and keep the conversation moving without switching tabs.',
      },
    },
    {
      component: 'ReleaseArticle',
      data: {
        title: 'A home for product updates',
        image: {
          src: releaseFeedImage,
          alt: 'The What’s New page showing release notes grouped by version',
        },
        paragraph: 'The new What’s New page brings each minor release into one clear timeline. It is now easier to see what changed, explore the highlights, and revisit earlier updates.',
      },
    },
  ],
}
