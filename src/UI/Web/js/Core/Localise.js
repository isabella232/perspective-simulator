let translations = {};

/**
 * Translate a text string with variable replacements in the style of sprintf().
 *
 * @param string   str          The string to replace.
 * @param ...mixed replacements Remaining arguments as compatible string replacement values.
 *
 * @return string
 */
export const _ = (str, ...replacements) => {
    if (translations.hasOwnProperty(str) === true) {
        str = translations[str];
    }

    for (let i = 0, l = replacements.length; i<l; i++) {
        str = str.replace(/%s/, replacements[i]);
    }

    return str;
};

/**
 * Translate singular or plural text string with variable replacements in the style of sprintf().
 *
 * @param string   str          The singular string to replace.
 * @param string   plural       The plural string to replace.
 * @param integer  n            The number to determine the translation for the respective grammatical number.

 * @param ...mixed replacements Remaining arguments as compatible string replacement values.
 *
 * @return string
 */
export const ngettext = (singular, plural, n, ...replacements) => {
    let str;
    if (n === 1) {
        str = singular;
    } else {
        str = plural;
    }

    if (replacements.length === 0) {
        replacements = [n];
    }

    str = _(str, ...replacements);

    return str;
};