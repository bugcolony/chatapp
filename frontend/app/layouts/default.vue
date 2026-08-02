<script setup lang="js">
const auth = useAuthStore()
const { disconnect } = useSocketStore()

async function logout() {
  disconnect()
	await auth.logout()
}
</script>

<template>
	<div class="min-h-screen">
		<header class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
			<nav class="p-4 flex items-center justify-between max-w-7xl mx-auto">
				<div class="flex items-center gap-4">
					<NuxtLink to="/" class="font-semibold hover:text-emerald-700 transition-colors">Home</NuxtLink>
					<NuxtLink to="/updates" class="font-semibold hover:text-emerald-700 transition-colors">What's New</NuxtLink>
					<NuxtLink v-if="auth.isAuthenticated" to="/app" class="font-semibold hover:text-emerald-700 transition-colors">App</NuxtLink>
				</div>
				<div class="flex items-center gap-3">
					<UButton
						v-if="auth.isAuthenticated"
						variant="ghost"
						color="neutral"
						@click="logout"
					>
						Logout
					</UButton>
					<UButton
						v-else
						to="/login"
						variant="ghost"
						color="primary"
						:loading="!auth.isResolved"
					>
						Login
					</UButton>
				</div>
			</nav>
		</header>
		<main>
			<slot />
		</main>
	</div>
</template>

<style scoped>

</style>