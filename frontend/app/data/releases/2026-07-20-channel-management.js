import channelImage from '~/data/images/channelupd.webp'

export default {
  id: 'channel-management',
  title: 'Shape your server',
  publishedAt: '2026-07-20',
  version: 'v0.2.0',
  description: 'A new home page introduces the workspace, server owners gain full control over channels and categories, and live updates keep everyone in sync.',
  cover: {
    src: channelImage,
    alt: 'Channel management controls in the server sidebar',
  },
  sections: [
    {
      component: 'ReleaseChangelist',
      data: {
        title: 'Release details',
        list: [
          { type: 'add', description: 'Added a responsive home page with a detailed workspace preview and direct path into chat.' },
          { type: 'add', description: 'Added controls to create, rename, move, and delete text channels.' },
          { type: 'add', description: 'Added category creation and editing for grouping related channels.' },
          { type: 'add', description: 'Added channel and category actions through a compact context menu.' },
          { type: 'add', description: 'Added real-time channel creation, update, and deletion events.' },
          { type: 'add', description: 'Added notification sounds for new incoming messages.' },
          { type: 'changed', description: 'Pinned servers now stay synchronized with account preferences.' },
          { type: 'fixed', description: 'Improved frontend startup reliability and reduced development overhead.' },
        ],
      },
    },
  ],
}
