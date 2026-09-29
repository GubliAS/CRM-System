<script setup>
import PipelineDonut from '@/Components/PipelineDonut.vue';
import TableHeadIcon from '@/Components/TableHeadIcon.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDay, formatMoney } from '@/display';
import { Icon } from '@iconify/vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const CLOSED_WON = 'Closed Won';
const CLOSED_LOST = 'Closed Lost';
// Open stages in pipeline order (OpportunityStage::PROBABILITIES).
const OPEN_STAGES = [
    'Qualification',
    'Meeting Scheduled',
    'Proposal/Price Quote',
    'Negotiation/Review',
];

// Curve geometry (SVG user units, stretched to the card width).
const CURVE_WIDTH = 300;
const CURVE_HEIGHT = 88;
const CURVE_PAD = 6;

// Pie colors follow the CRM tokens (the invoice pie used its own palette).
const MIX_COLORS = {
    open: 'var(--color-secondary)',
    won: 'var(--color-primary)',
    lost: 'var(--color-chart-c)',
};

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

// Row action menu: fixed-positioned (like the Invoice system's portalled menu)
// so the table's horizontal scroll container can't clip it.
const rowMenu = ref(null);

function toggleRowMenu(event, opportunity) {
    if (rowMenu.value?.deal.id === opportunity.id) {
        rowMenu.value = null;
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const width = 176;

    rowMenu.value = {
        deal: opportunity,
        top: rect.bottom + 4,
        left: Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8)),
    };
}

function closeRowMenu() {
    rowMenu.value = null;
}

function onDocumentKey(event) {
    if (event.key === 'Escape') {
        closeRowMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onDocumentKey);
    window.addEventListener('scroll', closeRowMenu, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onDocumentKey);
    window.removeEventListener('scroll', closeRowMenu, true);
});

const chartMetric = ref('value');
const hoveredStage = ref(null);

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

const openDealCount = computed(() =>
    openPipelineRows.value.reduce((sum, row) => sum + (Number(row.count) || 0), 0),
);

const closedWon = computed(
    () => props.pipeline.find((row) => row.stage === CLOSED_WON) ?? null,
);

const closedLost = computed(
    () => props.pipeline.find((row) => row.stage === CLOSED_LOST) ?? null,
);

const wonValue = computed(() => Number(closedWon.value?.value) || 0);
const lostValue = computed(() => Number(closedLost.value?.value) || 0);
const wonCount = computed(() => Number(closedWon.value?.count) || 0);
const lostCount = computed(() => Number(closedLost.value?.count) || 0);

const winRate = computed(() => {
    const closed = wonCount.value + lostCount.value;

    if (closed === 0) {
        return null;
    }

    return roundPercent((wonCount.value / closed) * 100);
});

const winRateLabel = computed(() =>
    winRate.value === null ? '—' : formatPercent(winRate.value),
);

const dealCountTotal = computed(() =>
    props.pipeline.reduce((sum, row) => sum + (Number(row.count) || 0), 0),
);

const pipelineIsEmpty = computed(() => Number(props.pipelineTotal) === 0);
const revenueIsEmpty = computed(() => Number(props.revenueBySourceTotal) === 0);

/* Stage column chart ---------------------------------------------------- */

const stageBars = computed(() => {
    const metric = chartMetric.value === 'count' ? 'count' : 'value';
    const max = Math.max(0, ...props.pipeline.map((row) => Number(row[metric]) || 0));

    return props.pipeline.map((row) => {
        const amount = Number(row[metric]) || 0;

        return {
            stage: row.stage,
            href: row.href,
            label: stageShortLabel(row.stage),
            value: Number(row.value) || 0,
            count: Number(row.count) || 0,
            percent: Number(row.percent) || 0,
            fill: max > 0 ? Math.max((amount / max) * 100, amount > 0 ? 4 : 0) : 0,
        };
    });
});

/* Pipeline mix donut ---------------------------------------------------- */

const mixTotal = computed(
    () => openPipelineValue.value + wonValue.value + lostValue.value,
);

const mixSlices = computed(() => [
    { key: 'open', name: 'Open pipeline', value: openPipelineValue.value },
    { key: 'won', name: 'Closed won', value: wonValue.value },
    { key: 'lost', name: 'Closed lost', value: lostValue.value },
].map((slice) => ({
    ...slice,
    color: MIX_COLORS[slice.key],
    display: formatMoney(slice.value),
})));

const mixIsEmpty = computed(() => mixTotal.value <= 0);

const mixCenterValue = computed(() =>
    mixTotal.value.toLocaleString(undefined, {
        maximumFractionDigits: mixTotal.value >= 1000 ? 0 : 2,
    }),
);

/* Next key deal to close ------------------------------------------------ */

const nextDeal = computed(() => {
    const today = new Date(`${todayDate.value}T00:00:00`);
    const dated = props.keyOpportunities
        .filter((deal) => deal.close_date)
        .map((deal) => ({
            ...deal,
            days: Math.round(
                (new Date(`${deal.close_date}T00:00:00`) - today) / 86400000,
            ),
        }));

    if (dated.length === 0) {
        return null;
    }

    // Soonest upcoming close; if everything is overdue, the most recent miss.
    const upcoming = dated.filter((deal) => deal.days >= 0).sort((a, b) => a.days - b.days);
    const deal = upcoming[0] ?? dated.sort((a, b) => b.days - a.days)[0];

    return { ...deal, stageIndex: Math.max(OPEN_STAGES.indexOf(deal.stage), 0) };
});

/* Side cards ------------------------------------------------------------ */

const closedSplit = computed(() => {
    const closed = wonValue.value + lostValue.value;

    if (closed <= 0) {
        return null;
    }

    const lost = roundPercent((lostValue.value / closed) * 100);

    return { lost, won: roundPercent(100 - lost) };
});

const sourceBars = computed(() => {
    const rows = props.revenueBySource;
    const max = Math.max(0, ...rows.map((row) => Number(row.value) || 0));
    const topIndex = rows.findIndex((row) => (Number(row.value) || 0) === max);

    return rows.map((row, index) => {
        const value = Number(row.value) || 0;

        return {
            source: row.source,
            value,
            percent: Number(row.percent) || 0,
            height: max > 0 ? `${Math.max((value / max) * 100, value > 0 ? 6 : 0)}%` : '0%',
            top: max > 0 && index === topIndex,
        };
    });
});

const sourceAverageBottom = computed(() => {
    const rows = props.revenueBySource;
    const max = Math.max(0, ...rows.map((row) => Number(row.value) || 0));

    if (rows.length < 2 || max <= 0) {
        return null;
    }

    const avg = rows.reduce((sum, row) => sum + (Number(row.value) || 0), 0) / rows.length;

    // Mini bars sit above a ~1.45rem label row; keep the line inside the bar area.
    return `calc(1.45rem + (100% - 1.45rem) * ${avg / max})`;
});

const stageCurve = computed(() => {
    const counts = props.pipeline.map((row) => Number(row.count) || 0);

    if (counts.length < 2) {
        return null;
    }

    const max = Math.max(1, ...counts);
    const step = (CURVE_WIDTH - CURVE_PAD * 2) / (counts.length - 1);
    const points = counts.map((count, index) => ({
        x: CURVE_PAD + step * index,
        y: CURVE_PAD + (CURVE_HEIGHT - CURVE_PAD * 2) * (1 - count / max),
    }));

    const line = smoothPath(points);
    const last = points[points.length - 1];
    const area = `${line} L ${last.x} ${CURVE_HEIGHT} L ${points[0].x} ${CURVE_HEIGHT} Z`;

    return { line, area, last };
});

/* Today list ------------------------------------------------------------ */

const todayItems = computed(() => {
    const tasks = props.tasksDueToday.map((task) => ({
        key: `task-${task.id}`,
        kind: 'task',
        title: task.subject,
        meta: task.related_label || `Priority: ${task.priority ?? '—'}`,
        tag: task.status,
        url: task.url,
    }));

    const events = props.eventsToday.map((event) => ({
        key: `event-${event.id}`,
        kind: 'event',
        title: event.subject,
        meta: event.location || event.related_label || 'Event',
        tag: event.all_day ? 'All day' : formatClock(event.starts_at),
        url: event.url,
    }));

    return [...events, ...tasks].slice(0, 6);
});

/* Helpers --------------------------------------------------------------- */

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

function formatClock(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(date);
}

function stageShortLabel(stage) {
    const labels = {
        Qualification: 'Qualify',
        'Meeting Scheduled': 'Meeting',
        'Proposal/Price Quote': 'Proposal',
        'Negotiation/Review': 'Negotiate',
        'Closed Won': 'Won',
        'Closed Lost': 'Lost',
    };

    return labels[stage] ?? stage;
}

// Catmull-Rom through the points, emitted as cubic Béziers.
function smoothPath(points) {
    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i - 1] ?? points[i];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2] ?? p2;
        const c1x = p1.x + (p2.x - p0.x) / 6;
        const c1y = p1.y + (p2.y - p0.y) / 6;
        const c2x = p2.x - (p3.x - p1.x) / 6;
        const c2y = p2.y - (p3.y - p1.y) / 6;

        d += ` C ${c1x} ${c1y}, ${c2x} ${c2y}, ${p2.x} ${p2.y}`;
    }

    return d;
}

function initial(text) {
    const value = String(text || '').trim();

    return value ? value.charAt(0).toUpperCase() : '?';
}

const STAGE_TAGS = {
    Qualification: 'crm-ov-tag-draft',
    'Meeting Scheduled': 'crm-ov-tag-pending',
    'Proposal/Price Quote': 'crm-ov-tag-open',
    'Negotiation/Review': 'crm-ov-tag-partial',
    'Closed Won': 'crm-ov-tag-won',
    'Closed Lost': 'crm-ov-tag-lost',
};

function stageTagClass(stage) {
    return `crm-ov-tag ${STAGE_TAGS[stage] ?? ''}`;
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

        <template #header>
            <div class="min-w-0">
                <h1 class="crm-page-title">Overview</h1>
                <p class="crm-page-subtitle">
                    Pipeline and revenue for {{ year }}, plus today’s tasks and events.
                </p>
            </div>
        </template>

        <div class="crm-page max-w-none px-4 py-4 sm:px-5">
            <div class="crm-ov">
                <!-- Main column -->
                <div class="crm-ov-col">
                    <div class="crm-ov-top">
                    <!-- Stage column chart -->
                    <section class="crm-ov-card" aria-labelledby="ov-pipeline-heading">
                        <div class="crm-ov-head">
                            <div class="min-w-0">
                                <h2 id="ov-pipeline-heading" class="crm-ov-title">
                                    Pipeline by stage
                                </h2>
                                <div class="crm-ov-legend">
                                    <span class="crm-ov-legend-item">
                                        <span
                                            class="crm-ov-dot"
                                            style="background: var(--color-chart-soft)"
                                        />
                                        {{ chartMetric === 'value' ? 'Value' : 'Deals' }}
                                    </span>
                                    <span class="crm-ov-legend-item">
                                        <span
                                            class="crm-ov-dot"
                                            style="background: var(--color-primary)"
                                        />
                                        Hovered stage
                                    </span>
                                </div>
                            </div>
                            <div
                                class="crm-ov-seg"
                                role="tablist"
                                aria-label="Chart metric"
                            >
                                <button
                                    type="button"
                                    role="tab"
                                    class="crm-ov-seg-btn"
                                    :class="chartMetric === 'value' ? 'crm-ov-seg-btn-active' : ''"
                                    :aria-selected="chartMetric === 'value'"
                                    @click="chartMetric = 'value'"
                                >
                                    Value
                                </button>
                                <button
                                    type="button"
                                    role="tab"
                                    class="crm-ov-seg-btn"
                                    :class="chartMetric === 'count' ? 'crm-ov-seg-btn-active' : ''"
                                    :aria-selected="chartMetric === 'count'"
                                    @click="chartMetric = 'count'"
                                >
                                    Deals
                                </button>
                            </div>
                        </div>

                        <p v-if="pipelineIsEmpty" class="crm-ov-empty" role="status">
                            No pipeline opportunities for {{ year }}. Stage columns still
                            link to filtered lists.
                        </p>

                        <div
                            class="crm-ov-bars"
                            :aria-label="`Pipeline chart for ${year}. ${dealCountTotal} deals totaling ${formatMoney(pipelineTotal)}.`"
                            @mouseleave="hoveredStage = null"
                        >
                            <button
                                v-for="bar in stageBars"
                                :key="bar.stage"
                                type="button"
                                class="crm-ov-bar-col"
                                :class="hoveredStage === bar.stage ? 'crm-ov-bar-col-active' : ''"
                                :aria-label="`${bar.stage}: ${bar.count} opportunities, ${formatMoney(bar.value)}, ${formatPercent(bar.percent)} of pipeline`"
                                @mouseenter="hoveredStage = bar.stage"
                                @focus="hoveredStage = bar.stage"
                                @blur="hoveredStage = null"
                                @click="router.visit(bar.href)"
                            >
                                <span class="crm-ov-bar-track">
                                    <span
                                        v-if="bar.fill < 100"
                                        class="crm-ov-bar-rest"
                                        :style="{ flexGrow: 100 - bar.fill }"
                                    />
                                    <span
                                        class="crm-ov-bar-fill"
                                        :style="{ flexGrow: bar.fill }"
                                    />
                                    <span
                                        v-if="hoveredStage === bar.stage"
                                        class="crm-ov-tip"
                                        aria-hidden="true"
                                    >
                                        <span class="crm-ov-tip-title block">{{ bar.stage }}</span>
                                        <span class="crm-ov-tip-row">
                                            <span class="crm-ov-ring" />
                                            {{ formatMoney(bar.value) }}
                                        </span>
                                        <span class="crm-ov-tip-row">
                                            <span
                                                class="crm-ov-dot"
                                                style="background: var(--color-chart-c)"
                                            />
                                            {{ bar.count }} {{ bar.count === 1 ? 'deal' : 'deals' }}
                                        </span>
                                    </span>
                                </span>
                                <span class="crm-ov-bar-label">{{ bar.label }}</span>
                            </button>
                        </div>
                    </section>

                    <!-- Next key deal to close -->
                    <section class="crm-ov-spot" aria-labelledby="ov-spot-heading">
                        <span class="crm-ov-spot-rings" aria-hidden="true" />
                        <div class="crm-ov-spot-head">
                            <h2 id="ov-spot-heading">Next to close</h2>
                            <span class="crm-ov-spot-chip">
                                <Icon icon="lucide:flame" aria-hidden="true" />
                                Key deal
                            </span>
                        </div>

                        <template v-if="nextDeal">
                            <div class="crm-ov-spot-countdown">
                                <span class="crm-ov-spot-days tabular-nums">
                                    {{ nextDeal.days === 0 ? 'Today' : Math.abs(nextDeal.days) }}
                                </span>
                                <span class="crm-ov-spot-days-label">
                                    {{ nextDeal.days === 0 ? 'is the close date' : nextDeal.days < 0 ? 'days overdue' : nextDeal.days === 1 ? 'day left' : 'days left' }}
                                </span>
                            </div>

                            <Link :href="nextDeal.url" class="crm-ov-spot-name">
                                {{ nextDeal.name }}
                            </Link>
                            <p class="crm-ov-spot-meta">
                                {{ nextDeal.account?.name ?? 'No account' }}
                                · closes {{ formatDay(nextDeal.close_date) }}
                            </p>

                            <p class="crm-ov-spot-amount tabular-nums">
                                {{ formatMoney(nextDeal.amount) }}
                            </p>

                            <div class="crm-ov-spot-steps" :aria-label="`Stage: ${nextDeal.stage}`">
                                <span
                                    v-for="(step, index) in OPEN_STAGES"
                                    :key="step"
                                    class="crm-ov-spot-step"
                                    :class="index <= nextDeal.stageIndex ? 'crm-ov-spot-step-done' : ''"
                                />
                            </div>
                            <p class="crm-ov-spot-stage">
                                {{ nextDeal.stage }}
                                <span>· step {{ nextDeal.stageIndex + 1 }} of {{ OPEN_STAGES.length }}</span>
                            </p>

                            <Link :href="nextDeal.url" class="crm-ov-spot-cta">
                                Open deal
                                <Icon icon="lucide:arrow-up-right" aria-hidden="true" />
                            </Link>
                        </template>

                        <p v-else class="crm-ov-spot-meta mt-6">
                            No open key deals with a close date.
                        </p>
                    </section>
                </div>

                    <div class="crm-ov-row">
                        <!-- Pipeline mix donut -->
                        <section class="crm-ov-card" aria-labelledby="ov-mix-heading">
                            <div class="crm-ov-head">
                                <div>
                                    <h2 id="ov-mix-heading" class="crm-ov-title">
                                        Pipeline mix
                                    </h2>
                                    <p class="crm-ov-sub">Share of {{ year }} value</p>
                                </div>
                                <Icon
                                    icon="lucide:grip"
                                    class="crm-ov-grip text-text-muted"
                                    aria-hidden="true"
                                />
                            </div>

                            <p v-if="mixIsEmpty" class="crm-ov-empty" role="status">
                                No opportunity amounts for {{ year }}.
                            </p>
                            <PipelineDonut
                                v-else
                                :slices="mixSlices"
                                center-eyebrow="Total"
                                :center-value="mixCenterValue"
                                center-sub="Pipeline value"
                            />
                        </section>

                        <!-- Today: events + tasks -->
                        <section class="crm-ov-card" aria-labelledby="ov-today-heading">
                            <div class="crm-ov-head">
                                <div>
                                    <h2 id="ov-today-heading" class="crm-ov-title">
                                        Today
                                    </h2>
                                    <p class="crm-ov-sub">
                                        {{ eventsToday.length }}
                                        {{ eventsToday.length === 1 ? 'event' : 'events' }}
                                        ·
                                        {{ tasksDueToday.length }}
                                        {{ tasksDueToday.length === 1 ? 'task' : 'tasks' }} due
                                    </p>
                                </div>
                                <Link
                                    :href="route('events.index', { view: 'day', date: todayDate })"
                                    class="crm-ov-icon-link"
                                    aria-label="Open today’s calendar"
                                >
                                    <Icon icon="lucide:arrow-up-right" />
                                </Link>
                            </div>

                            <p
                                v-if="todayItems.length === 0"
                                class="crm-ov-empty"
                                role="status"
                            >
                                Nothing scheduled or due today.
                            </p>

                            <ul v-else class="crm-ov-list" role="list">
                                <li
                                    v-for="item in todayItems"
                                    :key="item.key"
                                    class="crm-ov-item"
                                >
                                    <span
                                        class="crm-ov-avatar"
                                        :class="item.kind === 'task' ? 'crm-ov-avatar-task' : 'crm-ov-avatar-event'"
                                        aria-hidden="true"
                                    >
                                        <Icon
                                            :icon="item.kind === 'task' ? 'lucide:list-checks' : 'lucide:calendar'"
                                        />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <Link :href="item.url" class="crm-ov-item-title">
                                            {{ item.title }}
                                        </Link>
                                        <p class="crm-ov-item-meta">{{ item.meta }}</p>
                                    </div>
                                    <span class="crm-ov-tag tabular-nums">{{ item.tag }}</span>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <!-- Key deals table -->
                    <section class="crm-ov-card" aria-labelledby="ov-deals-heading">
                        <div class="crm-ov-head mb-3">
                            <div>
                                <h2 id="ov-deals-heading" class="crm-ov-title">Key deals</h2>
                                <p class="crm-ov-sub">Largest open opportunities</p>
                            </div>
                            <Link
                                :href="route('opportunities.index')"
                                class="crm-ov-icon-link"
                                aria-label="View all opportunities"
                            >
                                <Icon icon="lucide:arrow-up-right" />
                            </Link>
                        </div>

                        <p
                            v-if="keyOpportunities.length === 0"
                            class="crm-ov-empty"
                            role="status"
                        >
                            No open opportunities.
                        </p>

                        <div v-else class="crm-ov-tablewrap">
                            <table class="crm-ov-table">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <span class="crm-ov-th"><TableHeadIcon name="id" />Deal</span>
                                        </th>
                                        <th scope="col">
                                            <span class="crm-ov-th"><TableHeadIcon name="client" />Account</span>
                                        </th>
                                        <th scope="col">
                                            <span class="crm-ov-th"><TableHeadIcon name="status" />Stage</span>
                                        </th>
                                        <th scope="col">
                                            <span class="crm-ov-th"><TableHeadIcon name="due" />Close date</span>
                                        </th>
                                        <th scope="col">
                                            <span class="crm-ov-th"><TableHeadIcon name="amount" />Amount</span>
                                        </th>
                                        <th scope="col" class="crm-ov-col-center">
                                            <span class="crm-ov-th"><TableHeadIcon name="actions" />Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="opportunity in keyOpportunities"
                                        :key="opportunity.id"
                                        class="crm-ov-row-link"
                                        @click="router.visit(opportunity.url)"
                                    >
                                        <td class="crm-ov-cell-strong">{{ opportunity.name }}</td>
                                        <td>
                                            <Link
                                                v-if="opportunity.account"
                                                :href="route('accounts.show', opportunity.account.id)"
                                                class="crm-ov-cell-link"
                                                @click.stop
                                            >
                                                {{ opportunity.account.name }}
                                            </Link>
                                            <span v-else class="text-text-muted">—</span>
                                        </td>
                                        <td>
                                            <span :class="stageTagClass(opportunity.stage)">
                                                {{ opportunity.stage }}
                                            </span>
                                        </td>
                                        <td>{{ formatDay(opportunity.close_date) }}</td>
                                        <td class="crm-ov-cell-strong tabular-nums">
                                            {{ formatMoney(opportunity.amount) }}
                                        </td>
                                        <td class="crm-ov-col-center">
                                            <button
                                                type="button"
                                                class="crm-ov-row-btn"
                                                aria-label="More actions"
                                                aria-haspopup="menu"
                                                :aria-expanded="rowMenu?.deal.id === opportunity.id"
                                                @click.stop="toggleRowMenu($event, opportunity)"
                                            >
                                                <Icon icon="lucide:ellipsis" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Side column -->
                <aside class="crm-ov-side" aria-label="Key figures">
                    <!-- Open pipeline: dark card with won/lost split -->
                    <section class="crm-ov-stat crm-ov-stat-dark">
                        <div class="crm-ov-stat-head">
                            <h2>Open pipeline</h2>
                            <Icon icon="lucide:grip" class="crm-ov-grip" aria-hidden="true" />
                        </div>
                        <p class="crm-ov-stat-value">{{ formatMoney(openPipelineValue) }}</p>
                        <p class="crm-ov-stat-sub">
                            {{ openDealCount }} open {{ openDealCount === 1 ? 'deal' : 'deals' }}
                            · {{ formatMoney(pipelineTotal) }} total
                        </p>

                        <template v-if="closedSplit">
                            <div
                                class="crm-ov-split"
                                role="img"
                                :aria-label="`Closed value: ${formatPercent(closedSplit.lost)} lost, ${formatPercent(closedSplit.won)} won`"
                            >
                                <div
                                    class="crm-ov-split-part crm-ov-split-hatch"
                                    :style="{ flexBasis: `${closedSplit.lost}%` }"
                                >
                                    <span>{{ formatPercent(closedSplit.lost) }}</span>
                                </div>
                                <div
                                    class="crm-ov-split-part crm-ov-split-solid"
                                    :style="{ flexBasis: `${closedSplit.won}%` }"
                                >
                                    {{ formatPercent(closedSplit.won) }}
                                </div>
                            </div>
                            <div class="crm-ov-split-legend" aria-hidden="true">
                                <span class="crm-ov-legend-item">
                                    <span class="crm-ov-ring" /> Lost
                                </span>
                                <span class="crm-ov-legend-item">
                                    <span class="crm-ov-dot bg-surface" /> Won
                                </span>
                            </div>
                        </template>
                        <p v-else class="crm-ov-stat-sub mt-4">No closed deals yet this year.</p>
                    </section>

                    <!-- Revenue by source: blue gradient card with mini bars -->
                    <section class="crm-ov-stat crm-ov-stat-blue">
                        <div class="crm-ov-stat-head">
                            <h2>Revenue by source</h2>
                            <Icon icon="lucide:grip" class="crm-ov-grip" aria-hidden="true" />
                        </div>
                        <p class="crm-ov-stat-value">{{ formatMoney(revenueBySourceTotal) }}</p>
                        <p class="crm-ov-stat-sub">
                            {{ revenueBySource.length }}
                            {{ revenueBySource.length === 1 ? 'lead source' : 'lead sources' }}
                            · {{ year }}
                        </p>

                        <p v-if="revenueIsEmpty" class="crm-ov-stat-sub mt-4">
                            No revenue by lead source for {{ year }}.
                        </p>
                        <div
                            v-else
                            class="crm-ov-mini"
                            role="img"
                            :aria-label="`Revenue by source: ${sourceBars.map((b) => `${b.source} ${formatMoney(b.value)}`).join(', ')}`"
                        >
                            <span
                                v-if="sourceAverageBottom"
                                class="crm-ov-mini-avg"
                                :style="{ bottom: sourceAverageBottom }"
                                aria-hidden="true"
                            />
                            <div
                                v-for="bar in sourceBars"
                                :key="bar.source"
                                class="crm-ov-mini-col"
                                :class="bar.top ? 'crm-ov-mini-col-top' : ''"
                                :title="`${bar.source}: ${formatMoney(bar.value)} · ${formatPercent(bar.percent)}`"
                            >
                                <div
                                    class="relative flex w-full flex-1 items-end"
                                >
                                    <div class="crm-ov-mini-bar relative" :style="{ height: bar.height }">
                                        <span v-if="bar.top" class="crm-ov-mini-tip">
                                            {{ abbreviateMoney(bar.value) }}
                                        </span>
                                    </div>
                                </div>
                                <span class="crm-ov-mini-label">{{ bar.source }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Deals by stage: soft card with curve -->
                    <section class="crm-ov-stat crm-ov-stat-soft">
                        <div class="crm-ov-stat-head">
                            <h2>Win rate</h2>
                            <Icon icon="lucide:grip" class="crm-ov-grip" aria-hidden="true" />
                        </div>
                        <p class="crm-ov-stat-value">
                            {{ winRateLabel }}
                        </p>
                        <p class="crm-ov-stat-sub">
                            {{ wonCount }} won · {{ lostCount }} lost · {{ dealCountTotal }}
                            {{ dealCountTotal === 1 ? 'deal' : 'deals' }} by stage
                        </p>

                        <div
                            v-if="stageCurve"
                            class="crm-ov-curve"
                            role="img"
                            :aria-label="`Deals per stage: ${stageBars.map((b) => `${b.stage} ${b.count}`).join(', ')}`"
                        >
                            <svg
                                :viewBox="`0 0 ${CURVE_WIDTH} ${CURVE_HEIGHT}`"
                                preserveAspectRatio="none"
                                aria-hidden="true"
                            >
                                <path class="crm-ov-curve-area" :d="stageCurve.area" />
                                <path class="crm-ov-curve-line" :d="stageCurve.line" />
                            </svg>
                            <div class="crm-ov-curve-labels" aria-hidden="true">
                                <span v-for="bar in stageBars" :key="`c-${bar.stage}`">
                                    {{ bar.label }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Assistant follow-ups -->
                    <section class="crm-ov-card" aria-labelledby="ov-assistant-heading">
                        <div class="crm-ov-head">
                            <div>
                                <h2 id="ov-assistant-heading" class="crm-ov-title">
                                    Assistant
                                </h2>
                                <p class="crm-ov-sub">Follow-ups to review</p>
                            </div>
                            <Icon
                                icon="lucide:sparkles"
                                class="crm-ov-grip text-secondary"
                                aria-hidden="true"
                            />
                        </div>

                        <p
                            v-if="recommendations.length === 0"
                            class="crm-ov-empty"
                            role="status"
                        >
                            No recommendations right now.
                        </p>

                        <ul v-else class="crm-ov-list" role="list">
                            <li
                                v-for="recommendation in recommendations"
                                :key="recommendation.key"
                                class="crm-ov-item items-start"
                            >
                                <span
                                    class="crm-ov-avatar crm-ov-avatar-letter"
                                    aria-hidden="true"
                                >
                                    {{ initial(recommendation.title) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="recommendation.url"
                                        class="crm-ov-item-title"
                                    >
                                        {{ recommendation.title }}
                                    </Link>
                                    <p class="crm-ov-item-meta whitespace-normal">
                                        {{ recommendation.message }}
                                    </p>
                                    <button
                                        type="button"
                                        class="crm-ov-link-btn mt-1"
                                        :aria-label="`Dismiss recommendation for ${recommendation.title}`"
                                        @click="dismissRecommendation(recommendation)"
                                    >
                                        Dismiss
                                    </button>
                                </div>
                                <span class="crm-ov-tag">{{ suggestionKind(recommendation) }}</span>
                            </li>
                        </ul>
                    </section>

                    <Link
                        v-if="$page.props.auth?.role_slug"
                        :href="route('reports.index')"
                        class="crm-ov-cta"
                    >
                        <Icon icon="lucide:file-text" class="text-lg" aria-hidden="true" />
                        Open reports
                    </Link>
                </aside>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="rowMenu" class="crm-row-menu-backdrop" @click="closeRowMenu" />
            <div
                v-if="rowMenu"
                class="crm-row-menu"
                role="menu"
                :style="{ top: rowMenu.top + 'px', left: rowMenu.left + 'px' }"
            >
                <Link :href="rowMenu.deal.url" class="crm-row-menu-item" role="menuitem" @click="closeRowMenu">
                    <Icon icon="lucide:file-text" />
                    View deal
                </Link>
                <Link
                    v-if="rowMenu.deal.account"
                    :href="route('accounts.show', rowMenu.deal.account.id)"
                    class="crm-row-menu-item"
                    role="menuitem"
                    @click="closeRowMenu"
                >
                    <Icon icon="lucide:building-2" />
                    View account
                </Link>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
