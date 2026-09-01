<template>
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--color-ink)] px-4 py-10 text-[var(--color-paper)]">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-8rem] top-[-6rem] h-72 w-72 rounded-full bg-[var(--color-accent)]/20 blur-3xl"></div>
            <div class="absolute bottom-[-7rem] right-[-6rem] h-72 w-72 rounded-full bg-[var(--color-mint)]/15 blur-3xl"></div>
        </div>

        <section class="relative w-full max-w-md rounded-[2rem] border border-white/10 bg-[rgba(12,22,36,0.88)] p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
            <div class="flex items-center gap-4">
                <img :src="logoUrl" alt="Logo de AppGastos" class="h-16 w-16 rounded-2xl border border-white/10 bg-[#081225] p-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-[var(--color-gold)]/85">AppGastos</p>
                    <h1 class="mt-1 font-[var(--font-display)] text-2xl font-bold">{{ isRegister ? 'Crear cuenta' : 'Iniciar sesión' }}</h1>
                </div>
            </div>

            <p class="mt-6 text-sm leading-6 text-white/65">
                Tus gastos se sincronizarán de forma segura entre la web y tus dispositivos NativePHP.
            </p>

            <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ errorMessage }}
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <FormField v-if="isRegister" v-model="form.name" label="Nombre" name="name" autocomplete="name" required />
                <FormField v-model="form.email" label="Correo electrónico" name="email" type="email" autocomplete="email" required />
                <FormField v-model="form.password" label="Contraseña" name="password" type="password" autocomplete="current-password" required />
                <FormField v-if="isRegister" v-model="form.password_confirmation" label="Repetir contraseña" name="password_confirmation" type="password" autocomplete="new-password" required />

                <button :disabled="submitting" type="submit" class="w-full rounded-2xl bg-[var(--color-gold)] px-5 py-3 font-semibold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-60">
                    {{ submitting ? 'Conectando…' : (isRegister ? 'Crear cuenta' : 'Entrar') }}
                </button>
            </form>

            <button type="button" class="mt-5 w-full text-center text-sm text-white/65 underline decoration-white/25 underline-offset-4" @click="toggleMode">
                {{ isRegister ? 'Ya tengo una cuenta' : 'Crear una cuenta nueva' }}
            </button>
        </section>
    </main>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import FormField from '../../ui/form/FormField.vue';

const props = defineProps({
    errorMessage: { type: String, default: '' },
    submitting: { type: Boolean, default: false },
});
const emit = defineEmits(['authenticate', 'clear-error']);
const mode = ref('login');
const isRegister = computed(() => mode.value === 'register');
const logoUrl = `${window.location.origin}/images/logo.svg`;
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });

function submit() {
    emit('authenticate', { mode: mode.value, payload: { ...form } });
}

function toggleMode() {
    mode.value = isRegister.value ? 'login' : 'register';
    form.password = '';
    form.password_confirmation = '';
    emit('clear-error');
}
</script>
