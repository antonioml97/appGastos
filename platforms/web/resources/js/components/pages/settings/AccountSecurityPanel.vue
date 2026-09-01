<template>
    <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
        <p class="text-sm uppercase tracking-[0.28em] text-[var(--color-mint)]/85">Tu cuenta</p>
        <h2 class="mt-3 font-[var(--font-display)] text-[clamp(1.9rem,4vw,2.8rem)] leading-none font-bold">
            Seguridad y acceso
        </h2>
        <p class="mt-4 text-sm leading-7 text-white/65">
            Cambia tu contraseña cuando lo necesites. Al hacerlo se cerrarán las demás sesiones abiertas.
        </p>

        <div v-if="passwordMessage" class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-50">
            {{ passwordMessage }}
        </div>
        <div v-if="passwordErrors.length" class="mt-5 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
            <ul class="space-y-2"><li v-for="error in passwordErrors" :key="error">{{ error }}</li></ul>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="changePassword">
            <FormField v-model="passwordForm.current_password" label="Contraseña actual" name="current_password" type="password" autocomplete="current-password" required />
            <FormField v-model="passwordForm.password" label="Nueva contraseña" name="password" type="password" autocomplete="new-password" required />
            <FormField v-model="passwordForm.password_confirmation" label="Repetir nueva contraseña" name="password_confirmation" type="password" autocomplete="new-password" required />
            <button :disabled="isChangingPassword" type="submit" class="w-full rounded-2xl bg-[var(--color-mint)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-60">
                {{ isChangingPassword ? 'Actualizando…' : 'Cambiar contraseña' }}
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-[2rem] border border-rose-400/30 bg-[linear-gradient(145deg,rgba(244,63,94,0.16),rgba(255,255,255,0.03))] p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
        <p class="text-sm uppercase tracking-[0.28em] text-rose-200/85">Cuenta</p>
        <h2 class="mt-3 font-[var(--font-display)] text-[clamp(1.9rem,4vw,2.8rem)] leading-none font-bold">
            Eliminar mi cuenta
        </h2>
        <p class="mt-4 text-sm leading-7 text-white/70">
            Borra definitivamente tu usuario, gastos, ingresos, categorías, cuentas y movimientos fijos. Esta acción no se puede deshacer.
        </p>

        <div v-if="deleteErrors.length" class="mt-5 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
            <ul class="space-y-2"><li v-for="error in deleteErrors" :key="error">{{ error }}</li></ul>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="deleteUser">
            <FormField v-model="deleteForm.password" label="Contraseña actual" name="delete_password" type="password" autocomplete="current-password" required />
            <FormField v-model="deleteForm.confirmation" label="Escribe BORRAR MI CUENTA" name="delete_confirmation" autocomplete="off" required />
            <button :disabled="isDeletingUser" type="submit" class="w-full rounded-2xl bg-rose-500 px-5 py-3 text-sm font-semibold text-white transition hover:brightness-105 disabled:cursor-wait disabled:opacity-60">
                {{ isDeletingUser ? 'Eliminando…' : 'Eliminar mi cuenta definitivamente' }}
            </button>
        </form>
    </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import FormField from '../../ui/form/FormField.vue';

const emit = defineEmits(['user-deleted']);
const isChangingPassword = ref(false);
const isDeletingUser = ref(false);
const passwordErrors = ref([]);
const deleteErrors = ref([]);
const passwordMessage = ref('');
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const deleteForm = reactive({ password: '', confirmation: '' });

async function changePassword() {
    if (isChangingPassword.value) return;
    isChangingPassword.value = true;
    passwordErrors.value = [];
    passwordMessage.value = '';

    try {
        const { data } = await window.api.patch('/auth/password', { ...passwordForm });
        passwordMessage.value = data.message;
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (error) {
        passwordErrors.value = extractErrors(error, 'No se pudo cambiar la contraseña.');
    } finally {
        isChangingPassword.value = false;
    }
}

async function deleteUser() {
    if (isDeletingUser.value) return;

    if (!window.confirm('Se eliminarán tu cuenta y todos tus datos definitivamente. ¿Quieres continuar?')) return;

    isDeletingUser.value = true;
    deleteErrors.value = [];

    try {
        const { data } = await window.api.delete('/auth/account', { data: { ...deleteForm } });
        emit('user-deleted', data.message);
    } catch (error) {
        deleteErrors.value = extractErrors(error, 'No se pudo eliminar la cuenta.');
    } finally {
        isDeletingUser.value = false;
    }
}

function extractErrors(error, fallback) {
    const errors = error?.response?.data?.errors;
    if (errors) return Object.values(errors).flat();
    return [error?.response?.data?.message ?? fallback];
}
</script>
