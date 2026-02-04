import { ref, computed } from 'vue';

interface ConfirmOptions {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'info' | 'warning' | 'danger';
    onConfirm?: () => Promise<void> | void;
    onCancel?: () => void;
}

export function useModal() {
    const isOpen = ref(false);
    const isLoading = ref(false);

    const open = () => {
        isOpen.value = true;
    };

    const close = () => {
        if (!isLoading.value) {
            isOpen.value = false;
        }
    };

    const toggle = () => {
        if (isOpen.value) {
            close();
        } else {
            open();
        }
    };

    const setLoading = (value: boolean) => {
        isLoading.value = value;
    };

    return {
        isOpen,
        isLoading,
        open,
        close,
        toggle,
        setLoading,
    };
}

export function useConfirmDialog() {
    const isOpen = ref(false);
    const isLoading = ref(false);
    const options = ref<ConfirmOptions>({
        title: 'Konfirmasi',
        message: '',
        confirmText: 'Ya',
        cancelText: 'Batal',
        variant: 'info',
    });

    let resolvePromise: ((value: boolean) => void) | null = null;

    const confirm = (opts: ConfirmOptions): Promise<boolean> => {
        options.value = {
            title: opts.title || 'Konfirmasi',
            message: opts.message,
            confirmText: opts.confirmText || 'Ya',
            cancelText: opts.cancelText || 'Batal',
            variant: opts.variant || 'info',
            onConfirm: opts.onConfirm,
            onCancel: opts.onCancel,
        };
        isOpen.value = true;

        return new Promise((resolve) => {
            resolvePromise = resolve;
        });
    };

    const handleConfirm = async () => {
        isLoading.value = true;
        try {
            if (options.value.onConfirm) {
                await options.value.onConfirm();
            }
            resolvePromise?.(true);
        } finally {
            isLoading.value = false;
            isOpen.value = false;
        }
    };

    const handleCancel = () => {
        if (options.value.onCancel) {
            options.value.onCancel();
        }
        resolvePromise?.(false);
        isOpen.value = false;
    };

    // Shorthand methods
    const confirmDelete = (message?: string) => {
        return confirm({
            title: 'Hapus Data',
            message: message || 'Apakah Anda yakin ingin menghapus data ini?',
            confirmText: 'Hapus',
            cancelText: 'Batal',
            variant: 'danger',
        });
    };

    const confirmAction = (message: string, title?: string) => {
        return confirm({
            title: title || 'Konfirmasi',
            message,
            variant: 'warning',
        });
    };

    return {
        isOpen,
        isLoading,
        options,
        confirm,
        handleConfirm,
        handleCancel,
        confirmDelete,
        confirmAction,
    };
}
