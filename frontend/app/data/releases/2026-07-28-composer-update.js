import attachmentButtonImage from '~/data/images/attachmentbutton.webp'
import dragAndDropImage from '~/data/images/dndattach.webp'
import emojiPickerImage from '~/data/images/emojipicker.webp'
import mentionsImage from '~/data/images/mentions.webp'
import richTextImage from '~/data/images/rte.webp'

export default {
  id: 'composer-update',
  title: 'Express yourself',
  publishedAt: '2026-07-28',
  version: 'v0.3.0',
  description: 'Write richer messages with formatting, emoji, member mentions, and effortless file attachments—all from the composer.',
  sections: [
    {
      component: 'ReleaseGrid',
      data: {
        title: 'A more capable composer',
        description: 'The tools stay close to the message field without getting in the way of everyday conversation.',
        cols: 3,
        items: [
          {
            title: 'Emoji picker',
            description: 'Search and insert native emoji without leaving the composer.',
            image: {
              src: emojiPickerImage,
              alt: 'Emoji picker open above the message composer',
            },
          },
          {
            title: 'Attachment button',
            description: 'Choose a file from your device and preview it before sending.',
            image: {
              src: attachmentButtonImage,
              alt: 'Attachment button in the message composer',
            },
          },
          {
            title: 'Drag and drop',
            description: 'Drop a file directly onto the composer for a quicker upload.',
            image: {
              src: dragAndDropImage,
              alt: 'File being dragged onto the message composer',
            },
          },
          {
            title: 'Member mentions',
            description: 'Type @ to find a member and bring them into the conversation.',
            image: {
              src: mentionsImage,
              alt: 'Member mention suggestions above the message composer',
            },
          },
          {
            title: 'Rich text',
            description: 'Use familiar Markdown formatting for clearer, more expressive messages.',
            image: {
              src: richTextImage,
              alt: 'Formatted text in the message composer',
            },
          },
        ],
      },
    },
    {
      component: 'ReleaseChangelist',
      data: {
        title: 'Release details',
        list: [
          { type: 'add', description: 'Added rich-text formatting with secure Markdown rendering.' },
          { type: 'add', description: 'Added a searchable native emoji picker and jumbo emoji messages.' },
          { type: 'add', description: 'Added member mentions with @ autocomplete.' },
          { type: 'add', description: 'Added file attachments through the picker, clipboard, and drag and drop.' },
          { type: 'add', description: 'Added image previews and downloads for shared files.' },
        ],
      },
    },
  ],
}
