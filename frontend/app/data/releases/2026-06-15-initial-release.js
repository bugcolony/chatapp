export default {
  id: 'initial-release',
  title: 'The first rooms are open',
  publishedAt: '2026-06-15',
  version: 'v0.1.0',
  description: 'The first development release brings the core chat experience together with servers, channels, live conversations, invitations, and member navigation.',
  sections: [
    {
      component: 'ReleaseChangelist',
      data: {
        title: 'The foundation',
        description: 'The core pieces needed to create a space and start a conversation.',
        list: [
          { type: 'add', description: 'Added secure sign-in, session recovery, and protected app routes.' },
          { type: 'add', description: 'Added server creation, navigation, pinning, and leave controls.' },
          { type: 'add', description: 'Added channel conversations with message history and real-time updates.' },
          { type: 'add', description: 'Added shareable server invitations that survive the sign-in flow.' },
          { type: 'add', description: 'Added member and friends panels for navigating the workspace.' },
        ],
      },
    },
  ],
}
