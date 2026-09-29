<script setup>
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { display } from '@/display';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    builder: { type: Object, required: true },
    report: { type: Object, default: null },
});

const isEdit = computed(() => !!props.report);

const steps = [
    { key: 'object', label: 'Object' },
    { key: 'columns', label: 'Columns' },
    { key: 'filters', label: 'Filters' },
    { key: 'group', label: 'Group by' },
    { key: 'chart', label: 'Chart' },
    { key: 'preview', label: 'Preview' },
    { key: 'save', label: 'Save' },
];

const stepIndex = ref(0);

const form = useForm({
    name: props.report?.name ?? '',
    description: props.report?.description ?? '',
    folder: props.report?.folder ?? 'private',
    object_type: props.report?.object_type ?? props.builder.objects[0]?.key ?? '',
    columns: [...(props.report?.columns ?? [])],
    filters: [...(props.report?.filters ?? [])],
    group_by: [...(props.report?.group_by ?? [])],
    chart: props.report?.chart
        ? { type: props.report.chart.type ?? '', label: props.report.chart.label ?? '', value: props.report.chart.value ?? '' }
        : { type: '', label: '', value: '' },
});

const preview = ref(null);
const previewError = ref('');
const previewLoading = ref(false);

const objectFields = computed(() => props.builder.fields[form.object_type] ?? null);
const selectableColumns = computed(() => objectFields.value?.columns ?? []);
const groupableFields = computed(() => objectFields.value?.groupable ?? []);

const objectOptions = computed(() =>
    props.builder.objects.map((object) => ({ value: object.key, label: object.label })),
);
const folderOptions = computed(() =>
    props.builder.folders.map((folder) => ({ value: folder.key, label: folder.label })),
);
const operatorOptions = computed(() =>
    props.builder.operators.map((operator) => ({ value: operator.key, label: operator.label })),
);
const chartTypeOptions = computed(() =>
    props.builder.chartTypes.map((type) => ({ value: type.key, label: type.label })),
);
const columnOptions = computed(() =>
    selectableColumns.value.map((column) => ({ value: column.key, label: column.label })),
);
const chartFieldOptions = computed(() => {
    const options = form.columns.map((key) => ({ value: key, label: key }));
    if (!options.some((option) => option.value === 'record_count')) {
        options.push({ value: 'record_count', label: 'record_count' });
    }

    return options;
});

watch(
    () => form.object_type,
    (value, oldValue) => {
        if (isEdit.value && value === props.report?.object_type && oldValue === undefined) {
            return;
        }
        if (isEdit.value && value === props.report?.object_type && oldValue === props.report?.object_type) {
            return;
        }
        if (oldValue === undefined) {
            return;
        }
        form.columns = [];
        form.filters = [];
        form.group_by = [];
        form.chart = { type: '', label: '', value: '' };
        preview.value = null;
    },
);

function toggleColumn(key) {
    if (form.columns.includes(key)) {
        form.columns = form.columns.filter((item) => item !== key);
    } else {
        form.columns = [...form.columns, key];
    }
}

function toggleGroup(key) {
    if (form.group_by.includes(key)) {
        form.group_by = form.group_by.filter((item) => item !== key);
    } else {
        form.group_by = [...form.group_by, key];
        if (!form.columns.includes(key)) {
            form.columns = [...form.columns, key];
        }
    }
}

function addFilter() {
    form.filters.push({
        field: selectableColumns.value[0]?.key ?? '',
        operator: 'equals',
        value: '',
    });
}

function removeFilter(index) {
    form.filters.splice(index, 1);
}

function operatorNeedsValue(operator) {
    return !['this_fy', 'is_true', 'is_false', 'is_null'].includes(operator);
}

function canAdvance() {
    if (steps[stepIndex.value].key === 'object') {
        return !!form.object_type;
    }
    if (steps[stepIndex.value].key === 'columns') {
        return form.columns.length > 0;
    }
    if (steps[stepIndex.value].key === 'save') {
        return form.name.trim() !== '' && !!form.folder;
    }

    return true;
}

function nextStep() {
    if (!canAdvance() || stepIndex.value >= steps.length - 1) {
        return;
    }

    stepIndex.value += 1;

    if (steps[stepIndex.value].key === 'preview') {
        loadPreview();
    }
}

function prevStep() {
    if (stepIndex.value > 0) {
        stepIndex.value -= 1;
    }
}

function ensureGroupCountColumn() {
    if (form.group_by.length > 0 && !form.columns.includes('record_count')) {
        form.columns = [...form.columns, 'record_count'];
    }
}

async function loadPreview() {
    previewLoading.value = true;
    previewError.value = '';
    ensureGroupCountColumn();

    try {
        const { data } = await axios.post(route('reports.preview'), {
            object_type: form.object_type,
            columns: form.columns,
            filters: form.filters,
            group_by: form.group_by,
            chart: form.chart?.type
                ? {
                      type: form.chart.type,
                      label: form.chart.label || undefined,
                      value: form.chart.value || undefined,
                  }
                : null,
            per_page: 25,
        });
        preview.value = data.result;
    } catch (error) {
        preview.value = null;
        previewError.value =
            error.response?.data?.message ||
            error.response?.data?.errors?.object_type?.[0] ||
            'Preview failed.';
    } finally {
        previewLoading.value = false;
    }
}

function submit() {
    ensureGroupCountColumn();

    const payload = {
        name: form.name,
        description: form.description,
        folder: form.folder,
        object_type: form.object_type,
        columns: form.columns,
        filters: form.filters,
        group_by: form.group_by,
        chart: form.chart?.type
            ? {
                  type: form.chart.type,
                  label: form.chart.label || null,
                  value: form.chart.value || null,
              }
            : null,
    };

    if (isEdit.value) {
        form.transform(() => payload).put(route('reports.update', props.report.id));
    } else {
        form.transform(() => payload).post(route('reports.store'));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="isEdit ? 'Edit report' : 'New report'" />

        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6">
            <div>
                <p class="text-small text-text-muted">
                    <Link :href="route('reports.index')" class="text-secondary underline">
                        Reports
                    </Link>
                </p>
                <h1 class="mt-1 text-h1">{{ isEdit ? 'Edit report' : 'Report builder' }}</h1>
            </div>

            <ol class="flex flex-wrap gap-2">
                <li
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="inline-flex min-h-11 items-center rounded-md border px-3 text-small"
                    :class="
                        index === stepIndex
                            ? 'border-secondary bg-surface text-primary'
                            : index < stepIndex
                              ? 'border-border bg-bg text-text'
                              : 'border-border bg-surface text-text-muted'
                    "
                >
                    {{ index + 1 }}. {{ step.label }}
                </li>
            </ol>

            <section class="rounded-md border border-border bg-surface p-4">
                <div v-if="steps[stepIndex].key === 'object'" class="space-y-4">
                    <h2 class="text-h2">Choose the object</h2>
                    <FormField label="Object" required :error="form.errors.object_type">
                        <SelectInput
                            id="object_type"
                            v-model="form.object_type"
                            :options="objectOptions"
                            placeholder="Select an object"
                        />
                    </FormField>
                </div>

                <div v-else-if="steps[stepIndex].key === 'columns'" class="space-y-4">
                    <h2 class="text-h2">Choose columns</h2>
                    <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <li v-for="column in selectableColumns" :key="column.key">
                            <label class="flex min-h-11 items-center gap-2 text-body">
                                <input
                                    type="checkbox"
                                    class="rounded border-border text-secondary"
                                    :checked="form.columns.includes(column.key)"
                                    @change="toggleColumn(column.key)"
                                />
                                {{ column.label }}
                            </label>
                        </li>
                    </ul>
                    <p v-if="form.errors.columns" class="text-small text-danger">
                        {{ form.errors.columns }}
                    </p>
                </div>

                <div v-else-if="steps[stepIndex].key === 'filters'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-h2">Add filters</h2>
                        <SecondaryButton type="button" @click="addFilter">Add filter</SecondaryButton>
                    </div>
                    <p v-if="form.filters.length === 0" class="text-body text-text-muted">
                        No filters. Optionally add field / operator / value rules.
                    </p>
                    <div
                        v-for="(filter, index) in form.filters"
                        :key="index"
                        class="grid grid-cols-1 gap-3 border-t border-border pt-3 md:grid-cols-3"
                    >
                        <FormField label="Field">
                            <SelectInput
                                :id="`filter-field-${index}`"
                                v-model="filter.field"
                                :options="columnOptions"
                                placeholder="Field"
                            />
                        </FormField>
                        <FormField label="Operator">
                            <SelectInput
                                :id="`filter-op-${index}`"
                                v-model="filter.operator"
                                :options="operatorOptions"
                                placeholder="Operator"
                            />
                        </FormField>
                        <FormField label="Value">
                            <TextInput
                                :id="`filter-value-${index}`"
                                v-model="filter.value"
                                class="block w-full"
                                :disabled="!operatorNeedsValue(filter.operator)"
                            />
                            <button
                                type="button"
                                class="mt-2 text-small text-danger underline"
                                @click="removeFilter(index)"
                            >
                                Remove
                            </button>
                        </FormField>
                    </div>
                </div>

                <div v-else-if="steps[stepIndex].key === 'group'" class="space-y-4">
                    <h2 class="text-h2">Group by</h2>
                    <p class="text-body text-text-muted">
                        Optional. Grouped reports show counts and totals.
                    </p>
                    <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <li v-for="field in groupableFields" :key="field.key">
                            <label class="flex min-h-11 items-center gap-2 text-body">
                                <input
                                    type="checkbox"
                                    class="rounded border-border text-secondary"
                                    :checked="form.group_by.includes(field.key)"
                                    @change="toggleGroup(field.key)"
                                />
                                {{ field.label }}
                            </label>
                        </li>
                    </ul>
                </div>

                <div v-else-if="steps[stepIndex].key === 'chart'" class="space-y-4">
                    <h2 class="text-h2">Add a chart</h2>
                    <FormField label="Chart type">
                        <SelectInput
                            id="chart_type"
                            v-model="form.chart.type"
                            :options="chartTypeOptions"
                            placeholder="No chart"
                        />
                    </FormField>
                    <div v-if="form.chart.type" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <FormField label="Label field">
                            <SelectInput
                                id="chart_label"
                                v-model="form.chart.label"
                                :options="chartFieldOptions"
                                placeholder="Select"
                            />
                        </FormField>
                        <FormField label="Value field">
                            <SelectInput
                                id="chart_value"
                                v-model="form.chart.value"
                                :options="chartFieldOptions"
                                placeholder="Select"
                            />
                        </FormField>
                    </div>
                </div>

                <div v-else-if="steps[stepIndex].key === 'preview'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-h2">Preview</h2>
                        <SecondaryButton type="button" :disabled="previewLoading" @click="loadPreview">
                            Refresh preview
                        </SecondaryButton>
                    </div>
                    <p v-if="previewLoading" class="text-body text-text-muted">Loading preview…</p>
                    <p v-else-if="previewError" class="text-body text-danger">{{ previewError }}</p>
                    <template v-else-if="preview">
                        <p class="text-small text-text-muted">
                            {{ preview.total }} record{{ preview.total === 1 ? '' : 's' }}
                        </p>
                        <div class="overflow-x-auto rounded-md border border-border">
                            <table class="min-w-full text-left text-body">
                                <thead class="bg-bg text-small text-text-muted">
                                    <tr>
                                        <th
                                            v-for="column in preview.columns"
                                            :key="column.key"
                                            class="px-3 py-2"
                                        >
                                            {{ column.label }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="preview.rows.length === 0">
                                        <td
                                            :colspan="Math.max(preview.columns.length, 1)"
                                            class="px-4 py-6 text-center text-text-muted"
                                        >
                                            No rows.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(row, index) in preview.rows"
                                        :key="index"
                                        class="border-t border-border"
                                    >
                                        <td
                                            v-for="column in preview.columns"
                                            :key="column.key"
                                            class="px-3 py-2"
                                        >
                                            {{ display(row[column.key]) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <div v-else class="space-y-4">
                    <h2 class="text-h2">Save report</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField label="Name" required :error="form.errors.name">
                            <TextInput id="name" v-model="form.name" class="block w-full" required />
                        </FormField>
                        <FormField label="Folder" required :error="form.errors.folder">
                            <SelectInput
                                id="folder"
                                v-model="form.folder"
                                :options="folderOptions"
                                placeholder="Select a folder"
                            />
                        </FormField>
                    </div>
                    <FormField label="Description" span :error="form.errors.description">
                        <TextArea id="description" v-model="form.description" rows="3" />
                    </FormField>
                </div>
            </section>

            <div class="flex flex-wrap justify-between gap-3">
                <SecondaryButton type="button" :disabled="stepIndex === 0" @click="prevStep">
                    Back
                </SecondaryButton>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton
                        v-if="steps[stepIndex].key !== 'save'"
                        type="button"
                        :disabled="!canAdvance()"
                        @click="nextStep"
                    >
                        Next
                    </PrimaryButton>
                    <PrimaryButton
                        v-else
                        type="button"
                        :disabled="form.processing || !canAdvance()"
                        @click="submit"
                    >
                        {{ isEdit ? 'Update report' : 'Save report' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
