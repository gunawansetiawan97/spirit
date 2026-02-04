import { ref, reactive, computed } from 'vue';
import type { ValidationRule } from '@/types';

interface FormField {
    value: any;
    error: string;
    touched: boolean;
    rules?: ValidationRule;
}

interface UseFormOptions<T> {
    initialValues: T;
    validationRules?: Partial<Record<keyof T, ValidationRule>>;
    onSubmit?: (values: T) => Promise<void> | void;
}

export function useForm<T extends Record<string, any>>(options: UseFormOptions<T>) {
    const { initialValues, validationRules = {}, onSubmit } = options;

    const fields = reactive<Record<string, FormField>>({});
    const isSubmitting = ref(false);
    const submitError = ref<string | null>(null);

    // Initialize fields
    Object.keys(initialValues).forEach((key) => {
        fields[key] = {
            value: initialValues[key as keyof T],
            error: '',
            touched: false,
            rules: validationRules[key as keyof T],
        };
    });

    // Get current form values
    const values = computed(() => {
        const result = {} as T;
        Object.keys(fields).forEach((key) => {
            (result as any)[key] = fields[key].value;
        });
        return result;
    });

    // Get all errors
    const errors = computed(() => {
        const result: Record<string, string> = {};
        Object.keys(fields).forEach((key) => {
            result[key] = fields[key].error;
        });
        return result;
    });

    // Check if form is valid
    const isValid = computed(() => {
        return Object.keys(fields).every((key) => !fields[key].error);
    });

    // Check if form has been modified
    const isDirty = computed(() => {
        return Object.keys(fields).some(
            (key) => fields[key].value !== initialValues[key as keyof T]
        );
    });

    // Validate a single field
    const validateField = (name: string): boolean => {
        const field = fields[name];
        if (!field) return true;

        const rules = field.rules;
        if (!rules) {
            field.error = '';
            return true;
        }

        const value = field.value;

        // Required
        if (rules.required && (value === '' || value === null || value === undefined)) {
            field.error = 'Field ini wajib diisi';
            return false;
        }

        // Min length
        if (rules.minLength && typeof value === 'string' && value.length < rules.minLength) {
            field.error = `Minimal ${rules.minLength} karakter`;
            return false;
        }

        // Max length
        if (rules.maxLength && typeof value === 'string' && value.length > rules.maxLength) {
            field.error = `Maksimal ${rules.maxLength} karakter`;
            return false;
        }

        // Min value
        if (rules.min !== undefined && typeof value === 'number' && value < rules.min) {
            field.error = `Nilai minimal ${rules.min}`;
            return false;
        }

        // Max value
        if (rules.max !== undefined && typeof value === 'number' && value > rules.max) {
            field.error = `Nilai maksimal ${rules.max}`;
            return false;
        }

        // Email
        if (rules.email && typeof value === 'string' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                field.error = 'Format email tidak valid';
                return false;
            }
        }

        // Pattern
        if (rules.pattern && typeof value === 'string' && !rules.pattern.test(value)) {
            field.error = 'Format tidak valid';
            return false;
        }

        // Custom validation
        if (rules.custom) {
            const result = rules.custom(value);
            if (result !== true) {
                field.error = typeof result === 'string' ? result : 'Validasi gagal';
                return false;
            }
        }

        field.error = '';
        return true;
    };

    // Validate all fields
    const validate = (): boolean => {
        let isFormValid = true;
        Object.keys(fields).forEach((key) => {
            fields[key].touched = true;
            if (!validateField(key)) {
                isFormValid = false;
            }
        });
        return isFormValid;
    };

    // Set field value
    const setFieldValue = (name: string, value: any) => {
        if (fields[name]) {
            fields[name].value = value;
            if (fields[name].touched) {
                validateField(name);
            }
        }
    };

    // Set field error
    const setFieldError = (name: string, error: string) => {
        if (fields[name]) {
            fields[name].error = error;
        }
    };

    // Set multiple errors (useful for server-side validation)
    const setErrors = (errors: Record<string, string>) => {
        Object.keys(errors).forEach((key) => {
            if (fields[key]) {
                fields[key].error = errors[key];
            }
        });
    };

    // Touch a field
    const touchField = (name: string) => {
        if (fields[name]) {
            fields[name].touched = true;
            validateField(name);
        }
    };

    // Reset form to initial values
    const reset = () => {
        Object.keys(fields).forEach((key) => {
            fields[key].value = initialValues[key as keyof T];
            fields[key].error = '';
            fields[key].touched = false;
        });
        submitError.value = null;
    };

    // Handle form submission
    const handleSubmit = async () => {
        if (!validate()) return;
        if (!onSubmit) return;

        isSubmitting.value = true;
        submitError.value = null;

        try {
            await onSubmit(values.value);
        } catch (error: any) {
            submitError.value = error.message || 'Terjadi kesalahan';

            // Handle Laravel validation errors
            if (error.response?.data?.errors) {
                setErrors(
                    Object.fromEntries(
                        Object.entries(error.response.data.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : value,
                        ])
                    ) as Record<string, string>
                );
            }
        } finally {
            isSubmitting.value = false;
        }
    };

    return {
        fields,
        values,
        errors,
        isValid,
        isDirty,
        isSubmitting,
        submitError,
        setFieldValue,
        setFieldError,
        setErrors,
        touchField,
        validateField,
        validate,
        reset,
        handleSubmit,
    };
}
