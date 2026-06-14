// https://nuxt.com/docs/api/configuration/nuxt-config

export default defineNuxtConfig({
  ssr: false,
  hooks:{
    "prerender:routes"({routes}){
      routes.clear()
    }
  },
  spaLoadingTemplate: false,
  compatibilityDate: "2025-07-15",

  app: {
    head: {
      style: [
        {
          textContent:
            "html,body,#__nuxt{min-height:100%}html,body{margin:0;background:#020617;color-scheme:dark}",
        },
      ],
    },
  },

  runtimeConfig: {
    public: {
      // @ts-expect-error -- Node environment variables are available in Nuxt config.
      authTimeout: process.env.NUXT_AUTH_TIMEOUT ?? 10000,
      // @ts-expect-error -- Node environment variables are available in Nuxt config.
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? "",
      // @ts-expect-error -- Node environment variables are available in Nuxt config.
      appUrl: process.env.NUXT_PUBLIC_APP_URL ?? "http://localhost",
      // @ts-expect-error -- Node environment variables are available in Nuxt config.
      wsURL: process.env.NUXT_PUBLIC_WS_URL ?? "",
    },
  },

  modules: ["@nuxt/ui", "@pinia/nuxt", "pinia-plugin-persistedstate/nuxt", "@nuxt/icon", // "@nuxt/eslint",
  "@vueuse/nuxt", "@nuxt/eslint"],

  css: ["~/assets/css/main.css"],

  devtools: {
    enabled: true,

    timeline: {
      enabled: true,
    },
  },
});
