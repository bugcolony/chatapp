<script setup lang="js">
const props = defineProps({
	overlay: {
		type: Boolean,
		default: false,
	},
	panelClass: {
		type: String,
		default: "max-w-lg",
	},
	bodyClass: {
		type: String,
		default: "",
	},
});

const slots = useSlots();

const rootClasses = computed(() => {
	return [
		props.overlay ? "fixed inset-0 z-[100]" : "relative min-h-screen",
		"flex items-center justify-center overflow-hidden bg-neutral-950 px-6 py-10 text-white",
	];
});

const panelClasses = computed(() => {
	return [
		"relative w-full rounded-[2rem] border border-white/10 bg-white/95 p-8 text-slate-900 shadow-[0_30px_120px_rgba(15,23,42,0.45)] backdrop-blur",
		props.panelClass,
	];
});

const bodyClasses = computed(() => {
	return [
		"mt-6 text-sm leading-6 text-slate-600",
		props.bodyClass,
	];
});
</script>

<template>
	<div :class="rootClasses">
		<slot name="background">
			<div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.25),_transparent_45%),radial-gradient(circle_at_bottom,_rgba(56,189,248,0.18),_transparent_35%)]" />
			<div class="absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:48px_48px]" />
		</slot>

		<div :class="panelClasses">
			<div class="flex items-center gap-4">
				<slot name="icon">
					<div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
						<UIcon
							name="i-heroicons-arrow-path"
							class="h-7 w-7 animate-spin"
						/>
					</div>
				</slot>

				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">
						<slot name="eyebrow">Loading</slot>
					</p>
					<h1 class="text-2xl font-semibold text-slate-950">
						<slot name="title">Just a moment</slot>
					</h1>
				</div>
			</div>

			<div :class="bodyClasses">
				<slot>Getting things ready.</slot>
			</div>

			<UProgress animation="swing" />

			<div v-if="slots.actions" class="mt-8 flex gap-3">
				<slot name="actions" />
			</div>
		</div>
	</div>
</template>
