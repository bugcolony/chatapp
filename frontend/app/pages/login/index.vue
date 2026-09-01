<script setup lang="js">
import z from "zod";

definePageMeta({
	middleware: ["guest"],
	title: "Login",
	layout: "naked",
});

const auth = useAuthStore();
const config = useRuntimeConfig();
const route = useRoute();

const AUTH_ERROR_MESSAGES = {
	auth_failed: "We could not complete the sign in. Please try again.",
	access_denied: "You cancelled the sign in before it finished.",
	email_unverified:
		"Your provider did not share a verified email address. Verify your email with them, then try again.",
	email_not_allowed:
		"That email address cannot be used here. Sign in with a provider account that uses your real address.",
};

const authError = computed(() => {
	const code = route.query.error;

	if (typeof code !== "string") {
		return null;
	}

	return Object.hasOwn(AUTH_ERROR_MESSAGES, code)
		? AUTH_ERROR_MESSAGES[code]
		: AUTH_ERROR_MESSAGES.auth_failed;
});

const loginBackgroundStyle = {
	backgroundImage: 'url("/images/bgpatt.webp")',
	backgroundSize: 'auto 500px',
};

const fields = ref([
	{
		name: "email",
		type: "text",
		label: "Email",
    defaultValue: "example1@example.com",
		required: true,
	},
	{
		name: "password",
		type: "password",
		label: "Password",
    defaultValue: "password",
		required: true,
	},
]);

const schema = z.object({
	email: z.email(),
	password: z.string(),
});

const redirectToOAuthProvider = async (provider) => {
	await navigateTo(`${config.public.apiBase}/auth/${provider}/redirect`, {
		external: true,
	});
};


const providers = ref([
	{
		name: "google",
		icon: "i-simple-icons-google",
		color: "neutral",
		variant: "subtle",
		label: "Google",
		onClick: () => {
			return redirectToOAuthProvider("google");
		},
	},
	{
		name: "github",
		icon: "i-simple-icons-github",
		color: "neutral",
		variant: "subtle",
		label: "GitHub",
		onClick: () => {
			return redirectToOAuthProvider("github");
		},
	},
]);

const form = useTemplateRef("form");

function validate(_state) {
	return [];
}

function setFormErrors(errors) {
	form.value?.formRef?.setErrors(errors);
}

async function onSubmit(payload) {
	try {
		await auth.login(payload.data);
		await navigateTo(
			{
				path: "/login/process"
			},
			{ replace: true },
		);
	} catch (err) {
		console.error(err);
		if (err.response?._data?.errors) {
			const errors = Object.entries(err.response._data.errors).map(
				([name, messages]) => ({
					name,
					message: messages[0],
				}),
			);
			setFormErrors(errors);
		} else {
			setFormErrors([
				{ name: "email", message: "Invalid credentials" },
			]);
		}
	}
}
</script>

<template>
	<div
		:style="loginBackgroundStyle"
		class="h-screen w-full overflow-hidden bg-repeat flex justify-end"
	>

		<div
			class="w-full sm:max-w-xl h-full bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm p-6 sm:p-18 flex flex-col shadow-xl border-l border-gray-200 dark:border-gray-800"
		>
      <h1 class="font-semibold text-white text-center text-6xl my-20">Chat App</h1>
			<UAlert
				v-if="authError"
				class="mb-6"
				color="error"
				variant="subtle"
				icon="i-lucide-triangle-alert"
				title="Sign in failed"
				:description="authError"
			/>
			<UAuthForm
				ref="form"
				:schema="schema"
				:fields="fields"
        :validate="validate"
				:on-submit="onSubmit"
				:loading="auth.loading"
				:providers="providers"
			>
        <template #separator>
          <span/>
        </template>
        <template #email-hint>
          <UPopover :content="{side: 'top'}">
            <UIcon name="i-lucide-info" />

            <template #content>
              <div class="p-3 max-w-80">
                <span class="text-sm">Feel free to use demo users with credentials: <i>example[1-10]@example.com; password.</i> e.g. <i>example1@example.com; password</i></span>
              </div>
            </template>
          </UPopover>

        </template>
      </UAuthForm>
		</div>
	</div>
</template>

<style scoped></style>
