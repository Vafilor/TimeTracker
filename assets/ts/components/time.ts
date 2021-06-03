const secondsInMinute = 60;
const secondsInHour = secondsInMinute * 60;
const secondsInDay = secondsInHour * 24;
const secondsInMonth = secondsInDay * 30;
const secondsInYear = secondsInDay * 365;

export function formatTimeDifference(startMillis: number, endMillis: number, format: string): string {
    let duration = Math.floor((endMillis - startMillis) / 1000);

    const values = {
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0,
    };

    if (duration >= secondsInDay) {
        values.days = Math.floor(duration / secondsInDay);
        duration -= values.days * secondsInDay;
    }
    if (duration >= secondsInHour) {
        values.hours = Math.floor(duration / secondsInHour);
        duration -= values.hours * secondsInHour;
    }
    if (duration >= secondsInMinute) {
        values.minutes = Math.floor(duration / secondsInMinute);
        duration -= values.minutes * secondsInMinute;
    }

    values.seconds = duration;

    let specialChar = false;
    let result = '';
    for(let i = 0; i < format.length; i++) {
        const c = format.charAt(i);

        if (c === '%' && !specialChar) {
            specialChar = true;
        } else if(c === '%' && specialChar) {
            result += '%';
            specialChar = false;
        } else if(specialChar) {
            result += specialCharacterToValue(values, c);
            specialChar = false;
        } else {
            result += c;
        }
    }

    return result;
}

export function formatShortTimeDifference(startMillis: number, endMillis: number): string {
    let duration = Math.floor((endMillis - startMillis) / 1000);

    let result = ''

    if (duration >= secondsInHour) {
        const hours = Math.floor(duration / secondsInHour);
        duration -= hours * secondsInHour;
        result += hours + 'h ';
    }
    if (duration >= secondsInMinute) {
        const minutes = Math.floor(duration / secondsInMinute);
        duration -= minutes * secondsInMinute;

        result += minutes  + 'm ';
    }

    result += duration  + 's';

    return result;
}

function leadingZeroize(value: number): string {
    if (value < 10) {
        return '0' + value;
    }

    return '' + value;
}

function specialCharacterToValue(duration: any, char: string): string {
    switch (char) {
        case 'D':
            return leadingZeroize(duration.days);
        case 'd':
            return duration.days;
        case 'H':
            return leadingZeroize(duration.hours);
        case 'h':
            return duration.hours;
        case 'I':
            return leadingZeroize(duration.minutes);
        case 'i':
            return duration.minutes;
        case 'S':
            return leadingZeroize(duration.seconds);
        case 's':
            return duration.seconds;
    }

    return '';
}

function pluralize(value: number, singular: string, plural: string): string {
    let result = value  + ' ';
    if (value === 1) {
        result += singular;
    } else {
        result += plural;
    }

    return result + ' ago';
}

export function timeAgo(startMillis: number, endMillis?: number): string {
    if (!endMillis) {
        endMillis = (new Date()).getTime();
    }

    const duration = Math.floor((endMillis - startMillis) / 1000);

    if (duration >= secondsInYear) {
        const years = Math.floor(duration / secondsInYear);
        return pluralize(years, 'year', 'years');
    }
    if (duration >= secondsInMonth) {
        const months = Math.floor(duration / secondsInMonth);
        return pluralize(months, 'month', 'months');
    }
    if (duration >= secondsInDay) {
        const days = Math.floor(duration / secondsInDay);
        return pluralize(days, 'day', 'days');
    }
    if (duration >= secondsInHour) {
        const hours = Math.floor(duration / secondsInHour);
        return pluralize(hours, 'hour', 'hours');
    }
    if (duration >= secondsInMinute) {
        const minutes = Math.floor(duration / secondsInMinute);
        return pluralize(minutes, 'minute', 'minutes');
    }
    if (duration > 0) {
        return pluralize(duration, 'second', 'seconds');
    }

    return 'now';
}