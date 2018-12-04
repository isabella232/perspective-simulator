/* eslint no-console:false */
export const ERR_GENERAL    = 'ERROR_GENERAL';
export const ERR_CONTROLLER = 'ERROR_CONTROLLER';
export const ERR_WIDGET     = 'ERROR_WIDGET';
export const ERR_ACTION     = 'ERROR_ACTION';
export const ERR_REDUCER    = 'ERROR_REDUCER';
export const ERR_DOM        = 'ERROR_DOM';
export const ERR_SUPPRESSED = 'ERROR_SUPPRESSED';

export const handleError = (err, type=ERR_GENERAL, ...args) => {
    if (window && window._suppressError) {
        return;
    }

    let extras   = [`Error Type: ${type}`];
    let suppress = false;

    switch (type) {
        case ERR_CONTROLLER:
            extras.push(`Controller: %s`);
            extras.push(`Widget: %o`);
            break;

        case ERR_WIDGET:
            extras.push(`Widget: %o`);
            break;

        case ERR_ACTION:
            extras.push(`Action Type: %s`);
            break;

        case ERR_DOM:
            extras.push(`DOM: %s`);
            break;

        case ERR_SUPPRESSED:
            suppress = true;
            break;

        case ERR_GENERAL:
        default:

            break;
    }

    let msg = ``;
    extras.forEach(extra => {
        msg += extra+'\n\n';
    });

    if (!suppress) {
        console.error(msg, ...args, err);
    }
};
