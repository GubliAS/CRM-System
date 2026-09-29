<script setup>
/*
 * Donut with callout labels — a Vue port of the Invoice system's
 * TopSellingProductsCard pie (Recharts). Same geometry: 4° padding between
 * rounded-corner sectors, a ring whose inner radius is 69% of the outer, and
 * callouts laid out for all slices together (measured, word-wrapped, stacked
 * per side, leaders following arcs concentric with the ring). Falls back to
 * a plain legend when the card is too narrow for two label columns.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** [{ key, name, value, color, display }] — `display` is the formatted value. */
    slices: { type: Array, required: true },
    centerEyebrow: { type: String, default: '' },
    centerValue: { type: String, default: '' },
    centerSub: { type: String, default: '' },
});

const BASE = { chartW: 700, chartH: 464, innerR: 125, outerR: 180 };
const STYLE = { pctSize: 13.5, nameSize: 11, valSize: 10, nameLH: 12.5, valLH: 11.5, gap: 30 };

const RADIAN = Math.PI / 180;
const PAD_ANGLE = 4;
const CORNER_RADIUS = 11;
const SIDE_PAD = 8;
const V_PAD = 10;
const STACK_GAP = 10;
const STUB = 10;
const ARC_STEP = 5;
const RUN_IN = 12;
const MAX_NAME_LINES = 3;
const MIN_CALLOUT_OUTER_R = 72;
/** Load animation: one sweep round the ring, then the callouts fade in. */
const SWEEP_MS = 1400;
/** Wait for the card to finish rising in before the ring starts. */
const SWEEP_DELAY_MS = 450;

const shell = ref(null);
const shellW = ref(BASE.chartW);
const fontFamily = ref(null);
const fontsVersion = ref(0);
const tip = ref(null);
/** 0 → 1 over the load sweep; sectors are clipped to `sweep * 360°`. */
const sweep = ref(0);
let observer = null;
let visibility = null;
let frame = 0;

function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2;
}

function startSweep() {
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        sweep.value = 1;
        return;
    }

    const t0 = performance.now() + SWEEP_DELAY_MS;
    const step = (now) => {
        const t = Math.min(Math.max((now - t0) / SWEEP_MS, 0), 1);
        sweep.value = easeInOutCubic(t);
        if (t < 1) {
            frame = requestAnimationFrame(step);
        }
    };
    frame = requestAnimationFrame(step);
}

onMounted(() => {
    const el = shell.value;


    fontFamily.value = getComputedStyle(el ?? document.body).fontFamily;
    document.fonts?.ready.then(() => {
        fontsVersion.value++;
    });

    // Start the ring sweep when the donut scrolls into view, not on page load.
    if (el && typeof IntersectionObserver !== 'undefined') {
        visibility = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    visibility.disconnect();
                    startSweep();
                }
            },
            { threshold: 0.35 },
        );
        visibility.observe(el);
    } else {
        startSweep();
    }

    if (!el || typeof ResizeObserver === 'undefined') {
        return;
    }

    const update = () => {
        const w = el.getBoundingClientRect().width;
        if (Number.isFinite(w) && w >= 40) {
            shellW.value = Math.min(BASE.chartW, Math.floor(w));
        }
    };

    update();
    observer = new ResizeObserver(update);
    observer.observe(el);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    visibility?.disconnect();
    cancelAnimationFrame(frame);
});

/* Text measurement ------------------------------------------------------ */

const estimateText = (text, fontSize) => text.length * fontSize * 0.58;

const measure = computed(() => {
    // Re-measure once web fonts settle.
    void fontsVersion.value;

    if (!fontFamily.value || typeof document === 'undefined') {
        return estimateText;
    }

    const ctx = document.createElement('canvas').getContext('2d');
    if (!ctx) {
        return estimateText;
    }

    const cache = new Map();

    return (text, fontSize, fontWeight) => {
        const key = `${fontWeight}|${fontSize}|${text}`;
        let w = cache.get(key);
        if (w === undefined) {
            ctx.font = `${fontWeight} ${fontSize}px ${fontFamily.value}`;
            w = ctx.measureText(text).width;
            cache.set(key, w);
        }

        return w;
    };
});

function fitToWidth(text, maxW, size, weight, m) {
    if (m(text, size, weight) <= maxW) {
        return text;
    }

    let lo = 0;
    let hi = text.length;
    while (lo < hi) {
        const mid = (lo + hi + 1) >> 1;
        if (m(`${text.slice(0, mid).trimEnd()}…`, size, weight) <= maxW) {
            lo = mid;
        } else {
            hi = mid - 1;
        }
    }

    return `${text.slice(0, lo).trimEnd()}…`;
}

function wrapToWidth(text, maxW, maxLines, size, weight, m) {
    let rest = text.trim().split(/\s+/).filter(Boolean);
    if (rest.length === 0) {
        return [''];
    }

    const lines = [];
    while (rest.length > 0) {
        if (lines.length === maxLines - 1) {
            lines.push(fitToWidth(rest.join(' '), maxW, size, weight, m));
            break;
        }
        let n = 1;
        while (n < rest.length && m(rest.slice(0, n + 1).join(' '), size, weight) <= maxW) {
            n++;
        }
        lines.push(fitToWidth(rest.slice(0, n).join(' '), maxW, size, weight, m));
        rest = rest.slice(n);
    }

    return lines;
}

/* Sector math (Recharts: startAngle 0 at 3 o'clock, counter-clockwise) -- */

function sliceAngles(values) {
    const total = values.reduce((s, v) => s + v, 0);
    const pad = values.length <= 1 ? 0 : PAD_ANGLE;
    const nonZero = values.filter((v) => v !== 0).length;
    const realTotal = 360 - nonZero * pad;
    const out = [];
    let prevEnd = 0;

    values.forEach((v, i) => {
        const start = i ? prevEnd + pad * (v !== 0 ? 1 : 0) : 0;
        const end = start + (total > 0 ? v / total : 0) * realTotal;
        out.push({ start, end, mid: (start + end) / 2 });
        prevEnd = end;
    });

    return out;
}

function polar(cx, cy, r, deg) {
    return [cx + r * Math.cos(deg * RADIAN), cy - r * Math.sin(deg * RADIAN)];
}

function fmt(point) {
    return `${point[0].toFixed(2)},${point[1].toFixed(2)}`;
}

/** Annular sector with rounded corners, as Recharts draws with `cornerRadius`. */
function sectorPath(cx, cy, r, R, a0, a1) {
    const span = a1 - a0;
    let rc = Math.min(CORNER_RADIUS, (R - r) / 2);
    const thetaO = (c) => Math.asin(c / (R - c)) / RADIAN;
    while (rc > 0.5 && 2 * thetaO(rc) > span) {
        rc -= 0.5;
    }

    if (rc <= 0.5) {
        const large = span > 180 ? 1 : 0;
        return [
            `M${fmt(polar(cx, cy, R, a0))}`,
            `A${R},${R} 0 ${large} 0 ${fmt(polar(cx, cy, R, a1))}`,
            `L${fmt(polar(cx, cy, r, a1))}`,
            `A${r},${r} 0 ${large} 1 ${fmt(polar(cx, cy, r, a0))}`,
            'Z',
        ].join(' ');
    }

    const tO = thetaO(rc);
    const tI = Math.asin(rc / (r + rc)) / RADIAN;
    const dO = Math.sqrt((R - rc) ** 2 - rc ** 2);
    const dI = Math.sqrt((r + rc) ** 2 - rc ** 2);
    const largeO = span - 2 * tO > 180 ? 1 : 0;
    const largeI = span - 2 * tI > 180 ? 1 : 0;

    return [
        `M${fmt(polar(cx, cy, R, a0 + tO))}`,
        `A${R},${R} 0 ${largeO} 0 ${fmt(polar(cx, cy, R, a1 - tO))}`,
        `A${rc},${rc} 0 0 0 ${fmt(polar(cx, cy, dO, a1))}`,
        `L${fmt(polar(cx, cy, dI, a1))}`,
        `A${rc},${rc} 0 0 0 ${fmt(polar(cx, cy, r, a1 - tI))}`,
        `A${r},${r} 0 ${largeI} 1 ${fmt(polar(cx, cy, r, a0 + tI))}`,
        `A${rc},${rc} 0 0 0 ${fmt(polar(cx, cy, dI, a0))}`,
        `L${fmt(polar(cx, cy, dO, a0))}`,
        `A${rc},${rc} 0 0 0 ${fmt(polar(cx, cy, R, a0 + tO))}`,
        'Z',
    ].join(' ');
}

/* Callout layout (ported as-is) ----------------------------------------- */

function layoutCallouts({ chartW, maxOuterR, data, style, m }) {
    const total = data.reduce((s, d) => s + d.value, 0);
    if (data.length === 0 || total <= 0) {
        return null;
    }

    const labelW = Math.round(Math.min(150, Math.max(92, chartW * 0.2)));
    const outerR = Math.min(maxOuterR, Math.floor((chartW - 2 * SIDE_PAD - 2 * labelW - 2 * style.gap) / 2));
    if (outerR < MIN_CALLOUT_OUTER_R) {
        return null;
    }
    const innerR = Math.max(48, Math.round(outerR * 0.69));
    const cx = chartW / 2;
    const maxArcR = outerR + style.gap - RUN_IN - 4;

    const mids = sliceAngles(data.map((d) => d.value)).map((a) => a.mid);
    const blocks = data.map((d, i) => {
        const pct = `${Math.round((d.value / total) * 1000) / 10}%`;
        const nameLines = wrapToWidth(d.name, labelW, MAX_NAME_LINES, style.nameSize, 500, m);
        const value = fitToWidth(d.display, labelW, style.valSize, 500, m);
        const width = Math.max(
            m(pct, style.pctSize, 700),
            ...nameLines.map((l) => m(l, style.nameSize, 500)),
            m(value, style.valSize, 500),
        );
        const nameBase = 5 + style.nameSize * 0.8;
        const valueBase = nameBase + (nameLines.length - 1) * style.nameLH + style.valLH + 1;
        const cos = Math.cos(RADIAN * mids[i]);
        const sin = -Math.sin(RADIAN * mids[i]);

        return {
            key: d.key,
            color: d.color,
            pct,
            nameLines,
            value,
            width,
            nameBase,
            valueBase,
            topOff: 5 + style.pctSize * 0.8,
            botOff: valueBase + style.valSize * 0.3,
            cos,
            sin,
            side: cos >= 0 ? 1 : -1,
        };
    });

    const sides = [1, -1].map((side) => blocks.filter((b) => b.side === side));
    const stackH = Math.max(
        ...sides.map(
            (items) =>
                items.reduce((s, b) => s + b.topOff + b.botOff, 0) +
                Math.max(0, items.length - 1) * STACK_GAP,
        ),
    );
    const chartH = Math.ceil(Math.max(2 * (outerR + STUB + 4), stackH) + 2 * V_PAD);
    const cy = chartH / 2;

    const callouts = [];
    for (const items of sides) {
        if (items.length === 0) {
            continue;
        }
        const side = items[0].side;
        const sorted = items
            .map((b) => ({ b, want: cy + (outerR + STUB) * b.sin }))
            .sort((p, q) => p.want - q.want);
        const ty = sorted.map((s) => s.want);

        for (let i = 0; i < sorted.length; i++) {
            const min = i
                ? ty[i - 1] + sorted[i - 1].b.botOff + STACK_GAP + sorted[i].b.topOff
                : V_PAD + sorted[i].b.topOff;
            ty[i] = Math.max(ty[i], min);
        }
        for (let i = sorted.length - 1; i >= 0; i--) {
            const max =
                i === sorted.length - 1
                    ? chartH - V_PAD - sorted[i].b.botOff
                    : ty[i + 1] - sorted[i + 1].b.topOff - STACK_GAP - sorted[i].b.botOff;
            ty[i] = Math.min(ty[i], max);
        }

        const arcR = sorted.map(() => outerR + STUB);
        const down = sorted.map((_, i) => i).filter((i) => ty[i] - sorted[i].want > 0.5);
        const up = sorted.map((_, i) => i).filter((i) => ty[i] - sorted[i].want < -0.5);
        for (const group of [down.slice().reverse(), up]) {
            const step =
                group.length > 1 ? Math.min(ARC_STEP, (maxArcR - outerR - STUB) / (group.length - 1)) : 0;
            group.forEach((idx, k) => {
                arcR[idx] = outerR + STUB + step * k;
            });
        }
        const clearR = Math.max(...arcR) + 6;

        sorted.forEach(({ b }, i) => {
            const y = ty[i];
            const r = arcR[i];
            const dy = y - cy;

            const onArc = Math.abs(dy) < r - 1;
            const kx = onArc ? cx + side * Math.sqrt(r * r - dy * dy) : cx + side * 3;
            const ky = onArc ? y : cy + Math.sign(dy) * Math.sqrt(r * r - 9);

            const top = y - b.topOff;
            const bot = y + b.botOff;
            const dmin = top <= cy && bot >= cy ? 0 : Math.min(Math.abs(top - cy), Math.abs(bot - cy));
            const clearX = dmin < clearR ? Math.sqrt(clearR * clearR - dmin * dmin) : 0;
            const maxOff = chartW / 2 - SIDE_PAD - b.width;
            const off = Math.min(maxOff, Math.max(clearX, Math.abs(kx - cx) + RUN_IN));
            const tx = cx + side * off;

            const p0x = cx + (outerR + 2) * b.cos;
            const p0y = cy + (outerR + 2) * b.sin;
            const p1x = cx + r * b.cos;
            const p1y = cy + r * b.sin;
            let path = `M${p0x.toFixed(1)},${p0y.toFixed(1)} L${p1x.toFixed(1)},${p1y.toFixed(1)}`;
            if (Math.abs(ky - p1y) > 0.5 || Math.abs(kx - p1x) > 0.5) {
                let delta = Math.atan2(ky - cy, kx - cx) - Math.atan2(p1y - cy, p1x - cx);
                if (delta > Math.PI) delta -= 2 * Math.PI;
                if (delta <= -Math.PI) delta += 2 * Math.PI;
                path += ` A${r.toFixed(1)},${r.toFixed(1)} 0 0 ${delta > 0 ? 1 : 0} ${kx.toFixed(1)},${ky.toFixed(1)}`;
                if (Math.abs(ky - y) > 0.5) path += ` L${kx.toFixed(1)},${y.toFixed(1)}`;
            }
            path += ` L${(tx - side * 5).toFixed(1)},${y.toFixed(1)}`;

            callouts.push({ ...b, side, tx, ty: y, path });
        });
    }

    return { chartW, chartH, innerR, outerR, callouts };
}

/* Derived chart --------------------------------------------------------- */

const data = computed(() => props.slices.filter((s) => (Number(s.value) || 0) > 0));
const total = computed(() => data.value.reduce((s, d) => s + d.value, 0));

const calloutLayout = computed(() =>
    layoutCallouts({
        chartW: shellW.value,
        maxOuterR: BASE.outerR,
        data: data.value,
        style: STYLE,
        m: measure.value,
    }),
);

const showLegend = computed(() => calloutLayout.value === null);

const dims = computed(() => {
    if (calloutLayout.value) {
        return calloutLayout.value;
    }

    const scale = shellW.value / BASE.chartW;

    return {
        chartW: shellW.value,
        chartH: Math.max(220, Math.round(BASE.chartH * scale)),
        innerR: Math.max(48, Math.round(BASE.innerR * scale)),
        outerR: Math.max(70, Math.round(BASE.outerR * scale)),
    };
});

const sectors = computed(() => {
    const { chartW, chartH, innerR, outerR } = dims.value;
    const angles = sliceAngles(data.value.map((d) => d.value));

    // The sweep front reaches each sector in turn, so they fill one at a time.
    const front = sweep.value * 360;

    return data.value
        .map((d, i) => {
            const end = Math.min(angles[i].end, front);

            return {
                ...d,
                percent: total.value > 0 ? Math.round((d.value / total.value) * 1000) / 10 : 0,
                path:
                    end - angles[i].start > 0.3
                        ? sectorPath(chartW / 2, chartH / 2, innerR, outerR, angles[i].start, end)
                        : null,
            };
        });
});

function showTip(event, sector) {
    const box = shell.value?.getBoundingClientRect();
    if (!box) {
        return;
    }

    tip.value = {
        name: sector.name,
        value: sector.display,
        color: sector.color,
        x: event.clientX - box.left + 14,
        y: event.clientY - box.top + 14,
    };
}
</script>

<template>
    <div class="crm-donut">
        <div ref="shell" class="crm-donut-shell" @mouseleave="tip = null">
            <svg
                v-if="calloutLayout && sweep >= 1"
                class="crm-donut-callouts"
                :width="calloutLayout.chartW"
                :height="calloutLayout.chartH"
                :viewBox="`0 0 ${calloutLayout.chartW} ${calloutLayout.chartH}`"
                aria-hidden="true"
            >
                <g v-for="c in calloutLayout.callouts" :key="c.key">
                    <path
                        :d="c.path"
                        fill="none"
                        :style="{ stroke: c.color }"
                        stroke-width="1.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <g :text-anchor="c.side > 0 ? 'start' : 'end'">
                        <text
                            :x="c.tx"
                            :y="c.ty - 5"
                            :style="{ fill: c.color }"
                            :font-size="STYLE.pctSize"
                            font-weight="700"
                            letter-spacing="-0.02em"
                        >
                            {{ c.pct }}
                        </text>
                        <text
                            v-for="(line, i) in c.nameLines"
                            :key="`n-${i}`"
                            :x="c.tx"
                            :y="c.ty + c.nameBase + i * STYLE.nameLH"
                            fill="#64748b"
                            :font-size="STYLE.nameSize"
                            font-weight="500"
                        >
                            {{ line }}
                        </text>
                        <text
                            :x="c.tx"
                            :y="c.ty + c.valueBase"
                            fill="#94a3b8"
                            :font-size="STYLE.valSize"
                            font-weight="500"
                        >
                            {{ c.value }}
                        </text>
                    </g>
                </g>
            </svg>

            <svg
                class="crm-donut-ring"
                :width="dims.chartW"
                :height="dims.chartH"
                :viewBox="`0 0 ${dims.chartW} ${dims.chartH}`"
                role="img"
                :aria-label="sectors.map((s) => `${s.name} ${s.percent}%, ${s.display}`).join('; ')"
            >
                <path
                    v-for="sector in sectors.filter((s) => s.path)"
                    :key="sector.key"
                    class="crm-donut-sector"
                    :d="sector.path"
                    :style="{ fill: sector.color }"
                    @mousemove="showTip($event, sector)"
                />
            </svg>

            <div class="crm-donut-center-slot" aria-hidden="true">
                <div
                    class="crm-donut-center"
                    :style="{ maxWidth: `${Math.round(dims.innerR * 1.7)}px` }"
                >
                    <span v-if="centerEyebrow" class="crm-donut-center-eyebrow">{{ centerEyebrow }}</span>
                    <span class="crm-donut-center-amount">{{ centerValue }}</span>
                    <span v-if="centerSub" class="crm-donut-center-sub">{{ centerSub }}</span>
                </div>
            </div>

            <div
                v-if="tip"
                class="crm-donut-tip"
                :style="{ left: `${tip.x}px`, top: `${tip.y}px` }"
                aria-hidden="true"
            >
                <span class="crm-donut-tip-dot" :style="{ background: tip.color }" />
                {{ tip.name }}: <strong>{{ tip.value }}</strong>
            </div>
        </div>

        <ul v-if="showLegend" class="crm-donut-legend" role="list">
            <li v-for="sector in sectors" :key="`l-${sector.key}`" class="crm-donut-legend-item">
                <span class="crm-donut-legend-dot" :style="{ background: sector.color }" aria-hidden="true" />
                <span class="crm-donut-legend-name" :title="sector.name">{{ sector.name }}</span>
                <span class="crm-donut-legend-pct">{{ sector.percent }}%</span>
                <span class="crm-donut-legend-value">{{ sector.display }}</span>
            </li>
        </ul>
    </div>
</template>
