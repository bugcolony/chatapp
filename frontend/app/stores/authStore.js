export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    loading: false,
    bootstrapped: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    isGuest: (state) => !state.user,
    isResolved: (state) => state.bootstrapped,
    authId: (state) => state.user?.id,
  },

  actions: {
    async fetchCSRFToken() {
      const config = useRuntimeConfig();
      const { $apiFetch } = useNuxtApp();

      return $apiFetch("/sanctum/csrf-cookie", {
        baseURL: config.public.apiBase,
      });
    },

    async fetchUser() {
      const { $apiFetch } = useNuxtApp();

      return $apiFetch("/me");
    },

    async login(credentials) {
      this.loading = true;

      try {
        const { $apiFetch } = useNuxtApp();

        await this.fetchCSRFToken();
        await $apiFetch("/login", {
          method: "POST",
          body: credentials,
        });

        const user = await this.fetchUser();

        this.setAuthenticated(user);
      } catch (error) {
        console.error("Login error:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      const { $apiFetch } = useNuxtApp();

      await $apiFetch("/logout", { method: "POST" });
      this.setGuest();
    },

    setAuthenticated(user) {
      this.user = user;
      this.bootstrapped = true;
    },

    setGuest() {
      this.user = null;
      this.bootstrapped = true;
    },

    resetAuth(force = false) {
      this.user = null;

      if (force) {
        this.bootstrapped = false;
      }
    },
  },
});