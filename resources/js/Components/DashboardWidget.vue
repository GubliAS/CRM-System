<script setup>
/*
 * One dashboard widget, drawn exactly as the saved dashboard draws it.
 * `card` comes from buildCards(). In the builder it is not interactive (no
 * drill-through, links inert) and starts revealed so nothing waits on scroll.
 */
import WidgetGlyph from '@/Components/WidgetGlyph.vue';
import { TYPE_META } from '@/dashboard/layout';
import { RING_RADIUS, formatNumber } from '@/dashboard/widgets';
import { display } from '@/display';
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    card: { type: Object, required: true },
    /** false in the builder: no drill-through, links inert. */
    interactive: { type: Boolean, default: true },
    /** true while a builder preview is still being fetched. */
    loading: { type: Boolean, default: false },
});

const hovered = ref(null);
const isStat = computed(() => props.card.widget.type === 'metric' || props.card.widget.type === 'gauge');

function drill(event) {
    const url = props.card.widget.report_url;

    if (!props.interactive || !url || event.target.closest('a, button, input, select')) {
        return;
    }

    router.visit(url);
}
</script>

<template>
    <!-- Metric / gauge -->
    <section
        v-if="isStat"
        class="crm-db-w crm-ov-stat"
        :class="[
            `crm-ov-stat-${card.variant}`,
            interactive && card.widget.report_url ? 'crm-db-w-link' : '',
            interactive ? '' : 'is-in crm-db-w-static',
        ]"
        :style="interactive ? card.style : undefined"
        @click="drill"
    >
        <span class="crm-db-w-mark" aria-hidden="true">
            <WidgetGlyph :type="card.widget.type" />
        </span>

        <div class="crm-ov-stat-head">
            <h2>{{ card.widget.title }}</h2>
            <Link
                v-if="interactive && card.widget.report_url"
                :href="card.widget.report_url"
                class="crm-db-w-open"
                aria-label="Open report"
                title="Open report"
            >
                <Icon icon="lucide:arrow-up-right" />
            </Link>
            <span v-else-if="card.widget.report_url" class="crm-db-w-open" aria-hidden="true">
                <Icon icon="lucide:arrow-up-right" />
            </span>
        </div>

        <div v-if="loading" class="crm-db-skel" aria-busy="true">
            <span class="crm-db-skel-line crm-db-skel-big" />
            <span class="crm-db-skel-line" />
        </div>

        <p v-else-if="card.widget.error" class="crm-ov-stat-sub mt-3">{{ card.widget.error }}</p>

        <template v-else-if="card.widget.type === 'metric'">
            <p class="crm-ov-stat-value crm-db-bignum">{{ formatNumber(card.widget.result?.value) }}</p>
            <p class="crm-ov-stat-sub">{{ card.widget.result?.label }}</p>
        </template>

        <div v-else class="crm-db-gauge" :class="card.narrow ? 'crm-db-gauge-narrow' : ''">
            <div class="crm-db-ring" role="img" :aria-label="`${card.pct}% of the largest value on this dashboard`">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="crm-db-ring-track" cx="60" cy="60" :r="RING_RADIUS" />
                    <circle class="crm-db-ring-arc" cx="60" cy="60" :r="RING_RADIUS" :stroke-dasharray="card.dash" />
                </svg>
                <span class="crm-db-ring-pct tabular-nums">{{ card.pct }}%</span>
            </div>
            <div class="min-w-0">
                <p class="crm-ov-stat-value !mt-0">{{ formatNumber(card.widget.result?.value) }}</p>
                <p class="crm-ov-stat-sub">{{ card.widget.result?.label }}</p>
            </div>
        </div>
    </section>

    <!-- Chart / table -->
    <section
        v-else
        class="crm-db-w crm-ov-card"
        :class="[
            interactive && card.widget.report_url ? 'crm-db-w-link' : '',
            interactive ? '' : 'is-in crm-db-w-static',
        ]"
        :style="interactive ? card.style : undefined"
        @click="drill"
    >
        <div class="crm-ov-head">
            <div class="min-w-0">
                <h2 class="crm-ov-title truncate">{{ card.widget.title }}</h2>
                <p class="crm-ov-sub">
                    {{ TYPE_META[card.widget.type]?.label ?? card.widget.type }}
                    <template v-if="card.widget.report_name"> · {{ card.widget.report_name }}</template>
                </p>
            </div>
            <Link
                v-if="interactive && card.widget.report_url"
                :href="card.widget.report_url"
                class="crm-ov-icon-link crm-db-w-open"
                aria-label="Open report"
                title="Open report"
            >
                <Icon icon="lucide:arrow-up-right" />
            </Link>
            <span v-else-if="card.widget.report_url" class="crm-ov-icon-link crm-db-w-open" aria-hidden="true">
                <Icon icon="lucide:arrow-up-right" />
            </span>
            <Icon
                v-else
                :icon="TYPE_META[card.widget.type]?.icon ?? 'lucide:layout-grid'"
                class="crm-ov-grip text-text-muted"
                aria-hidden="true"
            />
        </div>

        <div v-if="loading" class="crm-db-skel crm-db-skel-fill" aria-busy="true">
            <template v-if="card.widget.type === 'chart'">
                <span v-for="n in 6" :key="n" class="crm-db-skel-bar" :style="{ height: `${25 + ((n * 37) % 60)}%` }" />
            </template>
            <template v-else>
                <span v-for="n in 4" :key="n" class="crm-db-skel-line crm-db-skel-row" />
            </template>
        </div>

        <p v-else-if="card.widget.error" class="crm-ov-empty" role="alert">{{ card.widget.error }}</p>

        <div v-else-if="card.widget.type === 'chart'" class="crm-db-chart">
            <p v-if="(card.bars ?? []).length === 0" class="crm-ov-empty">No chart data.</p>
            <div v-else class="crm-ov-bars" @mouseleave="hovered = null">
                <div
                    v-for="bar in card.bars"
                    :key="bar.key"
                    class="crm-ov-bar-col"
                    :class="hovered === bar.key ? 'crm-ov-bar-col-active' : ''"
                    @mouseenter="hovered = bar.key"
                >
                    <span class="crm-ov-bar-track">
                        <span v-if="bar.fill < 100" class="crm-ov-bar-rest" :style="{ flexGrow: 100 - bar.fill }" />
                        <span class="crm-ov-bar-fill" :style="{ flexGrow: bar.fill }" />
                        <span v-if="hovered === bar.key" class="crm-ov-tip" aria-hidden="true">
                            <span class="crm-ov-tip-title block">{{ bar.label }}</span>
                            <span class="crm-ov-tip-row">
                                Value <strong>{{ formatNumber(bar.value) }}</strong>
                            </span>
                        </span>
                    </span>
                    <span class="crm-ov-bar-label" :title="bar.label">{{ bar.label }}</span>
                </div>
            </div>
        </div>

        <div v-else class="crm-db-tablefill">
            <p v-if="(card.widget.result?.rows ?? []).length === 0" class="crm-ov-empty">No rows.</p>
            <div v-else class="crm-ov-tablewrap">
                <table class="crm-ov-table">
                    <thead>
                        <tr>
                            <th v-for="column in card.widget.result?.columns ?? []" :key="column.key" scope="col">
                                {{ column.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, rowIndex) in card.widget.result?.rows ?? []" :key="`${card.widget.id}-row-${rowIndex}`">
                            <td v-for="column in card.widget.result?.columns ?? []" :key="column.key">
                                {{ display(row[column.key]) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
