import voiceChannelListImage from '~/data/images/voice1.webp'
import voiceCallImage from '~/data/images/voice2.webp'
import voiceCameraImage from '~/data/images/voice3.webp'
import voiceScreenShareImage from '~/data/images/voice4.webp'

export default {
  id: 'voice-channels',
  title: 'Say it out loud',
  publishedAt: '2026-08-21',
  version: 'v0.6.0',
  description: 'Voice channels are here. Drop into a call with one click, turn on your camera, share your screen, and keep the text conversation right beside you.',
  sections: [
    {
      component: 'ReleaseArticle',
      data: {
        title: 'A room you can walk into',
        image: {
          src: voiceChannelListImage,
          alt: 'Channel sidebar showing a voice channel with the members currently in the call listed beneath it',
        },
        paragraph: 'Choose Voice when creating a channel and it takes its place in the sidebar with a headset icon. Everyone already in the call is listed underneath and updates live, so you can see where the conversation is happening before you join it.',
      },
    },
    {
      component: 'ReleaseGrid',
      data: {
        title: 'Everything the call needs',
        description: 'Microphone, camera, screen share, chat, and leave all sit in one bar at the bottom of the call—close at hand, out of the way.',
        cols: 3,
        items: [
          {
            title: 'Talk and listen',
            description: 'Every participant gets a tile that lights up while they speak and shows a mic icon when they are muted.',
            image: {
              src: voiceCallImage,
              alt: 'Voice call view with a participant tile and the call control bar',
            },
          },
          {
            title: 'Turn on your camera',
            description: 'Switch on video and your tile becomes a live camera feed for the rest of the room.',
            image: {
              src: voiceCameraImage,
              alt: 'Voice call showing a participant’s live camera feed',
            },
          },
          {
            title: 'Share your screen',
            description: 'Share a window or a whole screen when it is easier to show the work than describe it.',
            image: {
              src: voiceScreenShareImage,
              alt: 'Voice call showing a shared screen with an open editor window',
            },
          },
        ],
      },
    },
    {
      component: 'ReleaseGrid',
      data: {
        title: 'Small things that keep calls comfortable',
        cols: 2,
        items: [
          {
            title: 'Chat beside the call',
            icon: 'i-lucide-message-square',
            description: 'Every voice channel comes with its own text chat. Toggle it open from the call bar to drop a link, paste a snippet, or take notes while the call carries on.',
          },
          {
            title: 'You join muted',
            icon: 'i-lucide-mic-off',
            description: 'Calls open with your microphone off, so you can arrive quietly and unmute when you are ready. Your choice carries over to the next call you join.',
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
