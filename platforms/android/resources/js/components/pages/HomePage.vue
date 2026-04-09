<template>
    <section class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
        <div class="space-y-8">
            <div class="space-y-5">
                <p class="text-sm uppercase tracking-[0.35em] text-[var(--color-gold)]/90">
                    Control de gastos personales
                </p>

                <h2 class="max-w-3xl font-[var(--font-display)] text-5xl leading-none font-bold sm:text-6xl lg:text-7xl">
                    Bienvenido a AppGastos
                </h2>

                <p class="max-w-2xl text-base leading-8 text-white/78 sm:text-lg">
                    Esta app esta pensada para ayudarte a registrar tus gastos, organizar tus categorias
                    y entender mejor en que se va tu dinero con una experiencia simple y enfocada en movil.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <article v-for="item in highlights" :key="item.title" class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur-md">
                    <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-gold)]/85">{{ item.eyebrow }}</p>
                    <h3 class="mt-3 text-xl font-semibold text-white">{{ item.title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-white/70">{{ item.text }}</p>
                </article>
            </div>
        </div>

        <aside class="relative">
            <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-[2rem] bg-[var(--color-accent)]/15 blur-2xl"></div>

            <div class="relative rounded-[2rem] border border-white/10 bg-white/8 p-6 shadow-2xl backdrop-blur-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-white/60">Resumen de la app</p>
                        <h3 class="mt-2 font-[var(--font-display)] text-3xl font-bold">Tu dinero, mas claro</h3>
                    </div>
                    <div class="rounded-2xl bg-[var(--color-gold)] px-4 py-2 text-sm font-semibold text-[var(--color-ink)]">Vue</div>
                </div>

                <div class="mt-8 rounded-2xl bg-[linear-gradient(135deg,rgba(247,196,94,0.18),rgba(48,214,169,0.14))] p-5">
                    <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-gold)]">Accesos principales</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <a v-for="item in menuItems.filter((item) => item.href !== '/')" :key="item.href" :href="item.href" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-4 text-sm font-semibold text-white/90 transition hover:bg-white/12">
                            {{ item.label }}
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</template>

<script setup>
defineProps({
    menuItems: { type: Array, required: true },
});

const highlights = [
    { eyebrow: 'Organiza', title: 'Categorias claras', text: 'Prepara una estructura simple para separar comida, transporte, hogar, ocio y cualquier otro gasto.' },
    { eyebrow: 'Registra', title: 'Movimientos rapidos', text: 'Pensada para anotar importes, fecha y notas de forma rapida desde una experiencia movil.' },
    { eyebrow: 'Entiende', title: 'Vision de conjunto', text: 'El objetivo es que puedas detectar habitos, controlar excesos y tomar mejores decisiones.' },
];

</script>
