/**
 * Shared constant values.
 *
 * @package    Perspective
 * @subpackage UI
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2017 Squiz Pty Ltd (ABN 77 084 670 600)
 */

const constants = {};

const addImmutableProperty = (obj, key) => {
    if (obj.hasOwnProperty(key) === true) {
        throw `Unable to assign constant ${key} to shared constants object, it already exists.`;
    }

    Object.defineProperty(constants, key, {
        enumerable:   false,
        configurable: false,
        writable:     false,
        value:        key
    });
};

// Proxy the setting/getting of constant values so they can only ever occur once.
export default new Proxy(constants, {
    get: (container, key) => {
        if (container.hasOwnProperty(key) === false) {
            addImmutableProperty(container, key, {});
        }

        return container[key];
    },
    set: (container, key) => {
        addImmutableProperty(container, key);
    }
});