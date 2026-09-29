<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    objects: { type: Array, required: true },
    fields: { type: Object, required: true },
    result: { type: Object, default: null },
});

const page = usePage();
const headers = ref([]);
const fileInput = ref(null);

const form = useForm({
    object_type: props.objects[0]?.key ?? '',
    file: null,
    update_existing: false,
    mapping: {},
});

const currentFields = computed(() => props.fields[form.object_type] ?? []);

watch(
    () => form.object_type,
    () => {
        resetMapping();
    },
);

function resetMapping() {
    const mapping = {};
    for (const field of currentFields.value) {
        mapping[field.key] = null;
    }
    form.mapping = mapping;
}

resetMapping();

function onFileChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.file = file;
    headers.value = [];

    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        const text = String(reader.result ?? '');
        const firstLine = text.split(/\r?\n/).find((line) => line.trim() !== '') ?? '';
        headers.value = parseCsvLine(firstLine);
        autoMap();
    };
    reader.readAsText(file);
}

function parseCsvLine(line) {
    const cells = [];
    let current = '';
    let inQuotes = false;

    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        if (char === '"') {
            if (inQuotes && line[i + 1] === '"') {
                current += '"';
                i++;
            } else {
                inQuotes = !inQuotes;
            }
            continue;
        }
        if (char === ',' && !inQuotes) {
            cells.push(current.trim());
            current = '';
            continue;
        }
        current += char;
    }
    cells.push(current.trim());

    return cells.filter((cell, index, all) => !(cell === '' && index === all.length - 1));
}

function autoMap() {
    const mapping = { ...form.mapping };
    currentFields.value.forEach((field) => {
        const matchIndex = headers.value.findIndex(
            (header) =>
                header.toLowerCase().replace(/[\s_]+/g, '') ===
                field.label.toLowerCase().replace(/[\s_]+/g, ''),
        );
        mapping[field.key] = matchIndex >= 0 ? matchIndex : null;
    });
    form.mapping = mapping;
}

function submit() {
    form.post(route('import.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.file = null;
            headers.value = [];
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            resetMapping();
        },
    });
}

const flashResult = computed(() => props.result ?? page.props.flash?.import_result ?? null);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Import CSV" />

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-6">
            <div>
                <h1 class="text-h1">Import CSV</h1>
                <p class="mt-1 text-body text-text-muted">
                    Upload a CSV, map columns, and import leads, accounts, contacts, or opportunities.
                    Files are processed in memory and are not stored.
                </p>
            </div>

            <div
                v-if="flashResult"
                class="border border-border bg-surface p-4 text-body"
            >
                <p>
                    Inserted {{ flashResult.imported }}, updated {{ flashResult.updated }}, failed
                    {{ flashResult.failed }}.
                </p>
                <a
                    v-if="flashResult.error_token"
                    :href="route('import.errors', flashResult.error_token)"
                    class="mt-2 inline-flex text-secondary underline"
                >
                    Download error report
                </a>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="object_type" value="Object" required />
                        <select
                            id="object_type"
                            v-model="form.object_type"
                            class="mt-1 block w-full min-h-11 rounded-md border-border bg-surface text-body text-text"
                        >
                            <option v-for="object in objects" :key="object.key" :value="object.key">
                                {{ object.label }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.object_type" />
                    </div>
                    <div>
                        <InputLabel for="file" value="CSV file" required />
                        <input
                            id="file"
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="mt-1 block w-full text-body"
                            @change="onFileChange"
                        />
                        <InputError class="mt-1" :message="form.errors.file" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-body">
                    <input v-model="form.update_existing" type="checkbox" class="rounded border-border" />
                    Update existing records when a match is found (off by default)
                </label>

                <div v-if="headers.length > 0" class="space-y-3">
                    <h2 class="text-h2">Column mapping</h2>
                    <div
                        v-for="field in currentFields"
                        :key="field.key"
                        class="grid gap-2 md:grid-cols-2"
                    >
                        <InputLabel
                            :for="`map-${field.key}`"
                            :value="field.label"
                            :required="field.required"
                        />
                        <div>
                            <select
                                :id="`map-${field.key}`"
                                v-model="form.mapping[field.key]"
                                class="block w-full min-h-11 rounded-md border-border bg-surface text-body text-text"
                            >
                                <option :value="null">— Skip —</option>
                                <option
                                    v-for="(header, index) in headers"
                                    :key="`${field.key}-${index}`"
                                    :value="index"
                                >
                                    {{ header || `(Column ${index + 1})` }}
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors[`mapping.${field.key}`]"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-small font-semibold uppercase tracking-widest text-surface"
                        :disabled="form.processing || !form.file"
                    >
                        Run import
                    </button>
                    <Link
                        :href="route('home')"
                        class="inline-flex min-h-11 items-center rounded-md border border-border bg-surface px-4 text-small"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
