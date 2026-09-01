<template>
    <div>
        <label class="mb-2 block text-sm font-semibold text-white/75">
            {{ label }}<span v-if="required" class="ml-1 text-[var(--color-accent)]">*</span>
        </label>
        <input
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            :type="type"
            :placeholder="placeholder"
            :step="step"
            :min="min"
            :required="required"
            :name="name"
            :autocomplete="autocomplete"
            class="w-full rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-white outline-none transition placeholder:text-white/35 focus:border-[var(--focus-color,var(--color-gold))] focus:bg-black/15"
            :style="{ '--focus-color': focusColor }"
        >
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    step: { type: String, default: undefined },
    min: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    focusColor: { type: String, default: 'var(--color-gold)' },
    name: { type: String, default: undefined },
    autocomplete: { type: String, default: undefined },
});

defineEmits(['update:modelValue']);
</script>
