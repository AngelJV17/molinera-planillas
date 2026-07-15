export const formatDate = (value) => {
    if (!value) {
        return '';
    }

    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return [
            String(value.getDate()).padStart(2, '0'),
            String(value.getMonth() + 1).padStart(2, '0'),
            value.getFullYear(),
        ].join('-');
    }

    const text = String(value).trim();
    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }

    return text.replaceAll('/', '-');
};

export const formatPeriod = (value) => {
    if (!value) {
        return '';
    }

    const text = String(value).trim();
    const isoMatch = text.match(/^(\d{4})-(\d{2})$/);

    if (isoMatch) {
        return `${isoMatch[2]}-${isoMatch[1]}`;
    }

    return text.replaceAll('/', '-');
};

export const periodForRequest = (value) => {
    if (!value) {
        return '';
    }

    const text = String(value).trim().replaceAll('/', '-');
    const localMatch = text.match(/^(\d{2})-(\d{4})$/);

    if (localMatch) {
        return `${localMatch[2]}-${localMatch[1]}`;
    }

    return text;
};
