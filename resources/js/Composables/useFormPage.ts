import { ref, computed } from 'vue';
import axios from 'axios';
import { useUiStore } from '@/stores/ui';

type FormMode = 'create' | 'edit' | 'view';

export interface FormPageConfig {
    title: string;
    apiEndpoint: string;
    basePath: string;
    auditType?: string;
    auditRelations?: string[];
}

interface FormPageProps {
    id?: string | number;
    mode: FormMode;
}

type NavigateEmit = (event: 'navigate', route: string) => void;

const AUDIT_RELATION_MAP: Record<string, string> = {
    createdBy: 'created_by_user',
    updatedBy: 'updated_by_user',
    approvedBy: 'approved_by_user',
    printedBy: 'printed_by_user',
};

const AUDIT_TIMESTAMP_MAP: Record<string, string> = {
    createdBy: 'created_at',
    updatedBy: 'updated_at',
    approvedBy: 'approved_at',
    printedBy: 'printed_at',
};

export function useFormPage(config: FormPageConfig, props: FormPageProps, emit: NavigateEmit) {
    const uiStore = useUiStore();

    const {
        title,
        apiEndpoint,
        basePath,
        auditType,
        auditRelations = ['createdBy', 'updatedBy'],
    } = config;

    // State
    const loading = ref(!!props.id);
    const saving = ref(false);
    const formErrors = ref<Record<string, string>>({});
    const activeTab = ref('data');
    const auditData = ref<any>(null);
    const recordId = ref<number | null>(null);

    // Computed
    const pageTitle = computed(() => title);

    const formTabs = computed(() => {
        if (!auditType || props.mode === 'create') return undefined;
        return [
            { key: 'data', label: 'DATA' },
            { key: 'info', label: 'INFO' },
        ];
    });

    // Extract audit data from API response
    const extractAuditData = (data: any): any => {
        const audit: any = {};
        for (const relation of auditRelations) {
            const userKey = AUDIT_RELATION_MAP[relation];
            const tsKey = AUDIT_TIMESTAMP_MAP[relation];
            if (userKey) {
                // Map 'createdBy' → { created_by: data.created_by_user }
                const shortKey = relation.replace(/([A-Z])/g, '_$1').toLowerCase();
                audit[shortKey] = data[userKey] ?? null;
            }
            if (tsKey) {
                audit[tsKey] = data[tsKey] ?? null;
            }
        }
        return audit;
    };

    // Methods
    const setupPage = () => {
        const titles: Record<FormMode, string> = {
            create: `Tambah ${title}`,
            edit: `Edit ${title}`,
            view: `Detail ${title}`,
        };
        uiStore.setPageTitle(titles[props.mode]);
        uiStore.setPageActions([]);
    };

    const fetchData = async (mapData: (data: any) => void) => {
        if (!props.id) {
            loading.value = false;
            return;
        }

        loading.value = true;
        try {
            const response = await axios.get(`${apiEndpoint}/${props.id}`);
            const data = response.data.data;

            mapData(data);

            if (auditType) {
                recordId.value = data.id;
                auditData.value = extractAuditData(data);
            }
        } catch (error: any) {
            console.error(`Failed to fetch ${title.toLowerCase()}:`, error);
            alert(`Gagal memuat data ${title.toLowerCase()}`);
            emit('navigate', basePath);
        } finally {
            loading.value = false;
        }
    };

    const handleSubmit = async (getPayload: () => any) => {
        formErrors.value = {};
        saving.value = true;

        try {
            const payload = getPayload();

            if (props.mode === 'edit' && props.id) {
                await axios.put(`${apiEndpoint}/${props.id}`, payload);
            } else {
                await axios.post(apiEndpoint, payload);
            }

            emit('navigate', basePath);
        } catch (error: any) {
            if (error.response?.data?.errors) {
                formErrors.value = Object.fromEntries(
                    Object.entries(error.response.data.errors).map(([key, value]) => [
                        key,
                        Array.isArray(value) ? value[0] : value,
                    ])
                ) as Record<string, string>;
            } else {
                alert(error.response?.data?.message || 'Gagal menyimpan data');
            }
        } finally {
            saving.value = false;
        }
    };

    const handleBack = () => {
        emit('navigate', basePath);
    };

    const handleEdit = () => {
        if (props.id) {
            emit('navigate', `${basePath}/${props.id}/edit`);
        }
    };

    return {
        // State
        loading,
        saving,
        formErrors,
        activeTab,
        auditData,
        recordId,

        // Computed
        pageTitle,
        formTabs,

        // Methods
        setupPage,
        fetchData,
        handleSubmit,
        handleBack,
        handleEdit,
    };
}
