import assert from 'node:assert/strict'
import test from 'node:test'
import {
  createKlipyRequest,
  createKlipyGifMarkdown,
  isAllowedKlipyMediaUrl,
  normalizeKlipyGif,
  normalizeKlipyLocale,
  normalizeKlipyResponse,
} from '../app/utils/klipyGif.js'

test('normalizes a native KLIPY response and preferred renditions', () => {
  const response = normalizeKlipyResponse({
    result: true,
    data: {
      data: [{
        id: 42,
        slug: 'celebration-42',
        title: 'Celebration',
        type: 'gif',
        file: {
          md: {
            gif: {
              url: 'https://static.klipy.com/celebration-md.gif',
              width: 640,
              height: 360,
            },
          },
          sm: {
            webp: {
              url: 'https://static.klipy.com/celebration-sm.webp',
              width: 320,
              height: 180,
            },
          },
        },
      }],
      current_page: 2,
      has_next: true,
    },
  })

  assert.equal(response.currentPage, 2)
  assert.equal(response.hasNext, true)
  assert.deepEqual(response.gifs, [{
    id: '42',
    slug: 'celebration-42',
    title: 'Celebration',
    previewUrl: 'https://static.klipy.com/celebration-sm.webp',
    url: 'https://static.klipy.com/celebration-md.gif',
    width: 320,
    height: 180,
    providerUrl: 'https://klipy.com/gifs/celebration-42',
  }])
})

test('rejects malformed successful responses', () => {
  assert.throws(
    () => normalizeKlipyResponse({ result: true, data: {} }),
    /KLIPY_RESPONSE_INVALID/,
  )
  assert.throws(
    () => normalizeKlipyResponse({ result: false, data: { data: [] } }),
    /KLIPY_RESPONSE_INVALID/,
  )
})

test('drops ads, incomplete items, and insecure media URLs', () => {
  assert.equal(normalizeKlipyGif({ type: 'ad', file: {} }), null)
  assert.equal(normalizeKlipyGif({ type: 'gif', file: {} }), null)
  assert.equal(normalizeKlipyGif({
    type: 'gif',
    file: {
      sm: {
        gif: {
          url: 'http://media.klipy.com/insecure.gif',
          width: 100,
          height: 100,
        },
      },
    },
  }), null)
})

test('creates safe markdown only for HTTPS GIF URLs', () => {
  assert.equal(
    createKlipyGifMarkdown({
      title: 'Happy [dance]',
      url: 'https://static1.klipy.com/reaction.webp',
    }),
    '![Happy \\[dance\\] — GIF from KLIPY](<https://static1.klipy.com/reaction.webp>)',
  )
  assert.equal(createKlipyGifMarkdown({ url: 'javascript:alert(1)' }), '')
  assert.equal(
    createKlipyGifMarkdown({ url: 'https://attacker.example/tracker.gif' }),
    '',
  )
})

test('builds native v1 search and trending requests', () => {
  assert.deepEqual(createKlipyRequest({
    apiKey: 'app key',
    query: 'happy dance',
    page: 3,
    language: 'en-US',
  }), {
    url: 'https://api.klipy.com/api/v1/app%20key/gifs/search',
    query: {
      page: 3,
      per_page: 24,
      q: 'happy dance',
      locale: 'en',
      content_filter: 'high',
      format_filter: 'gif,webp',
    },
  })

  assert.equal(
    createKlipyRequest({ apiKey: 'key' }).url,
    'https://api.klipy.com/api/v1/key/gifs/trending',
  )
})

test('allows only documented KLIPY media hosts', () => {
  assert.equal(
    isAllowedKlipyMediaUrl('https://static.klipy.com/reaction.gif'),
    true,
  )
  assert.equal(
    isAllowedKlipyMediaUrl('https://static2.klipy.com/reaction.webp'),
    true,
  )
  assert.equal(
    isAllowedKlipyMediaUrl('https://static.klipy.co/reaction.webp'),
    true,
  )
  assert.equal(
    isAllowedKlipyMediaUrl('https://klipy.com/reaction.gif'),
    false,
  )
  assert.equal(
    isAllowedKlipyMediaUrl('https://static.klipy.com.attacker.example/a.gif'),
    false,
  )
  assert.equal(
    isAllowedKlipyMediaUrl('https://static.klipy.com:444/a.gif'),
    false,
  )
})

test('normalizes browser language tags for KLIPY', () => {
  assert.equal(normalizeKlipyLocale('en-US'), 'en')
  assert.equal(normalizeKlipyLocale('lv'), 'lv')
  assert.equal(normalizeKlipyLocale(), undefined)
})
