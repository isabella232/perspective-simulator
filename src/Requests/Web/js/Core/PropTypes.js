import { curry, getType, flatten } from './Util.js';
import DOM from './DOM.js';

let numberExpr = new RegExp('^[0-9]+(\.[0-9]+)?$');
let colourExpr = new RegExp('^#([a-f0-9]{3}){1,2}$', 'i');

export const typeCheck = (type, value) => {
    const _type           = getType(value);
    const _defaultTypeMsg = `Expected property value of type "${type}" instead got "${_type}"`;
    switch (type) {
    case 'string':
    case 'object':
    case 'number':
    case 'boolean':
        if (_type !== type) {
            return _defaultTypeMsg;
        }
        break;

    case 'function':
        if (_type !== 'function') {
            return _defaultTypeMsg;
        }
        break;

    case 'asyncFunction':
        if (_type !== 'function') {
            return _defaultTypeMsg;
        }

        if (value.constructor.name !== 'AsyncFunction') {
            return `Expected function constructor "AsyncFunction" instead got "${value.constructor.name}"`;
        }
        break;

    case 'array':
        if (getType(value) !== 'array') {
            return _defaultTypeMsg;
        }
        break;

    case 'null':
        if (value !== null) {
            return _defaultTypeMsg;
        }
        break;

    case 'any':
        if (typeof value === 'undefined') {
            return _defaultTypeMsg;
        }
        break;

    default:
        return `Unknown property check type of value type "${type}".`;
    }

    return null;
};

let _decoratedNames = [];

export const decorateTypeCheckFn = (fn, name='UNKNOWN', args=null) => {
    if (_decoratedNames.indexOf(name) !== -1) {
        throw new Error(`Cannot redefine type check function "${name}"`);
    }

    fn._name               = name;
    fn.isRequired          = curry(fn);
    fn.isRequired._name    = name;
    fn.isRequired.required = true;
    fn._args               = args;
    return fn;

};

export const createTypeCheck = (type) => {
    let fn = curry(typeCheck, type);
    return decorateTypeCheckFn(fn, type);

};


export const string    = createTypeCheck('string');
export const number    = createTypeCheck('number');
export const func      = createTypeCheck('function');
export const asyncFunc = createTypeCheck('asyncFunction');
export const array     = createTypeCheck('array');
export const object    = createTypeCheck('object');
export const boolean   = createTypeCheck('boolean');
export const any       = createTypeCheck('any');
export const isNull    = createTypeCheck('null');

/**
 * Generates a validation function to test a matching object shape.
 *
 * @param object validators An object of validators.
 *
 * @return function
 */
export const shape = (validators) => {
    const _type = typeof validators;
    if (_type !== 'object') {
        throw `Shape property value was not an object, instead got ${_type}`;
    }

    const fn = (testObj) => {
        if (testObj === null) {
            return `Property value was null.`;
        }

        if (typeof testObj !== 'object') {
            return `Expecting an object instead got "${getType(testObj)}"`;
        }

        let failures = validate(testObj, validators);
        if (failures.length) {
            if (failures.length >= 1) {
                return failures[0];
            } else {
                return `Multiple property value types do not match the specified object shape.`;
            }
        }

        return null;
    };

    return decorateTypeCheckFn(fn, 'shape', validators);

};//end shape()


/**
 * Create a test that compares a given value with a set of allowed values.
 *
 * @param array values Array of possible values.
 *
 * @return function
 */
export const oneOf = (...values) => {
    values = flatten(values);

    values.forEach(val => {
        if (typeof val === 'function' && val.hasOwnProperty('_name') === true) {
            let msg = `PropTypes.oneOf does not support passing type check functions.`;
            msg +=    ` Was expecting a JS primitive value, instead got a "${val._name}" type check function.`;
            throw new Error(msg);
        }
    });

    const fn = testValue => {
        let pass = false;
        for (let i = 0, l = values.length; i<l; i++) {
            if (values[i] === testValue) {
                pass = true;
                continue;
            }
        }

        if (!pass) {
            return `Value "${testValue}" does not match required list of values.`;
        }

        return null;
    };

    return decorateTypeCheckFn(fn, 'oneOf', values);

};//end oneOf()

export const anyNumber = value => {
    if (getType(value) === 'string') {
        if (numberExpr.test(value) === false) {
            return `Value "${value}" did not match expected pattern for a stringified number.`;
        }

        return null;
    }

    return typeCheck('number', value);
};

export const integer = decorateTypeCheckFn(value => {
    if ((value*1) % 1 !== 0) {
        return `Value "${value}" is not an integer.`;
    }

    return null;
}, 'integer');


/**
 * Create a test that compares a given value with a set of allowed value types.
 *
 * @param array values Array of possible value types.
 *
 * @return function
 */
export const oneOfType = (...values) => {
    values = flatten(values);

    const fn = testValue => {
        let pass = false;
        for (let i = 0, l = values.length; i<l; i++) {
            if (values[i](testValue) === null) {
                pass = true;
                break;
            }
        }

        if (!pass) {
            let readableTypes = values.map(fn => {
                if (fn._name === 'arrayOf') {
                    return `${fn._name}(${fn._args._name})`;
                }

                if (fn._name) {
                    return fn._name;
                }

                return 'UNKNOWN';
            }).join(', ');
            return `Value "${JSON.stringify(testValue).substring(0, 100)}" does not match list of permitted value types (${readableTypes}).`;
        }

        return null;
    };

    return decorateTypeCheckFn(fn, 'oneOfType', values);

};


/**
 * Match an array of values against a validator.
 *
 * @param function validator Validator function to use for each value.
 *
 * @return function
 */
export const arrayOf = function(validator) {
    const fn =  testValues => {
        const type = getType(testValues);
        if (type !== 'array') {
            throw new Error(`Expecting value to be an array, instead got "${type}"`);
        }

        let failMsg = null;

        testValues.forEach(value => {
            // Skip remaining values, there was a failure earlier. We can't
            // exit a foreach but we can cause it to do nothing.
            if (failMsg !== null) {
                return;
            }

            // Determine any failure on the value.
            failMsg = validator(value);
            if (failMsg !== null) {
                failMsg = `ArrayOf value check failed: ${failMsg}`;
            }
        });

        return failMsg;
    };

    return decorateTypeCheckFn(fn, 'arrayOf', validator);

};

/**
 * Validate a properties object with a set of validator functions.
 *
 * @param object  props         The properties object to test.
 * @param object  validators    Validator functions.
 * @param boolean requiredCheck Whether to run required checks.
 * @param boolean strict        Checks if prop has a key that is not defined in validators.
 *
 * @return array
 */
export const validate = (props, validators, requiredCheck = true, strict = false) => {
    if (typeof props !== 'object') {
        throw `Unable to validate non-object value`;
    }

    // Required checks.
    if (requiredCheck === true) {
        const reqMsgs = Object.getOwnPropertyNames(validators)
            .map(key => {
                if (!validators[key]) {
                    throw new Error(`validator ${key} missing`);
                }
                return key;
            })
            .filter(key => validators[key].required)
            .filter(key => typeof validators[key] === 'function')
            .map(key => {
                const fn = validators[key];
                return {
                    key,
                    fn
                };
            })
            .map(v => {
                const reqMsg = `Key "${v.key}" is required but was not found.`;
                if (props.hasOwnProperty(v.key) === false) {
                    return reqMsg;
                }

                return null;
            })
            .filter(msg => msg !== null);

        if (reqMsgs.length) {
            return reqMsgs;
        }
    }

    if (strict === true) {
        for (var key in props) {
            if (validators.hasOwnProperty(key) === false) {
                return [`Key "${key}" is not a valid key for validator.`];
            }
        }
    }

    // Type checks.
    return Object.getOwnPropertyNames(validators)
        .filter(key => typeof props[key] !== 'undefined')
        .filter(key => typeof validators[key] === 'function')
        .map(key => {
            return {
                key,
                fn: validators[key]
            };
        })
        .map(v => {
            const msg = v.fn(props[v.key]);
            if (msg !== null) {
                return `Object property "${v.key}" -> ${msg}`;
            }

            return msg;
        })
        .filter(msg => msg !== null);
};

export const DOMNode = decorateTypeCheckFn(value => {
    const type = getType(value);
    return DOM.isNode(value) ? null : `Value is not a valid DOM node, instead got ${type}`;
}, 'DOMNode');

export const elem = decorateTypeCheckFn(value => {
    const type = getType(value);
    return (value instanceof window.Element) ? null : `Value is not a valid element, instead got ${type}`;
}, 'elem');

export const assert = (props, validators, requiredCheck=true, strict=false) => {
    let invalids = validate(props, validators, requiredCheck, strict);

    if (invalids.length) {
        throw new Error(invalids.join('\n'));
    }
};

export const custom = (name, fn) => {
    return decorateTypeCheckFn(fn, name);
};

export const colour = decorateTypeCheckFn(value => {
    if (colourExpr.test(value) === false) {
        return `Value is not a valid hex colour, instead got ${value}.`;
    }

    return null;
}, 'colour');

export const numberRange = (min, max) => {
    const fn = value => {
        const numMinCheck = number(min);
        const numMaxCheck = number(max);
        if (numMinCheck !== null) {
            return numMinCheck;
        }

        if (numMaxCheck !== null) {
            return numMaxCheck;
        }

        if (value < min || value > max) {
            return `Supplied number ${value} is out of range. Value must be between ${min} and ${max}`;
        }

        return null;
    };

    return decorateTypeCheckFn(fn, 'numberRange', {min, max});
};

export const regExp = (exprStr, exprFlags='') => {
    const expr = new RegExp(exprStr, exprFlags);
    const fn = value => {
        if (expr.test(value) === false) {
            return `Value "${value}" does not match regular expression /${exprStr}/${exprFlags}`;
        }

        return null;
    };

    return decorateTypeCheckFn(fn, 'regExp', {exprStr, exprFlags});
};

export const deprecated = function(date='unknown') {
    let fn = function(value) {
        return `This prop is deprecated since ${date}. The supplied value must either be removed or updated.`;
    };

    fn._name = `deprecated`;
    fn._args = date;

    return fn;
};

export default {
    any,
    assert,
    custom,
    string,
    number,
    anyNumber,
    integer,
    isNull,
    func,
    asyncFunc,
    function: func,
    asyncFunction: asyncFunc,
    array,
    object,
    shape,
    boolean,
    validate,
    oneOf,
    oneOfType,
    arrayOf,
    DOMNode,
    elem,
    colour,
    numberRange,
    regExp,
    deprecated
};