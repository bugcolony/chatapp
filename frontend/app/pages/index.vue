<script setup lang="js">
definePageMeta({
  title: "Home",
});

useHead({
  title: "Chat App - Rooms built for momentum",
  meta: [
    {
      name: "description",
      content:
        "A focused team chat experience with servers, channels, invites, and a calm interface for daily collaboration.",
    },
  ],
});

const auth = useAuthStore();

const primaryCta = computed(() => ({
  label: auth.isAuthenticated ? "Open chat" : "Start chatting",
  to: auth.isAuthenticated ? "/app" : "/login",
}));

const stats = [
  { value: "Left", label: "servers and DMs" },
  { value: "Top", label: "pinned server strip" },
  { value: "Right", label: "friends panel" },
];

const panelClass =
  "min-w-0 overflow-hidden rounded-[1.45rem] border border-white/8 bg-slate-900/72 shadow-2xl shadow-black/20 backdrop-blur-xl";

const previewServers = [
  {
    name: "Mercy",
    description: "Product room",
    unread: 4,
    active: true,
    pinned: true,
    initials: "M",
    avatarClass: "bg-linear-to-br from-orange-400 to-orange-600 shadow-orange-500/25",
  },
  {
    name: "Launch",
    description: "Release work",
    unread: 12,
    active: false,
    pinned: false,
    initials: "L",
    avatarClass: "bg-linear-to-br from-indigo-400 to-indigo-600 shadow-indigo-500/25",
  },
  {
    name: "Support",
    description: "Customer desk",
    unread: 0,
    active: false,
    pinned: false,
    initials: "S",
    avatarClass: "bg-linear-to-br from-teal-400 to-teal-600 shadow-teal-500/25",
  },
  {
    name: "Ops",
    description: "Infra notes",
    unread: 2,
    active: false,
    pinned: false,
    initials: "O",
    avatarClass: "bg-linear-to-br from-yellow-400 to-yellow-600 shadow-yellow-500/25",
  },
];

const previewPinnedServers = previewServers.filter((server) => server.pinned);

const previewFriends = [
  {
    name: "Maya Chen",
    handle: "@maya",
    status: "Reviewing invites",
    initials: "MC",
    avatarClass: "bg-linear-to-br from-orange-400 to-orange-600",
    online: true,
  },
  {
    name: "Jon Bell",
    handle: "@jon",
    status: "In launch room",
    initials: "JB",
    avatarClass: "bg-linear-to-br from-sky-400 to-sky-600",
    online: true,
  },
  {
    name: "Rin Fox",
    handle: "@rin",
    status: "Away",
    initials: "RF",
    avatarClass: "bg-linear-to-br from-violet-400 to-violet-600",
    online: false,
  },
];

const features = [
  {
    icon: "i-lucide-server",
    title: "Server list",
    description:
      "The left panel mirrors the app: Servers and DMs tabs, compact server rows, pins, unread counts, and clear active state.",
  },
  {
    icon: "i-lucide-pin",
    title: "Pinned strip",
    description:
      "Pinned servers stay one click away in the center panel, with create and sidebar toggle controls around the strip.",
  },
  {
    icon: "i-lucide-users",
    title: "Friends panel",
    description:
      "The right panel keeps the user card, online count, handles, and status lines visible without crowding the workspace.",
  },
];
</script>

<template>
  <main
    class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_14%_18%,rgba(251,146,60,0.20),transparent_30rem),radial-gradient(circle_at_82%_8%,rgba(68,215,244,0.16),transparent_28rem),linear-gradient(140deg,#04100e_0%,#07110f_42%,#0d1714_100%)] text-[#f4fff9]"
  >
    <section class="relative isolate min-h-[78svh] px-5 pb-10 pt-10 sm:px-8 lg:px-12">
      <div
        class="pointer-events-none absolute inset-0 z-0 bg-[url('/images/bgpatt.webp')] bg-size-[auto_480px] bg-repeat opacity-[0.06] mix-blend-screen"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute inset-0 z-1 bg-[linear-gradient(rgba(209,250,229,0.14)_1px,transparent_1px),linear-gradient(90deg,rgba(209,250,229,0.14)_1px,transparent_1px)] bg-size-[72px_72px] mask-[linear-gradient(to_bottom,rgba(0,0,0,0.86),transparent_82%)]"
        aria-hidden="true"
      />

      <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-10 py-8 lg:grid-cols-[0.92fr_1.08fr] lg:py-12">
        <div class="max-w-176">
          <p class="mb-5 inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.12em] text-orange-400">
            <span class="size-2 rounded-full bg-orange-400 shadow-[0_0_0_0.35rem_rgba(251,146,60,0.16)]" aria-hidden="true" />
            Team chat with less drag
          </p>

          <h1 class="max-w-[11ch] font-serif text-[clamp(3.55rem,11vw,9.4rem)] font-bold leading-[0.86] tracking-normal text-[#f4fff9]">
            Rooms built for momentum.
          </h1>

          <p class="mt-7 max-w-156 text-[clamp(1.06rem,1.8vw,1.35rem)] leading-[1.7] text-emerald-50/72">
            The home screen opens into the real workspace: server tabs on the left, pinned rooms across the top, and friends on the right.
          </p>

          <div class="mt-8 flex flex-wrap gap-3">
            <UButton
              :to="primaryCta.to"
              color="primary"
              size="xl"
              trailing-icon="i-lucide-send"
              :ui="{ base: 'rounded-full px-5 py-3 shadow-2xl shadow-primary/25' }"
            >
              {{ primaryCta.label }}
            </UButton>
            <UButton
              to="#rooms"
              color="neutral"
              variant="subtle"
              size="xl"
              trailing-icon="i-lucide-arrow-down"
              :ui="{ base: 'rounded-full px-5 py-3 backdrop-blur' }"
            >
              See the flow
            </UButton>
          </div>

          <dl class="mt-10 grid max-w-xl grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Product highlights">
            <div
              v-for="stat in stats"
              :key="stat.label"
              class="min-w-0 rounded-xl border border-emerald-100/15 bg-white/4.5 p-4 backdrop-blur-xl"
            >
              <dt class="text-2xl font-black leading-none text-[#f4fff9]">
                {{ stat.value }}
              </dt>
              <dd class="mt-2 text-xs leading-tight text-emerald-50/50">
                {{ stat.label }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="relative min-w-0 lg:perspective-distant" aria-label="Chat App interface preview">
          <div class="pointer-events-none absolute inset-[-10%_2%_16%_-8%] rounded-full border border-emerald-100/15 transform-[rotate(-13deg)]" aria-hidden="true" />
          <div class="pointer-events-none absolute inset-[22%_-8%_-8%_18%] rounded-full border border-emerald-100/15 transform-[rotate(17deg)]" aria-hidden="true" />

          <div
            class="relative z-2 grid min-h-142 grid-cols-1 gap-2 overflow-hidden rounded-[1.65rem] border border-white/10 bg-[#0b1114] bg-[url('/images/bgpatt.webp')] bg-size-[auto_360px] p-2 shadow-[0_2.5rem_6rem_rgba(0,0,0,0.5)] backdrop-blur-2xl lg:grid-cols-[minmax(12.5rem,0.82fr)_minmax(18rem,1.16fr)_minmax(13rem,0.92fr)] lg:origin-center lg:transform-[rotateY(-6deg)_rotateX(3deg)]"
          >
            <aside :class="[panelClass, 'flex flex-col gap-3 p-3']" aria-label="Servers preview">
              <div class="flex gap-1 rounded-2xl border border-white/8 bg-slate-950/55 p-1">
                <span class="inline-flex min-h-9 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-xl bg-white px-2 text-xs font-black text-slate-950 shadow-lg shadow-black/20">
                  <UIcon name="i-lucide-server" class="size-4" />
                  Servers
                </span>
                <span class="inline-flex min-h-9 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-xl px-2 text-xs font-black text-slate-400">
                  <UIcon name="i-lucide-message-circle" class="size-4" />
                  DMs
                </span>
              </div>

              <div class="grid gap-1">
                <article
                  v-for="server in previewServers"
                  :key="server.name"
                  class="grid min-h-13 grid-cols-[auto_minmax(0,1fr)_auto_auto] items-center gap-2.5 rounded-lg px-2 py-2 text-slate-300 transition"
                  :class="server.active ? 'bg-white/8' : 'hover:bg-white/5'"
                >
                  <span
                    class="grid size-9 shrink-0 place-items-center rounded-md text-xs font-black text-white shadow-lg"
                    :class="server.avatarClass"
                  >
                    {{ server.initials }}
                  </span>
                  <span class="min-w-0">
                    <strong class="block truncate text-sm font-black text-white">{{ server.name }}</strong>
                    <small class="block truncate text-[11px] font-bold text-slate-500">{{ server.description }}</small>
                  </span>
                  <UIcon v-if="server.pinned" name="i-lucide-pin" class="size-3.5 text-slate-500" />
                  <em
                    v-if="server.unread"
                    class="grid h-5 min-w-5 place-items-center rounded-full bg-white/8 px-1.5 text-[10px] font-black not-italic text-slate-200"
                  >
                    {{ server.unread }}
                  </em>
                </article>
              </div>
            </aside>

            <section :class="[panelClass, 'flex min-h-88 flex-col bg-slate-950/62']" aria-label="Pinned servers preview">
              <nav class="flex flex-wrap items-center gap-2 border-b border-white/8 p-3" aria-label="Pinned server strip preview">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/8 text-white">
                  <UIcon name="i-lucide-chevron-left" class="size-4" />
                </span>
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/8 text-white">
                  <UIcon name="i-lucide-house" class="size-4" />
                </span>
                <span class="order-5 flex min-w-0 flex-1 basis-full items-center gap-2 overflow-hidden py-0.5 lg:order-0 lg:basis-auto">
                  <span
                    v-for="(server, index) in previewPinnedServers"
                    :key="server.name"
                    class="relative grid size-10 shrink-0 place-items-center rounded-xl border border-white/10 text-xs font-black text-white"
                    :class="[
                      server.avatarClass,
                      index === 0 ? 'after:absolute after:-bottom-1 after:left-1/2 after:h-1 after:w-6 after:-translate-x-1/2 after:rounded-full after:bg-orange-300' : '',
                    ]"
                  >
                    {{ server.initials }}
                    <small
                      v-if="server.unread"
                      class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-orange-500 px-1 text-[10px] font-black leading-none text-white ring-2 ring-slate-950"
                    >
                      {{ server.unread }}
                    </small>
                  </span>
                </span>
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl border border-dashed border-white/15 bg-white/6 text-slate-300">
                  <UIcon name="i-lucide-plus" class="size-4" />
                </span>
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/8 text-white">
                  <UIcon name="i-lucide-chevron-right" class="size-4" />
                </span>
              </nav>

              <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                <span class="grid size-16 place-items-center rounded-3xl border border-white/8 bg-white/6 text-slate-400">
                  <UIcon name="i-lucide-panels-top-left" class="size-8" />
                </span>
                <strong class="mt-4 text-base font-black text-white">Pick a server from the strip</strong>
                <p class="mt-2 max-w-68 text-xs leading-relaxed text-slate-500">
                  The app home keeps the center calm until a room or channel is selected.
                </p>
              </div>
            </section>

            <aside :class="[panelClass, 'flex flex-col gap-3 p-3']" aria-label="Friends preview">
              <div class="-m-3 mb-0 grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-white/8 p-3">
                <span class="relative grid size-11 place-items-center rounded-2xl bg-linear-to-br from-orange-400 to-orange-600 text-sm font-black text-white after:absolute after:bottom-0 after:right-0 after:size-3 after:rounded-full after:bg-emerald-400 after:ring-2 after:ring-slate-900">
                  G
                </span>
                <span class="min-w-0">
                  <strong class="block truncate text-sm font-black text-white">Guest</strong>
                  <small class="block truncate text-xs text-slate-500">Connected</small>
                </span>
                <span class="grid size-8 place-items-center rounded-xl bg-white/8 text-white">
                  <UIcon name="i-lucide-wifi" class="size-4" />
                </span>
              </div>

              <div class="flex items-end justify-between gap-3">
                <span class="min-w-0">
                  <small class="block text-[10px] font-black uppercase tracking-[0.2em] text-orange-200/70">Friends</small>
                  <strong class="mt-0.5 block truncate text-xl font-black text-white">{{ previewFriends.length }} total</strong>
                </span>
                <em class="shrink-0 rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-xs font-bold not-italic text-slate-200">
                  2 online
                </em>
              </div>

              <div class="grid gap-1">
                <article
                  v-for="friend in previewFriends"
                  :key="friend.name"
                  class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-3 rounded-2xl px-3 py-3 text-white transition hover:bg-white/6"
                >
                  <span
                    class="relative grid size-10 shrink-0 place-items-center rounded-2xl text-xs font-black text-white"
                    :class="[friend.avatarClass, friend.online ? '' : 'opacity-50 grayscale']"
                  >
                    {{ friend.initials }}
                    <i
                      class="absolute bottom-0 right-0 size-2.5 rounded-full ring-2 ring-slate-900"
                      :class="friend.online ? 'bg-emerald-400' : 'bg-slate-600'"
                    />
                  </span>
                  <span class="min-w-0">
                    <strong class="block truncate text-sm font-bold text-white">{{ friend.name }}</strong>
                    <small class="block truncate text-xs text-slate-500">
                      <b class="mr-1 rounded-full bg-white/8 px-1.5 py-0.5 text-[10px] text-slate-400">{{ friend.handle }}</b>
                      {{ friend.status }}
                    </small>
                  </span>
                </article>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </section>

    <section id="rooms" class="bg-[#f6faf5] px-5 py-14 text-[#0a1914] sm:px-8 lg:px-12 lg:py-20">
      <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.72fr_1.28fr]">
        <div>
          <p class="mb-5 inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.12em] text-orange-500">
            Room architecture
          </p>
          <h2 class="font-serif text-[clamp(2.25rem,5vw,5rem)] font-bold leading-none tracking-normal text-[#07110f]">
            Everything has a place before the first message lands.
          </h2>
          <p class="mt-5 max-w-lg text-base leading-relaxed text-[#0a1914]/70">
            Servers, channels, friends, and invites stay in predictable zones. The interface remains dense enough for real work while still giving conversations room to breathe.
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <article
            v-for="feature in features"
            :key="feature.title"
            class="min-w-0 rounded-[0.85rem] border border-[#0a1914]/12 bg-white/65 p-5 shadow-[0_1.1rem_3rem_rgba(7,17,15,0.08)]"
          >
            <span class="grid size-12 place-items-center rounded-[0.85rem] bg-[#07110f] text-orange-400">
              <UIcon :name="feature.icon" class="size-6" />
            </span>
            <h3 class="mt-5 text-lg font-black text-[#07110f]">
              {{ feature.title }}
            </h3>
            <p class="mt-3 text-sm leading-relaxed text-[#0a1914]/65">
              {{ feature.description }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="workflow" class="bg-[#f6faf5] px-5 pb-20 pt-2 sm:px-8 lg:px-12">
      <div class="mx-auto max-w-7xl">
        <div class="grid items-end gap-8 rounded-[1.1rem] bg-[#07110f] bg-[linear-gradient(135deg,rgba(251,146,60,0.16),transparent_45%)] p-[clamp(1.4rem,4vw,3rem)] text-[#f4fff9] shadow-[0_1.8rem_5rem_rgba(7,17,15,0.25)] lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
          <div>
            <p class="mb-5 inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.12em] text-orange-400">
              Daily workflow
            </p>
            <h2 class="font-serif text-[clamp(2.25rem,5vw,5rem)] font-bold leading-none tracking-normal text-[#f4fff9]">
              Open the app, pick a room, keep the thread moving.
            </h2>
          </div>

          <div class="grid gap-3" aria-label="Chat workflow">
            <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1 rounded-[0.85rem] border border-emerald-100/15 bg-white/5.5 p-4">
              <span class="row-span-2 font-black text-orange-400">01</span>
              <strong class="text-[#f4fff9]">Create a server</strong>
              <p class="m-0 text-sm text-emerald-50/50">Shape the shared space around the team.</p>
            </div>
            <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1 rounded-[0.85rem] border border-emerald-100/15 bg-white/5.5 p-4">
              <span class="row-span-2 font-black text-orange-400">02</span>
              <strong class="text-[#f4fff9]">Invite people</strong>
              <p class="m-0 text-sm text-emerald-50/50">Send one link and recover it after login.</p>
            </div>
            <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1 rounded-[0.85rem] border border-emerald-100/15 bg-white/5.5 p-4">
              <span class="row-span-2 font-black text-orange-400">03</span>
              <strong class="text-[#f4fff9]">Talk in channels</strong>
              <p class="m-0 text-sm text-emerald-50/50">Keep launches, support, and operations separate.</p>
            </div>
          </div>

          <UButton
            :to="primaryCta.to"
            color="primary"
            size="xl"
            trailing-icon="i-lucide-arrow-up-right"
            :ui="{ base: 'rounded-full px-5 py-3' }"
          >
            {{ primaryCta.label }}
          </UButton>
        </div>
      </div>
    </section>
  </main>
</template>
