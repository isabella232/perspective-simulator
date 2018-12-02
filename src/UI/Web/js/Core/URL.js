import * as Util from './Util.js';


/**
 * Get the state from a url.
 *
 * @param string url The url to fetch state from.
 *
 * @return array
 */
export const getState = function (url = window.location.href)
{
    let parsed = parseUrl(url);
    return parsed.state;

};//end getState()


/**
 * Validate a state array for it's values.
 *
 * @param array state Array to check.
 *
 * @return boolean
 */
export const validateState = state => {
    if (Array.isArray(state) === false) {
        return false;
    }

    // Null is valid, don't bother matching it.
    state = state.filter(param => param !== null);

    let valids = state
        .map(param => {
            return param.match(/^[a-z0-9]+$/i) !== null;
        })
        .filter(valid => valid);

    if (valids.length === state.length) {
        return true;
    }

    return false;

};//end validateState()

export const compareState = Util.distinctValue(null, Util.same.compare);
export const compareUrl   = Util.distinctValue(null, Util.same.compare);

/**
 * Sets a supplied state value into a url.
 *
 * @param array    state        State expressed as an array.
 * @param string   url          Url value to inject state values.
 * @param function replaceState ReplaceState function that handles updates of new url information.
 * @param function validatorFn  State validator function.
 *
 * @return string
 */
export const setState = function (
    state,
    url = window.location.href,
    replaceState = null,
    validatorFn = validateState
) {
    let parsed = parseUrl(url);

    if (validatorFn(state) === false) {
        throw new Error(`Failed to validate state ${state} to set.`);
    }

    if (compareState(state) === false || compareUrl(url) === false) {
        parsed.state = state;

        let newUrl = parsedToString(parsed);

        if (typeof replaceState === 'function') {
            replaceState(null, '', newUrl);
        } else {
            history.replaceState(null, '', newUrl);
        }

        return newUrl;
    }

    return url;

};

/**
 * Parses params from a string of key/value pairs.
 *
 * @param string   paramStr The parameter string to decode.
 * @param function decode   The function to use to decode the value.
 *
 * @return object
 */
export const parseParams = function (paramStr, decode = decodeURIComponent)
{
    let hashes = paramStr.replace('?', '').split('&');
    let params = {};
    hashes.forEach(hash => {
        let [key, val] = hash.split('=');
        params[key] = decode(val);

        // Parse numbers & floats.
        if (isNaN(params[key] * 1) === false) {
            params[key] = params[key] * 1;
        }
    });
    return params;

};

/**
 * Parse the current browser url.
 *
 * @return object
 */
export const parseCurrentUrl = () => {
    return parseUrl(window.location.href);
};

export const parseStateFromString = (str) => {
    let parsed = str
        .replace(/^[\_\/]+/, '')
        .replace(/[\/]+$/, '')
        .split(/[\/]/);

    parsed = parsed.filter(part => part.match(/^[a-z0-9\-\.\_]+$/i));

    if (parsed.length <= 0) {
        return [];
    }

    let mode = parsed[0].split('-');
    if (mode.length < 2) {
        mode = [mode[0], null];
    } else {
        mode = [mode[0], mode[1]];
    }

    let sub = parsed.slice(1).filter(part => part !== '');
    if (sub.length) {
        mode = mode.concat(sub);
    }

    return mode;
};

/**
 * Parses the URL into the parts.
 *
 * @param string url The url to parse.
 *
 * @return object
 */
export const parseUrl = (url, stateDelimeter = '/_/') => {
    let state    = [];
    let anchor   = null;
    let params   = {};
    let protocol = '';

    const queryPos    = url.indexOf('?');
    const anchorPos   = url.indexOf('#');
    const statePos    = url.indexOf(stateDelimeter);
    const protocolPos = url.indexOf('//');

    if (anchorPos !== -1) {
        anchor = url.substring(anchorPos+1);
        url    = url.substring(0, anchorPos);
    }

    if (queryPos !== -1) {
        params = parseParams(url.substring(queryPos));
        url    = url.substring(0, queryPos);
    }

    if (statePos !== -1) {
        state = parseStateFromString(url.substr(statePos + 3));
        url   = url.substring(0, statePos);
    }

    if (protocolPos !== -1) {
        protocol = url.substring(0, protocolPos);
    }

    return {
        url,
        state,
        params,
        anchor,
        protocol
    };
};

/**
 * Converts a params object to a valid query string.
 *
 * @param object   params Key/Value pairs.
 * @param function encode The function to use to encode the param value for the url.
 *
 * @return string.
 */
export const paramsToQueryString = function (params, encode = encodeURIComponent) {
    let hashes = Object.keys(params).sort().map(key => {
        return key+'='+encode(params[key]);
    });

    if (hashes.length) {
        return '?'+hashes.join('&');
    }

    return '';
};

/**
 * Converts a string value to a valid anchor.
 *
 * @param string anchor The anchor value.
 *
 * @return string
 */
export const anchorToString = anchor => {
    if (typeof anchor === 'string' && anchor !== '') {
        return '#'+anchor;
    }

    return '';
};

/**
 * Convert a parsed url into a string.
 *
 * @param object parsed A parsed URL object.
 *
 * @see parseUrl()
 * @return string
 */
export const parsedToString = parsed => {
    let state  = '';
    let query  = paramsToQueryString(parsed.params);
    let anchor = anchorToString(parsed.anchor);
    let url    = parsed.url;

    if (parsed.state.length) {
        state = '/_/' + parsed.state.slice(0, 2)
            .filter(part => typeof part === 'string')
            .join('-');

        if (parsed.state.length > 2) {
            state += '/' + parsed.state.slice(2).join('/');
        }
    }

    url = url.replace(/\/$/, '');
    url += state;
    url += query;
    url += anchor;

    return url;

};//end parsedToString()


/**
 * Clears state data from the provided url and return the modified url.
 *
 * @param string  url            (optional) url to clear.
 * @param boolean updateLocation (optional) Set to FALSE to prevent updates to window.location
 *
 * @return string
 */
export const clearState = (url = window.location.href, updateLocation = true) => {
    let parsed = parseUrl(url);

    parsed.state = [];
    let newUrl = parsedToString(parsed);

    // If we should update then push the modified url to the window.location
    if (updateLocation === true) {
        window.location.href = newUrl;
    }

    return newUrl;
};

export const updateParsedUrl = function(
    parsed,
    replaceState = null
) {
    let newUrl = parsedToString(parsed);
    if (typeof replaceState === 'function') {
        replaceState(null, '', newUrl);
    } else {
        history.replaceState(null, '', newUrl);
    }

    return newUrl;

};

export const replaceCurrentUrl = (url) => {
    let parsed = parseCurrentUrl();
    parsed.url = url;
    return updateParsedUrl(parsed);
};


export default {
    getState,
    setState,
    clearState,
    validateState,
    paramsToQueryString,
    parseUrl,
    parseCurrentUrl,
    parsedToString,
    updateParsedUrl,
    replaceCurrentUrl
};