export function display(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

export function fullName(person) {
    if (!person) {
        return '—';
    }

    const name = [person.first_name, person.last_name].filter(Boolean).join(' ');

    return name || '—';
}

export function personName(user) {
    return user?.name ? user.name : '—';
}

export function formatWhen(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function formatDay(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
}

export function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return '—';
    }

    return amount.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function websiteHref(value) {
    if (!value) {
        return null;
    }

    const trimmed = String(value).trim();

    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed;
    }

    if (/^[a-z0-9.-]+\.[a-z]{2,}([/?#].*)?$/i.test(trimmed)) {
        return `https://${trimmed}`;
    }

    return null;
}
