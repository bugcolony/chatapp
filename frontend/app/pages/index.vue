<script setup lang="js">
definePageMeta({
  title: 'Home',
  layout: 'naked',
})

useSeoMeta({
  title: 'Blueberry Chat',
  description: 'A shared place for teams, friends, and communities to talk and catch up.',
})

const auth = useAuthStore()

const primaryCta = computed(() => ({
  label: auth.isAuthenticated ? 'Open chat' : 'Sign in',
  to: auth.isAuthenticated ? '/app' : '/login',
  icon: auth.isAuthenticated ? 'i-lucide-arrow-up-right' : 'i-lucide-message-circle-more',
}))

const principles = [
  {
    number: '01',
    title: 'Catch up',
    description: 'Open the app and continue where you left off.',
  },
  {
    number: '02',
    title: 'Stay organized',
    description: 'Keep related conversations together without overcomplicating things.',
  },
  {
    number: '03',
    title: 'Talk about anything',
    description: 'Work, hobbies, plans, random updates—or all of the above.',
  },
]
</script>

<template>
  <div class="dark min-h-screen overflow-x-clip bg-default text-default [--ui-primary:var(--ui-color-secondary-400)] [--ui-secondary:var(--ui-color-primary-400)] [color-scheme:dark]">
    <header class="relative z-30 border-b border-muted bg-default/80 backdrop-blur-xl">
      <UContainer class="flex h-18 items-center justify-between gap-4">
        <NuxtLink
          to="/"
          class="group inline-flex items-center gap-2.5 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
          aria-label="Blueberry Chat home"
        >
          <span class="grid size-9 place-items-center rounded-[0.8rem] bg-primary text-inverted shadow-lg shadow-primary/20 transition-transform group-hover:-rotate-6">
            <UIcon name="i-lucide-message-circle-more" class="size-5" />
          </span>
          <span class="text-base font-black tracking-[-0.03em] text-highlighted [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif]">
            Blueberry Chat
          </span>
        </NuxtLink>

        <nav class="flex items-center gap-1 sm:gap-2" aria-label="Primary navigation">
          <UButton
            to="/updates"
            label="What’s new"
            color="neutral"
            variant="ghost"
            class="hidden rounded-full md:inline-flex"
          />
          <UButton
            :to="primaryCta.to"
            :label="auth.isAuthenticated ? 'Open chat' : 'Sign in'"
            color="neutral"
            variant="soft"
            trailing-icon="i-lucide-arrow-up-right"
            class="hidden rounded-full sm:inline-flex"
          />
          <UButton
            :to="primaryCta.to"
            :icon="auth.isAuthenticated ? 'i-lucide-messages-square' : 'i-lucide-log-in'"
            :aria-label="auth.isAuthenticated ? 'Open chat' : 'Sign in'"
            color="neutral"
            variant="soft"
            class="rounded-full sm:hidden"
          />
        </nav>
      </UContainer>
    </header>

    <main>
      <section class="relative isolate min-h-[calc(100svh-4.5rem)]">
        <div class="pointer-events-none absolute -left-32 top-0 -z-10 size-[32rem] rounded-full bg-[radial-gradient(circle,var(--ui-primary)_0%,transparent_68%)] opacity-20 blur-[1px]" aria-hidden="true" />
        <div class="pointer-events-none absolute -right-40 bottom-0 -z-10 size-[38rem] rounded-full bg-[radial-gradient(circle,var(--ui-secondary)_0%,transparent_68%)] opacity-20 blur-[1px]" aria-hidden="true" />

        <UContainer class="grid min-h-[calc(100svh-4.5rem)] min-w-0 items-center gap-12 py-16 lg:grid-cols-[minmax(0,1.04fr)_minmax(24rem,0.96fr)] lg:py-20">
          <div class="relative z-10 min-w-0 max-w-3xl">
            <h1 class="max-w-[10ch] text-[clamp(3.5rem,10vw,8.4rem)] font-black leading-[0.84] tracking-[-0.075em] text-highlighted [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif]">
              Chat with
              <span class="block text-balance text-primary">your group.</span>
            </h1>

            <p class="mt-8 max-w-2xl text-lg leading-8 text-muted sm:text-xl sm:leading-9">
              Blueberry Chat is a shared place for teams, friends, and communities to talk and catch up.
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-3">
              <UButton
                :to="primaryCta.to"
                :label="primaryCta.label"
                :trailing-icon="primaryCta.icon"
                color="primary"
                size="xl"
                class="rounded-full px-5 shadow-xl shadow-primary/20"
              />
              <UButton
                to="/updates"
                label="What’s new"
                trailing-icon="i-lucide-arrow-right"
                color="neutral"
                variant="ghost"
                size="xl"
                class="rounded-full px-4"
              />
            </div>

          </div>

          <div class="relative mx-auto w-full max-w-150 py-4" aria-hidden="true">
            <div class="relative aspect-square w-full">
              <span class="absolute inset-[4%] animate-spin rounded-[48%_52%_45%_55%] border border-default [animation-duration:30s] motion-reduce:animate-none">
                <i class="absolute left-[7%] top-[12%] size-3 rounded-full bg-primary shadow-[0_0_0_0.4rem_color-mix(in_srgb,var(--ui-primary)_14%,transparent)]" />
              </span>
              <span class="absolute inset-[18%] animate-spin rounded-[54%_46%_58%_42%] border border-default [animation-direction:reverse] [animation-duration:22s] motion-reduce:animate-none">
                <i class="absolute bottom-[21%] right-[4%] size-3 rounded-full bg-secondary shadow-[0_0_0_0.4rem_color-mix(in_srgb,var(--ui-secondary)_14%,transparent)]" />
              </span>

              <div class="absolute inset-[27%] grid rotate-3 place-items-center rounded-[37%_63%_55%_45%] bg-primary text-inverted shadow-2xl shadow-primary/25">
                <span class="-translate-y-3 text-[clamp(5rem,13vw,9rem)] font-black tracking-[-0.12em] [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif]">...</span>
              </div>

              <span class="absolute left-[2%] top-[25%] rotate-[-8deg] rounded-full border border-muted bg-elevated px-4 py-2 text-sm font-bold text-toned shadow-lg">
                teams
              </span>
              <span class="absolute right-[2%] top-[17%] rotate-6 rounded-[1.15rem] bg-secondary px-4 py-3 text-sm font-black text-inverted shadow-lg">
                friends
              </span>
              <span class="absolute bottom-[12%] left-[13%] rotate-3 rounded-[1.15rem] border border-muted bg-elevated px-4 py-3 text-sm font-bold text-toned shadow-lg">
                communities
              </span>
              <span class="absolute bottom-[5%] right-[12%] grid size-21 -rotate-6 place-items-center rounded-full border border-default bg-default text-3xl font-black text-primary shadow-xl [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif]">
                :)
              </span>

              <UIcon name="i-lucide-asterisk" class="absolute right-[17%] top-[3%] size-8 animate-pulse text-primary motion-reduce:animate-none" />
              <UIcon name="i-lucide-sparkle" class="absolute bottom-[26%] right-[2%] size-6 animate-pulse text-secondary [animation-delay:-1s] motion-reduce:animate-none" />
            </div>
          </div>
        </UContainer>
      </section>

      <section id="why" class="relative scroll-mt-18 py-24 sm:py-30">
        <UContainer>
          <div class="grid gap-16 lg:grid-cols-[minmax(0,0.9fr)_minmax(24rem,1.1fr)] lg:gap-24">
            <div class="lg:sticky lg:top-28 lg:self-start">
              <p class="mb-5 font-mono text-xs font-bold uppercase tracking-[0.18em] text-primary">
                What it’s for
              </p>
              <h2 class="max-w-[11ch] text-5xl font-black leading-[0.94] tracking-[-0.06em] text-highlighted [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif] sm:text-7xl">
                Everyday group conversations.
              </h2>
              <p class="mt-6 max-w-lg text-lg leading-8 text-muted">
                Use Blueberry Chat for whatever your group is already talking about.
              </p>
            </div>

            <ol class="border-t border-default">
              <li
                v-for="principle in principles"
                :key="principle.number"
                class="group grid gap-4 border-b border-default py-8 sm:grid-cols-[4rem_minmax(0,1fr)] sm:py-10"
              >
                <span class="font-mono text-xs font-bold text-primary">{{ principle.number }}</span>
                <div>
                  <h3 class="text-2xl font-black tracking-[-0.04em] text-highlighted transition-transform [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif] group-hover:translate-x-1 sm:text-3xl">
                    {{ principle.title }}
                  </h3>
                  <p class="mt-3 max-w-xl text-base leading-7 text-muted sm:text-lg">
                    {{ principle.description }}
                  </p>
                </div>
              </li>
            </ol>
          </div>
        </UContainer>
      </section>

      <section class="px-4 pb-4 sm:px-6 sm:pb-6">
        <div class="relative isolate mx-auto max-w-[92rem] overflow-hidden rounded-[2rem] border border-default bg-elevated px-6 py-16 text-default sm:px-10 sm:py-20 lg:px-16">
          <div class="relative grid items-end gap-10 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>
              <p class="mb-4 font-mono text-xs font-bold uppercase tracking-[0.18em] text-primary">
                Blueberry Chat
              </p>
              <h2 class="max-w-[12ch] text-5xl font-black leading-[0.92] tracking-[-0.06em] [font-family:ui-rounded,'Arial_Rounded_MT_Bold','Trebuchet_MS',sans-serif] sm:text-7xl">
                Open Blueberry Chat.
              </h2>
              <p class="mt-5 text-base text-muted sm:text-lg">
                Sign in to continue.
              </p>
            </div>

            <UButton
              :to="primaryCta.to"
              :label="auth.isAuthenticated ? 'Open chat' : 'Sign in'"
              trailing-icon="i-lucide-arrow-up-right"
              color="primary"
              size="xl"
              class="w-fit rounded-full px-6"
            />
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-muted">
      <UContainer class="flex flex-col gap-5 py-8 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-message-circle-more" class="size-4 text-primary" />
          <span>Blueberry Chat</span>
        </div>
        <nav class="flex flex-wrap items-center gap-x-5 gap-y-2" aria-label="Footer navigation">
          <NuxtLink to="/updates" class="transition-colors hover:text-highlighted">What’s new</NuxtLink>
          <NuxtLink :to="primaryCta.to" class="transition-colors hover:text-highlighted">
            {{ auth.isAuthenticated ? 'Open chat' : 'Sign in' }}
          </NuxtLink>
        </nav>
      </UContainer>
    </footer>
  </div>
</template>
