/*
 * Dashboard grid maths. A dashboard is a 12-column grid; each widget stores
 * `row`, `col`, `width`, `height` in grid cells (the backend validates the same
 * ranges: col 0-11, width 1-12, height 1-8, row 0-100).
 */

export const COLS = 12;
export const MAX_ROWS = 100;
export const MIN_WIDTH = 2;
export const MIN_HEIGHT = 2;
export const MAX_HEIGHT = 8;

export const TYPE_DEFAULTS = {
    metric: { width: 4, height: 2 },
    gauge: { width: 4, height: 2 },
    chart: { width: 6, height: 4 },
    table: { width: 12, height: 4 },
};

export const TYPE_META = {
    metric: { label: 'Metric', icon: 'lucide:hash', hint: 'One big number' },
    gauge: { label: 'Gauge', icon: 'lucide:gauge', hint: 'Number as a ring' },
    chart: { label: 'Chart', icon: 'lucide:chart-column', hint: 'Bars by group' },
    table: { label: 'Table', icon: 'lucide:table-2', hint: 'Rows of records' },
};

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

export function clampWidget(widget) {
    const width = clamp(Math.round(widget.width ?? 6), MIN_WIDTH, COLS);
    const height = clamp(Math.round(widget.height ?? 3), 1, MAX_HEIGHT);

    return {
        ...widget,
        width,
        height,
        col: clamp(Math.round(widget.col ?? 0), 0, COLS - width),
        row: clamp(Math.round(widget.row ?? 0), 0, MAX_ROWS),
    };
}

export function overlaps(a, b) {
    return (
        a.col < b.col + b.width &&
        b.col < a.col + a.width &&
        a.row < b.row + b.height &&
        b.row < a.row + a.height
    );
}

/**
 * Removes overlaps by pushing widgets down. The pinned widget (the one being
 * dragged or resized) stays exactly where the user put it.
 */
export function resolve(widgets, pinnedId = null) {
    const list = widgets.map((widget) => ({ ...widget }));
    const pinned = list.find((widget) => widget.id === pinnedId);
    const placed = pinned ? [pinned] : [];
    const rest = list
        .filter((widget) => widget !== pinned)
        .sort((a, b) => a.row - b.row || a.col - b.col);

    for (const widget of rest) {
        let hit = placed.find((other) => overlaps(widget, other));

        while (hit) {
            widget.row = hit.row + hit.height;
            hit = placed.find((other) => overlaps(widget, other));
        }

        placed.push(widget);
    }

    return list;
}

/** Pulls every widget as far up as it can go without overlapping. */
export function compact(widgets) {
    const list = widgets
        .map((widget) => ({ ...widget }))
        .sort((a, b) => a.row - b.row || a.col - b.col);
    const placed = [];

    for (const widget of list) {
        widget.row = 0;
        let hit = placed.find((other) => overlaps(widget, other));

        while (hit) {
            widget.row = hit.row + hit.height;
            hit = placed.find((other) => overlaps(widget, other));
        }

        placed.push(widget);
    }

    return list;
}

export function bottomRow(widgets) {
    return widgets.reduce((max, widget) => Math.max(max, widget.row + widget.height), 0);
}

export function newWidgetId() {
    return `w-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

/** Reading order, for phones where the grid collapses to one column. */
export function readingOrder(widgets) {
    return [...widgets].sort((a, b) => (a.row ?? 0) - (b.row ?? 0) || (a.col ?? 0) - (b.col ?? 0));
}
