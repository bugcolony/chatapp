import callControlsImage from '~/data/images/call_controls_2.webp'
import cameraTileImage from '~/data/images/camera_tile.webp'
import chatMessagesImage from '~/data/images/chat_messages.webp'
import fullCallGridImage from '~/data/images/full_call_grid_wide.webp'
import screenShareTileImage from '~/data/images/screen_share_tile.webp'
import voiceSidebarImage from '~/data/images/sidebar_wide.webp'

export default {
  id: 'voice-channels',
  title: 'Say it out loud',
  publishedAt: '2026-08-21',
  version: 'v0.6.0',
  cover: {
    src: fullCallGridImage,
    alt: 'A voice call with four participant tiles—two avatars, a shared editor window, and a live camera feed—above the call control bar',
  },
  description: 'Voice channels are here. Drop into a call with one click, turn on your camera, share your screen, and keep the text conversation right beside you.',
  sections: [
    {
      component: 'ReleaseArticle',
      data: {
        title: 'A room you can walk into',
        image: {
          src: voiceSidebarImage,
          alt: 'Channel sidebar with the Standup voice channel selected and the four members currently in the call listed beneath it',
        },
        paragraph: 'Choose Voice when creating a channel and it takes its place in the sidebar with a headset icon. Everyone already in the call is listed underneath and updates live, so you can see where the conversation is happening before you join it.',
      },
    },
    {
      component: 'ReleaseGrid',
      data: {
        title: 'Show, don’t just tell',
        description: 'Turn on video or share a screen and your tile becomes the thing you are showing.',
        cols: 2,
        imageAspect: 'video',
        items: [
          {
            title: 'Turn on your camera',
            description: 'Switch on video and your tile becomes a live camera feed for the rest of the room.',
            image: {
              src: cameraTileImage,
              alt: 'Participant tile showing a live camera feed during a call',
            },
          },
          {
            title: 'Share your screen',
            description: 'Share a window or a whole screen when it is easier to show the work than describe it.',
            image: {
              src: screenShareTileImage,
              alt: 'Participant tile showing a shared editor window during a call',
            },
          },
        ],
      },
    },
    {
      component: 'ReleaseGrid',
      data: {
        title: 'Everything else stays close',
        cols: 1,
        items: [
          {
            title: 'One bar for the whole call',
            description: 'Microphone, camera, screen share, chat, and leave sit together at the bottom of the call. You join with your microphone off, so you can arrive quietly and unmute when you are ready.',
            image: {
              src: callControlsImage,
              alt: 'Call control bar with microphone, camera, screen share, chat, and leave buttons',
            },
          },
          {
            title: 'Chat beside the call',
            description: 'Every voice channel comes with its own text chat. Toggle it open from the call bar to drop a link, paste a snippet, or take notes while the call carries on.',
            image: {
              src: chatMessagesImage,
              alt: 'Two chat messages posted in a voice channel’s text conversation',
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
          { type: 'add', description: 'Added voice channels, selectable as a channel type when creating a channel.' },
          { type: 'add', description: 'Added live participant lists beneath voice channels in the sidebar.' },
          { type: 'add', description: 'Added a call view with participant tiles, speaking highlights, and mute indicators.' },
          { type: 'add', description: 'Added microphone, camera, and screen-sharing controls in the call bar.' },
          { type: 'add', description: 'Added a paired text chat for every voice channel, toggled from the call bar.' },
        ],
      },
    },
  ],
}
