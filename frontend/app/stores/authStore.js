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
    needsOnboarding: (state) => Boolean(state.user) && !state.user.onboarded,
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

    async updateProfile({ name, avatar = null, remove_avatar = false }) {
      const { $apiFetch } = useNuxtApp();
      let body = { name };

      if (avatar) {
        body = new FormData();
        body.append("name", name);
        body.append("avatar", avatar, avatar.name);
      } else if (remove_avatar) {
        body.remove_avatar = true;
      }

      const res = await $apiFetch("/me", { method: "POST", body });
      const user = res?.data ?? res;

      this.user = { ...this.user, ...user };

      return this.user;
    },

    async completeOnboarding(username) {
      const { $apiFetch } = useNuxtApp();

      const res = await $apiFetch("/me/onboarding", {
        method: "POST",
        body: { username },
      });
      const user = res?.data ?? res;

      this.user = { ...this.user, ...user };

      return this.user;
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