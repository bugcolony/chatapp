import onlinePresenceImage from '~/data/images/onlinepresence.webp'
import typingPresenceImage from '~/data/images/typingpresence.webp'

export default {
  id: 'presence',
  title: 'See who’s here',
  publishedAt: '2026-08-08',
  version: 'v0.5.0',
  description: 'Conversations now feel more alive with live member presence and typing indicators. See who is available at a glance and know when someone is writing a reply.',
  sections: [
    {
      component: 'ReleaseArticle',
      data: {
        title: 'Know who’s around',
        image: {
          src: onlinePresenceImage,
          alt: 'Server member list showing online and offline presence indicators',
        },
        paragraph: 'The member list now shows who is online and keeps the total updated in real time.',
      },
    },
    {
      component: 'ReleaseArticle',
      data: {
        title: 'See replies taking shape',
        image: {
          src: typingPresenceImage,
          alt: 'Typing indicator above the message composer showing a member writing a reply',
        },
        paragraph: 'A live typing indicator now shows who is composing a message, so you know when another reply is already on the way.',
      },
    },
    {
      component: 'ReleaseChangelist',
      data: {
        title: 'Release details',
        list: [
          { type: 'add', description: 'Added live online and offline member presence.' },
          { type: 'add', description: 'Added live typing indicators in conversations.' },
        ],
      },
    },
  ],
}
