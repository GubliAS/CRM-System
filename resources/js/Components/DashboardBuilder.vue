<script setup>
import CrmSelect from '@/Components/CrmSelect.vue';
import DashboardWidget from '@/Components/DashboardWidget.vue';
import InputError from '@/Components/InputError.vue';
import WidgetGlyph from '@/Components/WidgetGlyph.vue';
import {
    COLS,
    MAX_HEIGHT,
    MIN_HEIGHT,
    MIN_WIDTH,
    TYPE_DEFAULTS,
    TYPE_META,
    bottomRow,
    clampWidget,
    compact,
    newWidgetId,
    readingOrder,
    resolve,
} from '@/dashboard/layout';
import { buildCards } from '@/dashboard/widgets';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    /** Inertia useForm: name, description, folder, widgets[] */
    form: { type: Object, required: true },
    builder: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save dashboard' },
    cancelHref: { type: String, required: true },
});

defineEmits(['submit']);

// Same as the viewer (4.75rem rows, 1rem gaps) so sizes here match the saved dashboard.
const GAP = 16;
const ROW_H = 76;

const canvas = ref(null);
const selectedId = ref(null);
const drag = ref(null);
const adding = ref(false);
const addType = ref('metric');
const addReport = ref(props.builder.reports[0]?.id ?? null);
const addTitle = ref('');

const types = computed(() =>
    props.builder.widgetTypes.map((type) => ({ ...type, ...(TYPE_META[type.key] ?? {}) })),
);

const reportOptions = computed(() =>
    props.builder.reports.map((report) => ({ value: report.id, label: report.name, hint: report.object_type })),
);

const reportName = (id) => props.builder.reports.find((report) => report.id === id)?.name ?? 'No report';

const widgets = computed(() => props.form.widgets);
const selected = computed(() => widgets.value.find((widget) => widget.id === selectedId.value) ?? null);
const selectedIndex = computed(() => widgets.value.findIndex((widget) => widget.id === selectedId.value));
const canAdd = computed(
    () => widgets.value.length < props.builder.maxWidgets && props.builder.reports.length > 0,
);
const rows = computed(() => Math.max(6, bottomRow(widgets.value) + 2));
const canvasHeight = computed(() => `${rows.value * ROW_H + (rows.value - 1) * GAP}px`);
const gridRows = computed(() => `repeat(${rows.value}, ${ROW_H}px)`);

/* Live previews: each widget runs its real report so it looks as it will once saved. */

const previews = reactive({});
let queue = Promise.resolve();

const previewKey = (widget) => `${widget.report_id}:${widget.type}`;

function ensurePreview(widget) {
    const key = previewKey(widget);

    if (!widget.report_id || previews[key]) {
        return;
    }

    previews[key] = { state: 'loading' };
    // One at a time: a preview is a full report run, and the server handles them serially anyway.
    queue = queue.then(() =>
        axios
            .post(route('dashboards.preview-widget'), { report_id: widget.report_id, type: widget.type })
            .then((response) => {
                previews[key] = { state: 'ready', widget: response.data };
            })
            .catch(() => {
                previews[key] = { state: 'error' };
            }),
    );
}

watch(
    () => widgets.value.map(previewKey),
    () => widgets.value.forEach(ensurePreview),
    { immediate: true },
);

const cardsById = computed(() => {
    const ordered = readingOrder(widgets.value).map((widget) => {
        const preview = previews[previewKey(widget)];
        const base = preview?.state === 'ready' ? preview.widget : {};

        return {
            ...base,
            id: widget.id,
            type: widget.type,
            title: widget.title || base.report_name || reportName(widget.report_id),
            report_name: base.report_name ?? reportName(widget.report_id),
            error: preview?.state === 'error' ? 'Could not load a preview for this report.' : (base.error ?? null),
            result: base.result ?? null,
            layout: { row: widget.row, col: widget.col, width: widget.width, height: widget.height },
        };
    });

    return Object.fromEntries(buildCards(ordered).map((card) => [card.widget.id, card]));
});

const isLoading = (widget) => (previews[previewKey(widget)]?.state ?? 'loading') === 'loading';

function widgetStyle(widget) {
    return {
        gridColumn: `${widget.col + 1} / span ${widget.width}`,
        gridRow: `${widget.row + 1} / span ${widget.height}`,
    };
}

function setWidgets(next) {
    props.form.widgets = next;
}

function update(id, patch, { pin = true } = {}) {
    const next = widgets.value.map((widget) =>
        widget.id === id ? clampWidget({ ...widget, ...patch }) : widget,
    );

    setWidgets(resolve(next, pin ? id : null));
}

function addWidget() {
    if (!canAdd.value || !addReport.value) {
        return;
    }

    const size = TYPE_DEFAULTS[addType.value] ?? { width: 6, height: 3 };
    const widget = clampWidget({
        id: newWidgetId(),
        report_id: addReport.value,
        type: addType.value,
        title: addTitle.value.trim(),
        row: bottomRow(widgets.value),
        col: 0,
        ...size,
    });

    setWidgets([...widgets.value, widget]);
    selectedId.value = widget.id;
    addTitle.value = '';
    adding.value = false;
}

function removeWidget(id) {
    setWidgets(widgets.value.filter((widget) => widget.id !== id));

    if (selectedId.value === id) {
        selectedId.value = null;
    }
}

function tidy() {
    setWidgets(compact(widgets.value));
}

/* Pointer drag: the header moves a widget, the corner handle resizes it. */

function cellMetrics() {
    const width = canvas.value.getBoundingClientRect().width;

    return {
        cellW: (width - GAP * (COLS - 1)) / COLS + GAP,
        cellH: ROW_H + GAP,
    };
}

function startDrag(event, widget, mode) {
    if (event.button !== 0) {
        return;
    }

    selectedId.value = widget.id;
    drag.value = {
        id: widget.id,
        mode,
        x: event.clientX,
        y: event.clientY,
        orig: { col: widget.col, row: widget.row, width: widget.width, height: widget.height },
        ...cellMetrics(),
    };
    try {
        event.currentTarget.setPointerCapture(event.pointerId);
    } catch {
        // Capture is a nicety: without it the drag still follows the pointer over the element.
    }
}

function onDrag(event) {
    const d = drag.value;

    if (!d) {
        return;
    }

    const dCol = Math.round((event.clientX - d.x) / d.cellW);
    const dRow = Math.round((event.clientY - d.y) / d.cellH);

    if (d.mode === 'move') {
        update(d.id, { col: d.orig.col + dCol, row: d.orig.row + dRow });

        return;
    }

    update(d.id, {
        width: Math.min(Math.max(d.orig.width + dCol, MIN_WIDTH), COLS - d.orig.col),
        height: Math.min(Math.max(d.orig.height + dRow, MIN_HEIGHT), MAX_HEIGHT),
    });
}

function endDrag() {
    drag.value = null;
}

/* Keyboard: arrows move the focused widget, Shift+arrows resize it. */
function onKey(event, widget) {
    const step = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[event.key];

    if (event.key === 'Delete' || event.key === 'Backspace') {
        if (event.target === event.currentTarget) {
            event.preventDefault();
            removeWidget(widget.id);
        }

        return;
    }

    if (!step || event.target !== event.currentTarget) {
        return;
    }

    event.preventDefault();

    if (event.shiftKey) {
        update(widget.id, {
            width: Math.min(Math.max(widget.width + step[0], MIN_WIDTH), COLS - widget.col),
            height: Math.min(Math.max(widget.height + step[1], MIN_HEIGHT), MAX_HEIGHT),
        });

        return;
    }

    update(widget.id, { col: widget.col + step[0], row: widget.row + step[1] });
}

function setNumber(id, key, value) {
    const number = Number(value);

    if (Number.isFinite(number)) {
        update(id, { [key]: number });
    }
}
</script>

<template>
    <form class="crm-bd" @submit.prevent="$emit('submit')">
        <!-- Properties -->
        <section class="crm-ov-card crm-bd-props">
            <div class="crm-bd-field crm-bd-field-name">
                <label for="dash-name">Name</label>
                <input
                    id="dash-name"
                    v-model="form.name"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="e.g. Quarterly pipeline"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="crm-bd-field crm-bd-field-desc">
                <label for="dash-desc">Description</label>
                <input
                    id="dash-desc"
                    v-model="form.description"
                    type="text"
                    placeholder="What is this dashboard for?"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="crm-bd-field">
                <span id="dash-folder-label" class="crm-bd-label">Folder</span>
                <div class="crm-ov-seg" role="radiogroup" aria-labelledby="dash-folder-label">
                    <button
                        type="button"
                        role="radio"
                        class="crm-ov-seg-btn"
                        :class="form.folder === 'private' ? 'crm-ov-seg-btn-active' : ''"
                        :aria-checked="form.folder === 'private'"
                        @click="form.folder = 'private'"
                    >
                        <Icon icon="lucide:lock" aria-hidden="true" /> Private
                    </button>
                    <button
                        type="button"
                        role="radio"
                        class="crm-ov-seg-btn"
                        :class="form.folder === 'shared' ? 'crm-ov-seg-btn-active' : ''"
                        :aria-checked="form.folder === 'shared'"
                        @click="form.folder = 'shared'"
                    >
                        <Icon icon="lucide:users" aria-hidden="true" /> Shared
                    </button>
                </div>
                <InputError :message="form.errors.folder" />
            </div>
        </section>

        <p class="crm-bd-folder-note">
            <Icon :icon="form.folder === 'shared' ? 'lucide:users' : 'lucide:lock'" aria-hidden="true" />
            {{
                form.folder === 'shared'
                    ? 'Shared: everyone who can open dashboards can view this one. Only you can edit it, and each viewer sees data from reports they are allowed to see.'
                    : 'Private: only you can see and edit this dashboard.'
            }}
        </p>

        <div class="crm-bd-workspace">
            <!-- Canvas -->
            <section class="crm-ov-card crm-bd-stage" aria-labelledby="bd-layout-heading">
                <div class="crm-ov-head">
                    <div>
                        <h2 id="bd-layout-heading" class="crm-ov-title">Layout</h2>
                        <p class="crm-ov-sub">
                            Drag a widget by its header, resize from the corner.
                            <span class="crm-bd-count tabular-nums">
                                {{ widgets.length }} / {{ builder.maxWidgets }}
                            </span>
                        </p>
                    </div>
                    <div class="crm-bd-tools">
                        <button
                            type="button"
                            class="crm-db-ghost"
                            :disabled="widgets.length < 2"
                            title="Close gaps between widgets"
                            @click="tidy"
                        >
                            <Icon icon="lucide:align-vertical-space-around" aria-hidden="true" />
                            Tidy up
                        </button>
                        <button
                            type="button"
                            class="crm-db-new"
                            :disabled="!canAdd"
                            :aria-expanded="adding"
                            @click="adding = !adding"
                        >
                            <Icon icon="lucide:plus" aria-hidden="true" />
                            Add widget
                        </button>
                    </div>
                </div>

                <p v-if="builder.reports.length === 0" class="crm-ov-empty" role="status">
                    Save a report first, then add it here as a widget.
                </p>
                <InputError class="mt-2" :message="form.errors.widgets" />

                <!-- Palette -->
                <Transition name="crm-bd-pop">
                <div v-if="adding" class="crm-bd-palette">
                    <div class="crm-bd-types" role="radiogroup" aria-label="Widget type">
                        <button
                            v-for="type in types"
                            :key="type.key"
                            type="button"
                            role="radio"
                            class="crm-bd-type"
                            :class="[`crm-dbt-${type.key}`, addType === type.key ? 'crm-bd-type-active' : '']"
                            :aria-checked="addType === type.key"
                            @click="addType = type.key"
                        >
                            <WidgetGlyph :type="type.key" />
                            <span class="crm-bd-type-name">{{ type.label }}</span>
                            <span class="crm-bd-type-hint">{{ type.hint }}</span>
                        </button>
                    </div>
                    <div class="crm-bd-palette-fields">
                        <div class="crm-bd-field">
                            <span class="crm-bd-label">Report</span>
                            <CrmSelect v-model="addReport" aria-label="Report" :options="reportOptions" />
                        </div>
                        <div class="crm-bd-field">
                            <label for="add-title">Title <span>(optional)</span></label>
                            <input id="add-title" v-model="addTitle" type="text" placeholder="Defaults to the report name" />
                        </div>
                        <div class="crm-bd-palette-actions">
                            <button type="button" class="crm-db-new" @click="addWidget">
                                <Icon icon="lucide:check" aria-hidden="true" />
                                Add to dashboard
                            </button>
                            <button type="button" class="crm-db-ghost" @click="adding = false">Cancel</button>
                        </div>
                    </div>
                </div>
                </Transition>

                <!-- Canvas -->
                <div class="crm-bd-canvas-wrap">
                    <div class="crm-bd-canvas" :style="{ height: canvasHeight }" @click.self="selectedId = null">
                        <div
                            class="crm-bd-cells"
                            :style="{ gridTemplateRows: gridRows }"
                            aria-hidden="true"
                        >
                            <span v-for="n in rows * COLS" :key="n" class="crm-bd-cell" />
                        </div>

                        <div
                            ref="canvas"
                            class="crm-bd-layer"
                            :style="{ gridTemplateRows: gridRows }"
                            @click.self="selectedId = null"
                        >
                            <article
                                v-for="widget in widgets"
                                :key="widget.id"
                                class="crm-bd-w"
                                :class="[
                                    `crm-dbt-${widget.type}`,
                                    selectedId === widget.id ? 'crm-bd-w-selected' : '',
                                    drag?.id === widget.id ? 'crm-bd-w-dragging' : '',
                                ]"
                                :style="widgetStyle(widget)"
                                tabindex="0"
                                :aria-label="`${widget.title || reportName(widget.report_id)}, ${widget.type} widget. Arrow keys move it, Shift plus arrows resize it.`"
                                @click.stop="selectedId = widget.id"
                                @keydown="onKey($event, widget)"
                            >
                                <DashboardWidget
                                    :card="cardsById[widget.id]"
                                    :interactive="false"
                                    :loading="isLoading(widget)"
                                />

                                <!-- Drag surface over the whole widget -->
                                <div
                                    class="crm-bd-drag"
                                    @pointerdown="startDrag($event, widget, 'move')"
                                    @pointermove="onDrag"
                                    @pointerup="endDrag"
                                    @pointercancel="endDrag"
                                />

                                <div class="crm-bd-bar" @pointerdown="startDrag($event, widget, 'move')" @pointermove="onDrag" @pointerup="endDrag" @pointercancel="endDrag">
                                    <Icon icon="lucide:grip-vertical" class="crm-bd-grip" aria-hidden="true" />
                                    <span class="crm-bd-bar-type">{{ TYPE_META[widget.type]?.label ?? widget.type }}</span>
                                    <button
                                        type="button"
                                        class="crm-bd-w-x"
                                        aria-label="Remove widget"
                                        @pointerdown.stop
                                        @click.stop="removeWidget(widget.id)"
                                    >
                                        <Icon icon="lucide:x" />
                                    </button>
                                </div>

                                <span
                                    class="crm-bd-resize"
                                    role="presentation"
                                    @pointerdown.stop="startDrag($event, widget, 'resize')"
                                    @pointermove="onDrag"
                                    @pointerup="endDrag"
                                    @pointercancel="endDrag"
                                >
                                    <Icon icon="lucide:move-diagonal-2" aria-hidden="true" />
                                </span>
                            </article>
                        </div>

                        <div v-if="widgets.length === 0" class="crm-bd-empty">
                            <span class="crm-db-empty-icon" aria-hidden="true">
                                <Icon icon="lucide:layout-grid" />
                            </span>
                            <p class="crm-ov-title">Your canvas is empty</p>
                            <p class="crm-ov-sub">Choose “Add widget” to place your first report.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Inspector -->
            <aside class="crm-ov-card crm-bd-inspector" aria-label="Widget settings">
                <template v-if="selected">
                    <div class="crm-ov-head">
                        <div>
                            <h2 class="crm-ov-title">Widget settings</h2>
                            <p class="crm-ov-sub">Changes apply straight away.</p>
                        </div>
                    </div>

                    <div class="crm-bd-field">
                        <label for="sel-title">Title</label>
                        <input
                            id="sel-title"
                            v-model="selected.title"
                            type="text"
                            maxlength="255"
                            placeholder="Defaults to the report name"
                        />
                    </div>

                    <div class="crm-bd-field">
                        <span class="crm-bd-label">Type</span>
                        <div class="crm-bd-types crm-bd-types-sm" role="radiogroup" aria-label="Widget type">
                            <button
                                v-for="type in types"
                                :key="type.key"
                                type="button"
                                role="radio"
                                class="crm-bd-type"
                                :class="[`crm-dbt-${type.key}`, selected.type === type.key ? 'crm-bd-type-active' : '']"
                                :aria-checked="selected.type === type.key"
                                @click="selected.type = type.key"
                            >
                                <Icon :icon="type.icon" aria-hidden="true" />
                                <span class="crm-bd-type-name">{{ type.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="crm-bd-field">
                        <span class="crm-bd-label">Report</span>
                        <CrmSelect v-model="selected.report_id" aria-label="Report" :options="reportOptions" />
                        <InputError :message="form.errors[`widgets.${selectedIndex}.report_id`]" />
                    </div>

                    <div class="crm-bd-size">
                        <div class="crm-bd-field">
                            <label for="sel-w">Width</label>
                            <input
                                id="sel-w"
                                type="number"
                                :min="MIN_WIDTH"
                                :max="COLS - selected.col"
                                :value="selected.width"
                                @input="setNumber(selected.id, 'width', $event.target.value)"
                            />
                        </div>
                        <div class="crm-bd-field">
                            <label for="sel-h">Height</label>
                            <input
                                id="sel-h"
                                type="number"
                                :min="MIN_HEIGHT"
                                :max="MAX_HEIGHT"
                                :value="selected.height"
                                @input="setNumber(selected.id, 'height', $event.target.value)"
                            />
                        </div>
                    </div>

                    <button type="button" class="crm-db-ghost crm-db-ghost-danger" @click="removeWidget(selected.id)">
                        <Icon icon="lucide:trash-2" aria-hidden="true" />
                        Remove widget
                    </button>
                </template>

                <div v-else class="crm-bd-inspector-empty">
                    <span class="crm-db-empty-icon" aria-hidden="true">
                        <Icon icon="lucide:mouse-pointer-click" />
                    </span>
                    <p class="crm-ov-title">Select a widget</p>
                    <p class="crm-ov-sub">
                        Click one on the canvas to rename it, change its type or report, or set its exact size.
                    </p>
                </div>
            </aside>
        </div>

        <div class="crm-bd-actions">
            <button type="submit" class="crm-db-new" :disabled="form.processing">
                <Icon icon="lucide:save" aria-hidden="true" />
                {{ submitLabel }}
            </button>
            <Link :href="cancelHref" class="crm-db-ghost">Cancel</Link>
        </div>
    </form>
</template>
