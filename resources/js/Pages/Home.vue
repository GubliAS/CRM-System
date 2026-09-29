<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDay, formatMoney } from '@/display';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const CLOSED_WON = 'Closed Won';
const CLOSED_LOST = 'Closed Lost';
const DONUT_RADIUS = 40;
const DONUT_CIRCUMFERENCE = 2 * Math.PI * DONUT_RADIUS;

const CHART_WIDTH = 640;
const CHART_HEIGHT = 280;
const CHART_PAD = { top: 16, right: 12, bottom: 36, left: 40 };

const props = defineProps({
    year: { type: Number, required: true },
    pipeline: { type: Array, required: true },
    pipelineTotal: { type: Number, required: true },
    revenueBySource: { type: Array, required: true },
    revenueBySourceTotal: { type: Number, required: true },
    tasksDueToday: { type: Array, required: true },
    eventsToday: { type: Array, required: true },
    keyOpportunities: { type: Array, required: true },
    recommendations: { type: Array, required: true },
});

const chartPeriod = ref('monthly');

const todayDate = computed(() => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
});

const openPipelineRows = computed(() =>
    props.pipeline.filter(
        (row) => row.stage !== CLOSED_WON && row.stage !== CLOSED_LOST,
    ),
);

const openPipelineValue = computed(() =>
    roundMoney(
        openPipelineRows.value.reduce(
            (sum, row) => sum + (Number(row.value) || 0),
            0,
        ),
    ),
);

const closedWon = computed(
    () => props.pipeline.find((row) => row.stage === CLOSED_WON) ?? null,
);

const closedLost = computed(
    () => props.pipeline.find((row) => row.stage === CLOSED_LOST) ?? null,
);

const wonValue = computed(() => Number(closedWon.value?.value) || 0);
const lostValue = computed(() => Number(closedLost.value?.value) || 0);

const winRate = computed(() => {
    const won = Number(closedWon.value?.count) || 0;
    const lost = Number(closedLost.value?.count) || 0;
    const closed = won + lost;

    if (closed === 0) {
        return null;
    }

    return roundPercent((won / closed) * 100);
});

const winRateLabel = computed(() => {
    if (winRate.value === null) {
        return '—';
    }

    return formatPercent(winRate.value);
});

const tasksDueCount = computed(() => props.tasksDueToday.length);
const eventsTodayCount = computed(() => props.eventsToday.length);
const pipelineIsEmpty = computed(() => Number(props.pipelineTotal) === 0);

const dealCountTotal = computed(() =>
    props.pipeline.reduce((sum, row) => sum + (Number(row.count) || 0), 0),
);

const chartHeadline = computed(() => {
    if (chartPeriod.value === 'daily') {
        return `Open deals : ${openPipelineRows.value.reduce((sum, row) => sum + (Number(row.count) || 0), 0)}`;
    }

    if (chartPeriod.value === 'weekly') {
        return `Open pipeline : ${formatMoney(openPipelineValue.value)}`;
    }

    return `Total pipeline : ${formatMoney(props.pipelineTotal)}`;
});

const showValueBars = computed(
    () => chartPeriod.value === 'weekly' || chartPeriod.value === 'monthly',
);

const showCountBars = computed(
    () => chartPeriod.value === 'daily' || chartPeriod.value === 'monthly',
);

const chartPlot = computed(() => {
    const innerWidth = CHART_WIDTH - CHART_PAD.left - CHART_PAD.right;
    const innerHeight = CHART_HEIGHT - CHART_PAD.top - CHART_PAD.bottom;
    const stages = props.pipeline;
    const count = Math.max(stages.length, 1);
    const groupWidth = innerWidth / count;
    const maxValue = Math.max(
        0,
        ...stages.map((row) => Number(row.value) || 0),
    );
    const maxCount = Math.max(
        0,
        ...stages.map((row) => Number(row.count) || 0),
    );
    const empty = pipelineIsEmpty.value;

    const dual = showValueBars.value && showCountBars.value;
    const barWidth = dual ? Math.min(14, groupWidth * 0.28) : Math.min(22, groupWidth * 0.45);
    const barGap = dual ? 4 : 0;

    const groups = stages.map((row, index) => {
        const value = Number(row.value) || 0;
        const deals = Number(row.count) || 0;
        const valueRatio = empty || maxValue <= 0 ? 0.12 : value / maxValue;
        const countRatio = empty || maxCount <= 0 ? 0.12 : deals / maxCount;
        const valueHeight = Math.max(valueRatio * innerHeight, empty ? innerHeight * 0.12 : value > 0 ? 6 : 2);
        const countHeight = Math.max(countRatio * innerHeight, empty ? innerHeight * 0.12 : deals > 0 ? 6 : 2);
        const centerX = CHART_PAD.left + groupWidth * index + groupWidth / 2;
        const pairWidth = dual ? barWidth * 2 + barGap : barWidth;
        const startX = centerX - pairWidth / 2;

        const bars = [];

        if (showValueBars.value) {
            bars.push({
                key: `${row.stage}-value`,
                className: empty || value <= 0 ? 'crm-home-bar-muted' : 'crm-home-bar-a',
                x: startX,
                y: CHART_PAD.top + innerHeight - valueHeight,
                width: barWidth,
                height: valueHeight,
            });
        }

        if (showCountBars.value) {
            bars.push({
                key: `${row.stage}-count`,
                className: empty || deals <= 0 ? 'crm-home-bar-muted' : 'crm-home-bar-b',
                x: startX + (showValueBars.value ? barWidth + barGap : 0),
                y: CHART_PAD.top + innerHeight - countHeight,
                width: barWidth,
                height: countHeight,
            });
        }

        return {
            stage: row.stage,
            href: row.href,
            label: stageShortLabel(row.stage),
            labelX: centerX,
            labelY: CHART_HEIGHT - 12,
            value,
            count: deals,
            percent: Number(row.percent) || 0,
            bars,
        };
    });

    const grid = [0, 0.33, 0.66, 1].map((ratio) => {
        const y = CHART_PAD.top + innerHeight * (1 - ratio);

        return {
            key: `grid-${ratio}`,
            y,
            label: empty
                ? ratio === 0
                    ? '0'
                    : ''
                : formatAxisTick(ratio, maxValue, maxCount),
        };
    });

    return {
        groups,
        grid,
        baselineY: CHART_PAD.top + innerHeight,
        left: CHART_PAD.left,
        right: CHART_WIDTH - CHART_PAD.right,
    };
});

const mixTotal = computed(
    () => openPipelineValue.value + wonValue.value + lostValue.value,
);

const mixSlices = computed(() => {
    const total = mixTotal.value;
    const raw = [
        { key: 'open', label: 'Open', value: openPipelineValue.value },
        { key: 'won', label: 'Won', value: wonValue.value },
        { key: 'lost', label: 'Lost', value: lostValue.value },
    ];

    if (total <= 0) {
        return [];
    }

    let offset = 0;

    return raw.map((slice, index) => {
        const percent = roundPercent((slice.value / total) * 100);
        const length = (percent / 100) * DONUT_CIRCUMFERENCE;
        const mapped = {
            ...slice,
            index,
            percent,
            length,
            offset: -offset,
            dasharray: `${length} ${DONUT_CIRCUMFERENCE - length}`,
        };
        offset += length;

        return mapped;
    });
});

const mixIsEmpty = computed(() => mixSlices.value.length === 0);

const donutCenterPercent = computed(() => {
    if (mixIsEmpty.value) {
        return null;
    }

    const won = mixSlices.value.find((slice) => slice.key === 'won');

    return won?.percent ?? mixSlices.value[0]?.percent ?? null;
});

function roundMoney(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
}

function roundPercent(value) {
    return Math.round((Number(value) || 0) * 10) / 10;
}

function formatPercent(value) {
    const amount = Number(value) || 0;

    return `${amount % 1 === 0 ? amount.toFixed(0) : amount.toFixed(1)}%`;
}

function formatAxisTick(ratio, maxValue, maxCount) {
    if (showValueBars.value && !showCountBars.value) {
        return abbreviateMoney(maxValue * ratio);
    }

    if (showCountBars.value && !showValueBars.value) {
        return String(Math.round(maxCount * ratio));
    }

    return abbreviateMoney(maxValue * ratio);
}

function abbreviateMoney(value) {
    const amount = Number(value) || 0;

    if (amount >= 1000000) {
        return `${(amount / 1000000).toFixed(amount >= 10000000 ? 0 : 1)}M`;
    }

    if (amount >= 1000) {
        return `${(amount / 1000).toFixed(amount >= 10000 ? 0 : 1)}k`;
    }

    return String(Math.round(amount));
}

function stageShortLabel(stage) {
    const labels = {
        Qualification: 'Qual',
        'Meeting Scheduled': 'Meet',
        'Proposal/Price Quote': 'Proposal',
        'Negotiation/Review': 'Negotiate',
        'Closed Won': 'Won',
        'Closed Lost': 'Lost',
    };

    return labels[stage] ?? stage;
}

function suggestionInitial(title) {
    const text = String(title || '').trim();

    if (!text) {
        return '?';
    }

    return text.charAt(0).toUpperCase();
}

function suggestionKind(recommendation) {
    if (recommendation.rule === 'inactive_account') {
        return 'Account';
    }

    if (recommendation.rule === 'stale_opportunity') {
        return 'Deal';
    }

    return 'Item';
}

function suggestionCode(recommendation) {
    const prefix =
        recommendation.recommendable_type === 'account' ? 'ACC' : 'OPP';

    return `${prefix}${String(recommendation.recommendable_id).padStart(5, '0')}`;
}

function dismissRecommendation(recommendation) {
    router.post(
        route('home.recommendations.dismiss'),
        {
            rule: recommendation.rule,
            recommendable_type: recommendation.recommendable_type,
            recommendable_id: recommendation.recommendable_id,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div class="crm-page py-5 sm:py-6">
            <div class="crm-home-stack">
                <header
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div class="min-w-0">
                        <h1 class="text-h1">Home</h1>
                        <p class="mt-1 text-body text-text-muted">
                            Pipeline and revenue for {{ year }}, plus today’s tasks and events.
                        </p>
                    </div>
                    <p
                        class="crm-chip inline-flex min-h-11 shrink-0 items-center px-3 text-small font-semibold"
                        aria-label="Reporting year"
                    >
                        Reporting year {{ year }}
                    </p>
                </header>

                <section class="crm-home-metrics" aria-label="Home summary">
                    <div
                        class="crm-home-metric crm-home-metric-primary"
                        role="status"
                    >
                        <span class="crm-home-metric-icon" aria-hidden="true">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 17l6-6 4 4 7-7"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M14 8h6v6"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="crm-home-metric-value tabular-nums">
                                {{ formatMoney(openPipelineValue) }}
                            </p>
                            <p class="crm-home-metric-label">Open pipeline</p>
                        </div>
                    </div>

                    <div class="crm-home-metric" role="status">
                        <span
                            class="crm-home-metric-icon crm-home-tile-lilac"
                            aria-hidden="true"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="crm-home-metric-value text-text tabular-nums">
                                {{ winRateLabel }}
                            </p>
                            <p class="crm-home-metric-label">Win rate</p>
                        </div>
                    </div>

                    <Link
                        :href="route('tasks.index', { view: 'today' })"
                        class="crm-home-metric focus:outline-none focus:ring-2 focus:ring-secondary"
                    >
                        <span
                            class="crm-home-metric-icon crm-home-tile-peach"
                            aria-hidden="true"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M4.5 6.75h15M6 6.75V5.25A1.5 1.5 0 017.5 3.75h9A1.5 1.5 0 0118 5.25v1.5"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="crm-home-metric-value text-text tabular-nums">
                                {{ tasksDueCount }}
                            </p>
                            <p class="crm-home-metric-label">Tasks due today</p>
                        </div>
                    </Link>

                    <Link
                        :href="
                            route('events.index', {
                                view: 'day',
                                date: todayDate,
                            })
                        "
                        class="crm-home-metric focus:outline-none focus:ring-2 focus:ring-secondary"
                    >
                        <span
                            class="crm-home-metric-icon crm-home-tile-sky"
                            aria-hidden="true"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0121 8.25v11.25A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5V8.25a1.5 1.5 0 011.5-1.5z"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="crm-home-metric-value text-text tabular-nums">
                                {{ eventsTodayCount }}
                            </p>
                            <p class="crm-home-metric-label">Events today</p>
                        </div>
                    </Link>
                </section>

                <div class="crm-home-split">
                    <section
                        class="crm-home-panel"
                        aria-labelledby="home-pipeline-heading"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <h2 id="home-pipeline-heading" class="text-h2">
                                    Pipeline overview
                                </h2>
                                <p class="crm-home-headline">
                                    {{ chartHeadline }}
                                </p>
                            </div>
                            <div
                                class="crm-home-period"
                                role="tablist"
                                aria-label="Pipeline chart period"
                            >
                                <button
                                    type="button"
                                    role="tab"
                                    class="crm-home-period-btn"
                                    :class="
                                        chartPeriod === 'daily'
                                            ? 'crm-home-period-btn-active'
                                            : ''
                                    "
                                    :aria-selected="chartPeriod === 'daily'"
                                    @click="chartPeriod = 'daily'"
                                >
                                    Daily
                                </button>
                                <button
                                    type="button"
                                    role="tab"
                                    class="crm-home-period-btn"
                                    :class="
                                        chartPeriod === 'weekly'
                                            ? 'crm-home-period-btn-active'
                                            : ''
                                    "
                                    :aria-selected="chartPeriod === 'weekly'"
                                    @click="chartPeriod = 'weekly'"
                                >
                                    Weekly
                                </button>
                                <button
                                    type="button"
                                    role="tab"
                                    class="crm-home-period-btn"
                                    :class="
                                        chartPeriod === 'monthly'
                                            ? 'crm-home-period-btn-active'
                                            : ''
                                    "
                                    :aria-selected="chartPeriod === 'monthly'"
                                    @click="chartPeriod = 'monthly'"
                                >
                                    Monthly
                                </button>
                            </div>
                        </div>

                        <div class="crm-home-legend" aria-hidden="true">
                            <span
                                v-if="showValueBars"
                                class="crm-home-legend-item"
                            >
                                <span class="crm-home-swatch crm-home-swatch-a" />
                                Pipeline value
                            </span>
                            <span
                                v-if="showCountBars"
                                class="crm-home-legend-item"
                            >
                                <span class="crm-home-swatch crm-home-swatch-b" />
                                Deal count
                            </span>
                        </div>

                        <p
                            v-if="pipelineIsEmpty"
                            class="mb-2 rounded-xl bg-bg px-3 py-2 text-small text-text-muted"
                            role="status"
                        >
                            No pipeline opportunities for {{ year }}. Stage bars still link to filtered lists.
                        </p>

                        <div
                            class="crm-home-bars"
                            role="img"
                            :aria-label="`Pipeline chart for ${year}. ${dealCountTotal} deals totaling ${formatMoney(pipelineTotal)}.`"
                        >
                            <svg
                                :viewBox="`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`"
                                preserveAspectRatio="xMidYMid meet"
                            >
                                <line
                                    v-for="line in chartPlot.grid"
                                    :key="line.key"
                                    class="crm-home-bars-grid"
                                    :x1="chartPlot.left"
                                    :x2="chartPlot.right"
                                    :y1="line.y"
                                    :y2="line.y"
                                />
                                <text
                                    v-for="line in chartPlot.grid"
                                    :key="`${line.key}-label`"
                                    class="crm-home-bars-axis"
                                    :x="chartPlot.left - 8"
                                    :y="line.y + 3"
                                    text-anchor="end"
                                >
                                    {{ line.label }}
                                </text>

                                <g
                                    v-for="group in chartPlot.groups"
                                    :key="group.stage"
                                    class="cursor-pointer"
                                    role="link"
                                    tabindex="0"
                                    :aria-label="`${group.stage}: ${group.count} opportunities, ${formatMoney(group.value)}, ${formatPercent(group.percent)} of pipeline`"
                                    @click="router.visit(group.href)"
                                    @keydown.enter.prevent="router.visit(group.href)"
                                    @keydown.space.prevent="router.visit(group.href)"
                                >
                                    <rect
                                        v-for="bar in group.bars"
                                        :key="bar.key"
                                        :class="bar.className"
                                        :x="bar.x"
                                        :y="bar.y"
                                        :width="bar.width"
                                        :height="bar.height"
                                        rx="4"
                                        ry="4"
                                    />
                                    <text
                                        class="crm-home-bars-axis"
                                        :x="group.labelX"
                                        :y="group.labelY"
                                        text-anchor="middle"
                                    >
                                        {{ group.label }}
                                    </text>
                                </g>
                            </svg>
                        </div>
                    </section>

                    <section
                        class="crm-home-panel flex flex-col"
                        aria-labelledby="home-mix-heading"
                    >
                        <div
                            class="flex items-start justify-between gap-2"
                        >
                            <div>
                                <h2 id="home-mix-heading" class="text-h2">
                                    Pipeline mix
                                </h2>
                                <p class="mt-0.5 text-small text-text-muted">
                                    Open · won · lost · {{ year }}
                                </p>
                            </div>
                            <span class="crm-home-menu" aria-hidden="true">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-5 w-5"
                                >
                                    <circle cx="12" cy="5" r="1.5" />
                                    <circle cx="12" cy="12" r="1.5" />
                                    <circle cx="12" cy="19" r="1.5" />
                                </svg>
                            </span>
                        </div>

                        <p
                            v-if="mixIsEmpty"
                            class="mt-3 rounded-xl bg-bg px-3 py-2 text-small text-text-muted"
                            role="status"
                        >
                            No opportunity amounts for {{ year }}.
                        </p>

                        <div
                            class="crm-home-donut-wrap"
                            :class="mixIsEmpty ? 'opacity-80' : ''"
                            role="img"
                            :aria-label="
                                mixIsEmpty
                                    ? 'Empty pipeline mix chart'
                                    : `Pipeline mix: ${mixSlices.map((s) => `${s.label} ${formatPercent(s.percent)}`).join(', ')}`
                            "
                        >
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <circle
                                    class="crm-home-donut-track"
                                    cx="60"
                                    cy="60"
                                    :r="DONUT_RADIUS"
                                />
                                <circle
                                    v-for="slice in mixSlices"
                                    :key="slice.key"
                                    class="crm-home-donut-seg"
                                    :class="`crm-home-donut-seg-${slice.index}`"
                                    cx="60"
                                    cy="60"
                                    :r="DONUT_RADIUS"
                                    :style="{
                                        strokeDasharray: slice.dasharray,
                                        strokeDashoffset: slice.offset,
                                    }"
                                />
                            </svg>
                            <div class="crm-home-donut-center">
                                <p class="crm-home-donut-pct tabular-nums">
                                    {{
                                        donutCenterPercent === null
                                            ? '—'
                                            : formatPercent(donutCenterPercent)
                                    }}
                                </p>
                                <p class="crm-home-donut-caption">
                                    {{ mixIsEmpty ? 'No pipeline yet' : 'Won share' }}
                                </p>
                            </div>
                        </div>

                        <ul
                            v-if="!mixIsEmpty"
                            class="crm-home-donut-legend"
                            role="list"
                        >
                            <li
                                v-for="slice in mixSlices"
                                :key="`legend-${slice.key}`"
                                class="crm-home-legend-item"
                            >
                                <span
                                    class="crm-home-swatch"
                                    :class="`crm-home-swatch-${slice.index}`"
                                    aria-hidden="true"
                                />
                                <span class="text-text">{{ slice.label }}</span>
                                <span class="tabular-nums">{{
                                    formatPercent(slice.percent)
                                }}</span>
                            </li>
                        </ul>
                    </section>
                </div>

                <div class="crm-home-split">
                    <section
                        class="crm-home-panel"
                        aria-labelledby="home-deals-heading"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 pb-2"
                        >
                            <h2 id="home-deals-heading" class="text-h2">
                                Latest deals
                            </h2>
                            <Link
                                :href="route('opportunities.index')"
                                class="inline-flex min-h-11 items-center text-body text-secondary underline"
                            >
                                View all
                            </Link>
                        </div>

                        <p
                            v-if="keyOpportunities.length === 0"
                            class="mt-2 rounded-xl bg-bg px-3 py-2 text-small text-text-muted"
                            role="status"
                        >
                            No open opportunities.
                        </p>

                        <div v-else class="mt-1 overflow-x-auto">
                            <table class="crm-home-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Code</th>
                                        <th scope="col">Deal</th>
                                        <th scope="col">Close date</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Stage</th>
                                        <th scope="col">Account</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="opportunity in keyOpportunities"
                                        :key="opportunity.id"
                                    >
                                        <td>
                                            <Link
                                                :href="opportunity.url"
                                                class="crm-home-code"
                                            >
                                                #{{ opportunity.id }}
                                            </Link>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="crm-home-thumb"
                                                    aria-hidden="true"
                                                >
                                                    {{
                                                        suggestionInitial(
                                                            opportunity.name,
                                                        )
                                                    }}
                                                </span>
                                                <Link
                                                    :href="opportunity.url"
                                                    class="font-semibold text-text hover:text-secondary"
                                                >
                                                    {{ opportunity.name }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="text-text-muted">
                                            {{ formatDay(opportunity.close_date) }}
                                        </td>
                                        <td class="font-semibold tabular-nums text-text">
                                            {{ formatMoney(opportunity.amount) }}
                                        </td>
                                        <td class="text-text-muted">
                                            {{ opportunity.stage }}
                                        </td>
                                        <td>
                                            <Link
                                                v-if="opportunity.account"
                                                :href="
                                                    route(
                                                        'accounts.show',
                                                        opportunity.account.id,
                                                    )
                                                "
                                                class="text-secondary"
                                            >
                                                {{ opportunity.account.name }}
                                            </Link>
                                            <span v-else class="text-text-muted"
                                                >—</span
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section
                        class="crm-home-panel"
                        aria-labelledby="home-assistant-heading"
                    >
                        <div
                            class="flex items-start justify-between gap-2 pb-1"
                        >
                            <div>
                                <h2 id="home-assistant-heading" class="text-h2">
                                    Assistant
                                </h2>
                                <p class="mt-0.5 text-small text-text-muted">
                                    Follow-ups to review
                                </p>
                            </div>
                            <span class="crm-home-menu" aria-hidden="true">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-5 w-5"
                                >
                                    <circle cx="12" cy="5" r="1.5" />
                                    <circle cx="12" cy="12" r="1.5" />
                                    <circle cx="12" cy="19" r="1.5" />
                                </svg>
                            </span>
                        </div>

                        <p
                            v-if="recommendations.length === 0"
                            class="mt-3 rounded-xl bg-bg px-3 py-2 text-small text-text-muted"
                            role="status"
                        >
                            No recommendations right now.
                        </p>

                        <ul v-else role="list">
                            <li
                                v-for="recommendation in recommendations"
                                :key="recommendation.key"
                                class="crm-home-suggest"
                            >
                                <span class="crm-home-avatar" aria-hidden="true">
                                    {{ suggestionInitial(recommendation.title) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="recommendation.url"
                                        class="block truncate text-body font-semibold text-text hover:text-secondary"
                                    >
                                        {{ recommendation.title }}
                                    </Link>
                                    <p class="mt-0.5 truncate text-small text-text-muted">
                                        {{ recommendation.message }}
                                    </p>
                                    <button
                                        type="button"
                                        class="mt-1 text-small text-secondary underline"
                                        :aria-label="`Dismiss recommendation for ${recommendation.title}`"
                                        @click="
                                            dismissRecommendation(recommendation)
                                        "
                                    >
                                        Dismiss
                                    </button>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="crm-home-suggest-id">
                                        {{ suggestionCode(recommendation) }}
                                    </span>
                                    <span class="text-small text-text-muted">
                                        {{ suggestionKind(recommendation) }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
