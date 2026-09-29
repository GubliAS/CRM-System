import { display } from '@/display';

/*
 * Shared by the dashboard viewer and the builder, so a widget looks the same
 * while you are placing it as it does once the dashboard is saved.
 */

export const RING_RADIUS = 46;
export const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

// Metric and gauge widgets take these looks in turn.
export const STAT_VARIANTS = ['dark', 'blue', 'soft'];

export function formatNumber(value) {
    const number = Number(value);

    if (value === null || value === undefined || value === '' || Number.isNaN(number)) {
        return display(value);
    }

    return number.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

/** The gauge ring is drawn relative to the biggest number on the dashboard. */
export function maxValueOf(widgets) {
    let max = 1;

    for (const widget of widgets) {
        for (const point of widget.result?.chart?.points ?? []) {
            max = Math.max(max, Number(point.value) || 0);
        }

        if (widget.type === 'gauge' || widget.type === 'metric') {
            max = Math.max(max, Number(widget.result?.value) || 0);
        }
    }

    return max;
}

/**
 * Turns widgets (as returned by the runner, or a builder preview) into cards:
 * stat look, chart geometry and gauge ring. `widgets` must already be in
 * reading order so the stat colours are handed out top-to-bottom.
 */
export function buildCards(widgets) {
    const max = maxValueOf(widgets);
    let statIndex = 0;

    return widgets.map((widget) => {
        const layout = widget.layout ?? {};
        const card = {
            widget,
            style: {
                '--c': (layout.col ?? 0) + 1,
                '--s': layout.width ?? 6,
                '--r': (layout.row ?? 0) + 1,
                '--h': layout.height ?? 3,
            },
            narrow: (layout.width ?? 6) <= 4,
        };

        if (widget.type === 'metric' || widget.type === 'gauge') {
            card.variant = STAT_VARIANTS[statIndex++ % STAT_VARIANTS.length];
        }

        if (widget.type === 'chart') {
            const points = widget.result?.chart?.points ?? [];
            const top = Math.max(1, ...points.map((p) => Number(p.value) || 0));

            card.bars = points.map((point, i) => {
                const value = Number(point.value) || 0;

                return {
                    key: `${widget.id}-${i}`,
                    label: String(point.label ?? ''),
                    value,
                    fill: Math.max((value / top) * 100, value > 0 ? 4 : 0),
                };
            });
        }

        if (widget.type === 'gauge') {
            const pct = Math.min(100, Math.round(((Number(widget.result?.value) || 0) / Math.max(max, 1)) * 100));

            card.pct = pct;
            card.dash = `${(pct / 100) * RING_CIRCUMFERENCE} ${RING_CIRCUMFERENCE}`;
        }

        return card;
    });
}
