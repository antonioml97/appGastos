<template>
    <div>
        <label class="mb-2 block text-sm font-semibold text-white/75">
            {{ label }}<span v-if="required" class="ml-1 text-[var(--color-accent)]">*</span>
        </label>
        <textarea
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            :placeholder="placeholder"
            :rows="rows"
            :required="required"
            :name="name"
            class="w-full rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-white outline-none transition placeholder:text-white/35 focus:border-[var(--focus-color,var(--color-gold))] focus:bg-black/15"
            :style="{ '--focus-color': focusColor }"
        ></textarea>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    rows: { type: Number, default: 3 },
    required: { type: Boolean, default: false },
    focusColor: { type: String, default: 'var(--color-gold)' },
    name: { type: String, default: undefined },
});

defineEmits(['update:modelValue']);
</script>
