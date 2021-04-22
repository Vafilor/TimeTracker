export function formatTimeDifference(startMillis: number, endMillis: number, format: string): string {
    let duration = Math.floor((endMillis - startMillis) / 1000);

    const values = {
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0,
    };

    if (duration > 86400) {
        values.days = Math.floor(duration / 86400);
        duration -= values.days * 86400;
    }
    if (duration > 3600) {
        values.hours = Math.floor(duration / 3600);
        duration -= values.hours * 3600;
    }
    if (duration > 60) {
        values.minutes = Math.floor(duration / 60);
        duration -= values.minutes * 60;
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