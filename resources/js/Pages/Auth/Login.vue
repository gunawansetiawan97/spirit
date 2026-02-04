<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { BaseInput, BaseButton } from '@/Components/Form';

const authStore = useAuthStore();

const emit = defineEmits<{
    (e: 'success'): void;
}>();

const form = reactive({
    email: '',
    password: '',
});

const errors = reactive({
    email: '',
    password: '',
});

const errorMessage = ref('');
const isLoading = ref(false);

const handleSubmit = async () => {
    // Reset errors
    errors.email = '';
    errors.password = '';
    errorMessage.value = '';

    // Validate
    if (!form.email) {
        errors.email = 'Email wajib diisi';
        return;
    }
    if (!form.password) {
        errors.password = 'Password wajib diisi';
        return;
    }

    isLoading.value = true;

    const result = await authStore.login(form.email, form.password);

    isLoading.value = false;

    if (result.success) {
        emit('success');
    } else {
        if (result.errors?.email) {
            errors.email = result.errors.email[0];
        }
        if (result.errors?.password) {
            errors.password = result.errors.password[0];
        }
        if (!result.errors) {
            errorMessage.value = result.message || 'Login gagal';
        }
    }
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white px-8 py-10 shadow-lg">
                <!-- Logo & Title -->
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-primary-600">Spirit ERP</h1>
                    <p class="mt-2 text-gray-600">Masuk ke akun Anda</p>
                </div>

                <!-- Error message -->
                <div
                    v-if="errorMessage"
                    class="mb-4 rounded-md bg-danger-50 p-4 text-sm text-danger-700"
                >
                    {{ errorMessage }}
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit">
                    <div class="space-y-4">
                        <BaseInput
                            v-model="form.email"
                            type="email"
                            label="Email"
                            placeholder="Masukkan email"
                            :error="errors.email"
                            :disabled="isLoading"
                            required
                        />

                        <BaseInput
                            v-model="form.password"
                            type="password"
                            label="Password"
                            placeholder="Masukkan password"
                            :error="errors.password"
                            :disabled="isLoading"
                            required
                        />
                    </div>

                    <div class="mt-6">
                        <BaseButton
                            type="submit"
                            variant="primary"
                            :loading="isLoading"
                            block
                        >
                            Masuk
                        </BaseButton>
                    </div>
                </form>

                <!-- Demo credentials -->
                <div class="mt-6 rounded-md bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500">Demo Credentials:</p>
                    <div class="mt-2 space-y-1 text-xs text-gray-600">
                        <p><span class="font-medium">Admin:</span> admin@spirit.com / password</p>
                        <p><span class="font-medium">Manager:</span> manager@spirit.com / password</p>
                        <p><span class="font-medium">Staff:</span> staff@spirit.com / password</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
