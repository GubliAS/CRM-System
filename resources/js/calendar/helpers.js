/*
 * Calendar maths and colour rules. Events arrive from the server as naive
 * local strings ("2026-10-14 09:30:00"), so everything here slices and
 * compares those strings instead of going through Date time zones.
 */

export const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];
export const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const pad = (n) => String(n).padStart(2, '0');

export const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

export function parseYmd(value) {
    const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);

    return new Date(y, m - 1, d);
}

export const addDays = (value, n) => {
    const d = parseYmd(value);
    d.setDate(d.getDate() + n);

    return ymd(d);
};

export function addMonths(value, n) {
    const d = parseYmd(value);
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + n);
    const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, last));

    return ymd(d);
}

export function startOfWeek(value) {
    return addDays(value, -parseYmd(value).getDay());
}

/** 42 cells (6 weeks, Sunday first) covering the month that contains `value`. */
export function monthCells(value) {
    const d = parseYmd(value);
    const first = new Date(d.getFullYear(), d.getMonth(), 1);
    const start = addDays(ymd(first), -first.getDay());

    return Array.from({ length: 42 }, (_, i) => {
        const date = addDays(start, i);

        return { date, day: parseYmd(date).getDate(), inMonth: date.slice(0, 7) === value.slice(0, 7) };
    });
}

export function weekDays(value) {
    const start = startOfWeek(value);

    return Array.from({ length: 7 }, (_, i) => {
        const date = addDays(start, i);
        const d = parseYmd(date);

        return { date, day: d.getDate(), weekday: DAY_NAMES[d.getDay()].slice(0, 3) };
    });
}

/** Dates the visible grid needs, first and last, for the given view. */
export function visibleRange(view, value) {
    if (view === 'day') {
        return [value, value];
    }

    if (view === 'week') {
        const start = startOfWeek(value);

        return [start, addDays(start, 6)];
    }

    const cells = monthCells(value);

    return [cells[0].date, cells[41].date];
}

export function titleFor(view, value) {
    const d = parseYmd(value);

    if (view === 'day') {
        return `${DAY_NAMES[d.getDay()]}, ${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
    }

    if (view === 'week') {
        const [a, b] = visibleRange('week', value).map(parseYmd);
        return `${a.getDate()} ${MONTH_NAMES[a.getMonth()].slice(0, 3)} – ${b.getDate()} ${MONTH_NAMES[b.getMonth()].slice(0, 3)} ${b.getFullYear()}`;
    }

    return `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
}

export function formatDateLabel(value) {
    const d = parseYmd(value);

    return `${DAY_NAMES[d.getDay()]}, ${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
}

export const minutesOf = (stamp) => Number(stamp.slice(11, 13)) * 60 + Number(stamp.slice(14, 16));

export function formatClock(stamp) {
    const h = Number(stamp.slice(11, 13));
    const m = stamp.slice(14, 16);

    return `${h % 12 === 0 ? 12 : h % 12}:${m} ${h < 12 ? 'AM' : 'PM'}`;
}

export function hourLabel(hour) {
    return `${hour % 12 === 0 ? 12 : hour % 12} ${hour < 12 ? 'AM' : 'PM'}`;
}

export function durationLabel(event) {
    if (event.all_day) {
        return 'All day';
    }

    const mins = Math.round(
        (new Date(event.ends_at.replace(' ', 'T')) - new Date(event.starts_at.replace(' ', 'T'))) / 60000,
    );

    if (mins < 60) {
        return `${Math.max(mins, 1)} min`;
    }

    const h = Math.floor(mins / 60);
    const m = mins % 60;

    return m ? `${h}h ${m}m` : `${h} ${h === 1 ? 'hour' : 'hours'}`;
}

/** Last date an event occupies. A timed event ending exactly at midnight belongs to the day before. */
export function lastDate(event) {
    const end = event.ends_at.slice(0, 10);

    if (!event.all_day && event.ends_at.slice(11, 19) === '00:00:00' && end > event.starts_at.slice(0, 10)) {
        return addDays(end, -1);
    }

    return end;
}

export const occursOn = (event, date) => event.starts_at.slice(0, 10) <= date && date <= lastDate(event);

/** Same rule the server applies (RescheduleEvent), so a drop can show at once. */
export function rescheduled(event, date, hour) {
    if (event.all_day) {
        const days = Math.round((parseYmd(date) - parseYmd(event.starts_at.slice(0, 10))) / 86400000);

        return {
            starts_at: `${addDays(event.starts_at.slice(0, 10), days)} 00:00:00`,
            ends_at: `${addDays(event.ends_at.slice(0, 10), days)} 00:00:00`,
        };
    }

    const time = hour === null ? event.starts_at.slice(11, 19) : `${pad(hour)}:00:00`;
    const startsAt = `${date} ${time}`;
    const seconds =
        (new Date(event.ends_at.replace(' ', 'T')) - new Date(event.starts_at.replace(' ', 'T'))) / 1000;
    const end = new Date(new Date(startsAt.replace(' ', 'T')).getTime() + seconds * 1000);

    return {
        starts_at: startsAt,
        ends_at: `${ymd(end)} ${pad(end.getHours())}:${pad(end.getMinutes())}:${pad(end.getSeconds())}`,
    };
}

/** Column layout for overlapping timed events on one day. */
export function layoutDay(events, date) {
    const items = events
        .filter((e) => !e.all_day && occursOn(e, date))
        .map((e) => {
            const start = e.starts_at.slice(0, 10) < date ? 0 : minutesOf(e.starts_at);
            let end = lastDate(e) > date ? 24 * 60 : minutesOf(e.ends_at);

            if (end <= start) {
                end = Math.min(start + 30, 24 * 60);
            }

            return { event: e, start, end, lane: 0, lanes: 1 };
        })
        .sort((a, b) => a.start - b.start || b.end - a.end);

    let group = [];
    let groupEnd = -1;

    const flush = () => {
        const lanes = group.reduce((max, item) => Math.max(max, item.lane + 1), 1);
        group.forEach((item) => (item.lanes = lanes));
        group = [];
    };

    for (const item of items) {
        if (item.start >= groupEnd) {
            flush();
            groupEnd = -1;
        }

        const taken = new Set(group.filter((g) => g.end > item.start).map((g) => g.lane));
        while (taken.has(item.lane)) {
            item.lane++;
        }

        group.push(item);
        groupEnd = Math.max(groupEnd, item.end);
    }

    flush();

    return items;
}

/* ─────────────── Colour coding (by calendar, show-time-as, or related record) ─────────────── */

export const COLOR_MODES = [
    { key: 'calendar', label: 'Calendar' },
    { key: 'show', label: 'Status' },
    { key: 'related', label: 'Related to' },
];

const CALENDARS = [
    { key: 'mine', label: 'My events', color: '#0176d3' },
    { key: 'team', label: 'Team events', color: '#7c5cd6' },
];

const SHOW = [
    { key: 'Busy', label: 'Busy', color: '#0176d3' },
    { key: 'Free', label: 'Free', color: '#2e844a' },
    { key: 'Out of Office', label: 'Out of office', color: '#c9691a' },
];

const RELATED = [
    { key: 'account', label: 'Account', color: '#0176d3' },
    { key: 'contact', label: 'Contact', color: '#0b8a9c' },
    { key: 'lead', label: 'Lead', color: '#e2761b' },
    { key: 'opportunity', label: 'Opportunity', color: '#7c5cd6' },
    { key: 'none', label: 'Not related', color: '#64748b' },
];

export const CALENDAR_TYPES = CALENDARS;

export function categoriesFor(mode) {
    return mode === 'show' ? SHOW : mode === 'related' ? RELATED : CALENDARS;
}

export function categoryOf(event, mode) {
    const list = categoriesFor(mode);
    const key =
        mode === 'show'
            ? event.show_time_as
            : mode === 'related'
              ? (event.related_key ?? 'none')
              : event.is_mine
                ? 'mine'
                : 'team';

    return list.find((item) => item.key === key) ?? list[0];
}

export const calendarOf = (event) => (event.is_mine ? 'mine' : 'team');

export function initials(name) {
    return (
        String(name ?? '')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('') || '?'
    );
}
