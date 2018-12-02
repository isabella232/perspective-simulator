import * as Util from './Util.js';
import { handleError, ERR_DOM } from './Error.js';
import PropTypes from './PropTypes.js';

// Diff Types.
const CREATE_NODE       = 'CREATE_NODE';
const REPLACE_NODE      = 'REPLACE_NODE';
const SORT_NODE         = 'SORT_NODE';
const CREATE_TEXT       = 'CREATE_TEXT';
const UPDATE_TEXT       = 'UPDATE_TEXT';
const CREATE_ATTR       = 'CREATE_ATTR';
const REMOVE_ATTR       = 'REMOVE_ATTR';
const UPDATE_ATTR       = 'UPDATE_ATTR';
const MAP_EVENT         = 'CREATE_EVENT';
const SET_INNER_HTML    = 'SET_INNER_HTML';

const REMOVE_NODE             = 'REMOVE_NODE';
const REMOVE_EVENT            = 'REMOVE_EVENT';
const REMOVE_NODE_AT_INDEX    = 'REMOVE_NODE_AT_INDEX';
const REMOVE_NODES_FROM_INDEX = 'REMOVE_NODES_FROM_INDEX';

const INVOKE_REFERENCE_CALLBACK = 'INVOKE_REFERENCE_CALLBACK';
const INITIALISE_WIDGET         = 'INITIALISE_WIDGET';
const PROPAGATE_WIDGET_PROPS    = 'PROPAGATE_WIDGET_PROPS';

// Property type keys.
const PROP_TYPE_INTERNAL   = '__internal__';
const PROP_TYPE_EVENT      = 'events';
const PROP_TYPE_ATTR       = 'attr';

// Reserved attribute and property names.
const UI_ID    = '__ID__';
const reservedProps = [
    'state',
    'key',
    'propagate',
    'ref',
    UI_ID,
    'innerhtml',
    'data-inner-html'
];

// Internal state.
let elemCounter = 0;
let eventStore  = {};

/**
 * Reset internal state.
 *
 * @return void
 */
export const reset = () => {
    elemCounter = 0;
    eventStore  = {};
};

/**
 * Returns TRUE if the given nodeType matches the expected pattern
 * for a Widget.
 *
 * @param string nodeType The type of node (elem.nodeName would be an expected value).
 *
 * @return boolean
 */
export const isWidget = function(nodeType)
{
    if (nodeType.substr(0, 3) === 'ui-') {
        return true;
    }

    return false;

};//end isWidget()


/**
 * Returns TRUE if the given object is a valid node. Nodes must be
 * either objects with a 'type' value or a string.
 *
 * @param miced node Node to test.
 *
 * @return bool
 */
export const isNode = function(node)
{
    if (typeof node !== 'undefined'
        && node !== null
        && (typeof node === 'string' || node.hasOwnProperty('type') === true)
    ) {
        return true;
    }

    return false;

};//end isNode()


/**
 * Convert a properties object into a flattened array assigning category
 * types for the supplied keys.
 *
 * @param object props An object where keys map to the property name.
 *
 * @return array
 */
const _convertPropsList = function(props)
{
    return Object.getOwnPropertyNames(props)
        .map(name => {
            const value          = props[name];
            const normalisedName = name.toLowerCase();
            const type           = getPropType(normalisedName);
            return {
                name: normalisedName,
                type,
                value
            };
        });

};//end _convertPropsList()

/**
 * Iterate node children with a callback for each node.
 *
 * @param object   node           Virtual DOM node.
 * @param function callback       Callback for each node.
 * @param boolean  includeWidgets Set to TRUE to allow it to visit widget nodes.
 *
 * @return void
 */
export const eachNode = function(node, callback, includeWidgets=false) {
    if (includeWidgets === true) {
        callback(node);
    } else if (typeof node === 'object' && isWidget(node.type) === false) {
        callback(node);
    } else {
        return;
    }

    if (node.hasOwnProperty('children') && node.children && node.children.length) {
        node.children.forEach(child => eachNode(child, callback, includeWidgets));
    }
};

/**
 * Prepare a styles object by converting it to a string.
 *
 * @param object styles CSS styles as key, value pairs.
 *
 * @return string
 */
const _prepareStylesValue = (styles) => {
    if (typeof styles === 'object' && styles !== null) {
        return Object.getOwnPropertyNames(styles).map(key => {
            let suffix = '';

            // If certain values are supplied as numbers then suffix them with px.
            if (['left', 'top', 'bottom', 'right', 'width', 'min-width'].indexOf(key) !== -1
                && typeof styles[key] === 'number'
            ) {
                suffix = 'px';
            }

            return `${key}: ${styles[key]}${suffix};`;
        }).join(' ');
    }

    return '';

};//end _prepareStylesValue()


/**
 * 'Hyperscript' Virtual DOM method.
 *
 * This method provides a public interface to generate a virtual dom node.
 *
 * @param string type     The node type, e.g. 'div'.
 * @param object props    Node properties.
 * @param array  children A list of child nodes (should also be created using this method, or
 *                        string values).
 *
 * @return object A virtual DOM node.
 */
export const h = function(type, props, children=null)
{
    props               = props || {};
    const filteredProps = _convertPropsList(props);

    if (props.hasOwnProperty('props') === true) {
        throw new Error(`"props" is deprecated, use "state" instead.`);
    }

    // Filtered properties into different category types.
    const internal      = filteredProps.filter(prop => prop.type === PROP_TYPE_INTERNAL);
    const events        = filteredProps.filter(prop => prop.type === PROP_TYPE_EVENT);
    const attr          = filteredProps
        .filter(prop => prop.type === PROP_TYPE_ATTR)
        .map(node => {
            if (node.name === 'classes') {
                node.name = 'class';
                if (Array.isArray(node.value) === true) {
                    node.value = node.value.join(' ');
                }
            }

            if (node.name === 'style') {
                node.value = _prepareStylesValue(node.value);
            }

            return node;
        });

    // Typecast children to array.
    if (children !== null && Array.isArray(children) === false) {
        children = [children];
    }

    PropTypes.assert({
        children
    }, {
        children: PropTypes.oneOfType(
            PropTypes.isNull,
            PropTypes.DOMNode,
            PropTypes.arrayOf(PropTypes.DOMNode)
        )
    });

    // Construct a virtual DOM object.
    const vdom = {
        type,
        children,
        events,
        attr,
        internal,
        isWidget: isWidget(type)
    };

    // Gather keys for child nodes to ensure that any key values are unique.
    if (children !== null) {
        const keys = children
            .filter(child => {if (typeof child === 'undefined') debugger; return child !== null && child.hasOwnProperty('internal')})
            .map(child => child.internal)
            .map(internal => {
                let found = null;
                internal.forEach(item => {
                    if (item.name === 'key') {
                        found = item.value;
                    }
                });
                return found;
            })
            .filter(key => key !== null);

        let duplicates = {};
        const hasDuplicateKey = keys.some((value, index) => {
            let isDuplicate = keys.indexOf(value) !== index;
            duplicates[value] = 1;
            return isDuplicate;
        });

        if (hasDuplicateKey === true) {
            let duplicateValues = JSON.stringify(Object.getOwnPropertyNames(duplicates));
            throw new Error(
                `Duplicate key values ${duplicateValues} found for child nodes, this must be unique`
            );
        }
    }

    return vdom;

};//end h()


/**
 * Sortcut method to create a node of inner HTML type.
 *
 * @param string html HTML content.
 *
 * @return object
 */
export const html = (html, props={}) => {
    return div(Object.assign({
        innerhtml: html
    }, props));

};//end html()


/**
 * Return a property type from the property name.
 *
 * @param string name The name of the property, e.g. 'class'.
 *
 * @return string
 */
export const getPropType = function(name) {
    if (reservedProps.indexOf(name) !== -1) {
        return PROP_TYPE_INTERNAL;
    }

    if (name.indexOf('on') === 0) {
        return PROP_TYPE_EVENT;
    }

    return PROP_TYPE_ATTR;

};//end getPropType()


/**
 * Creates a diff patch.
 *
 * @param string type  The type of patch to create.
 * @param object props Properties to assign to the diff (e.g. DOM elem).
 *
 * @return object
 */
const createPatch = function(type, props) {
    return {
        type,
        props
    };

};//end createPatch()


/**
 * Create a DOM element from a virtual DOM node.
 *
 * @param object node Virtual DOM node.
 *
 * @return object
 */
export const createElement = function(node)
{
    let elem = null;
    const nextElemID = elemCounter + 1;

    if (typeof node === 'string') {
        elem = document.createTextNode(node);
    } else if (node.hasOwnProperty('type') === true) {
        elem = document.createElement(node.type);
        elem[UI_ID] = nextElemID;
    } else {
        throw new Error(`Unable to create element from supplied node: ${node}`);
    }

    // Set internal key for lookups.
    if (elem && node.internal && node.type) {
        let key = node.internal.filter(item => item.name === 'key');
        if (key.length) {
            elem.key = key[0].value;
        }
    }

    if (elem) {
        elemCounter = nextElemID;
    }

    return elem;

};//end createElement()


export const getEventId = function(elem) {
    let id = elem[UI_ID] || null;
    if (!id) {
        return false;
    }

    if (elem.key) {
        id = `${id}-${elem.key}`;
    }

    return id;
};


/**
 * Returns TRUE if a given event type and subscriber function exists for a DOM element.
 *
 * @param object   elem DOM element.
 * @param string   type Event type, e.g. 'click'.
 * @param function fn   The subscriber function assigned to the event type.
 *
 * @return boolean
 */
export const eventExists = function(elem, type, fn)
{
    const id = getEventId(elem);
    if (!id) {
        return false;
    }

    // Use toString matches for event functions that already exist.
    if (eventStore[id] && eventStore[id][type] && eventStore[id][type].fn === fn) {
        return true;
    }//end if

    return false;

};//end eventExists()


/**
 * Remove an event type from a given DOM element.
 *
 * @param object           elem DOM element.
 * @param string|undefined type Event type, e.g. 'click'. Leave undefined to remove all events.
 *
 * @return boolean
 */
export const removeEvent = function(elem, type) {
    const id = getEventId(elem);
    if (!id) {
        return;
    }

    if (eventStore[id] && elem) {
        if (typeof type === 'undefined') {
            Object.keys(eventStore[id]).forEach(type => {
                eventStore[id][type].unsub();
            });
            delete eventStore[id];
            return true;
        }
        // Deleting the element will eventually result in gc to clean up
        // stray bindings.
        if (eventStore[id][type]) {
            eventStore[id][type].unsub();
            delete eventStore[id][type];
            return true;
        }
    }

    return false;

};//end removeEvent()


/**
 * Maps an event type and subscriber function to a DOM element.
 *
 * @param object   elem DOM element.
 * @param string   type Event type, e.g. 'click'.
 * @param function fn   Subscriber function to assign.
 *
 * @return object An event observable
 */
export const mapEvent = function(elem, type, fn) {
    if (typeof fn !== 'function') {
        throw new Error(`Was expecting function to bind event "${type}" to elem ${elem.nodeName}`);
    }

    const id = getEventId(elem);

    if (id) {
        if (!eventStore[id]) {
            eventStore[id] = {};
        }

        let updateEvent = false;
        if (!eventStore[id][type]
            || (fn.live || ''+eventStore[id][type].fn !== ''+fn)
        ) {
            updateEvent = true;
        }

        if (updateEvent === true) {
            // Normalise the event to match things like 'onclick' with 'click'.
            const eventType = type.replace(/^on/i, '');

            if (eventStore[id][type]) {
                eventStore[id][type].unsub();
            }

            // Create event listener.
            elem.addEventListener(eventType, fn, false);
            const unsub = () => {
                elem.removeEventListener(eventType, fn);
            };

            eventStore[id][type] = {
                unsub,
                elem,
                fn
            };
        }
    }//end if

};//end mapEvent()


/**
 * Decorate a function so that it can be detected as 'live'.
 *
 * @param function fn The function to decorate.
 *
 * @return function
 */
export const liveEvent = fn => {
    fn.live = true;
    return fn;
};


/**
 * Create an attribute value.
 *
 * @param mixed value The value to convert.
 *
 * @return string
 */
export const convertAttributeValueToString = function(value)
{
    // Convert array of strings or non-string values to a valid value for html attributes.
    if (Array.isArray(value) === true) {
        value = value.reduce((a, b) => {
            return a + ' ' + b;
        }, '');
    } else if (typeof value !== 'string') {
        value = JSON.stringify(value);
    }

    return value;

};//end convertAttributeValueToString()


/**
 * Find an existing element as a direct descendent of a parent DOM element
 * by searching an internal key value.
 *
 * @param mixed  key    A key value (unique amongst sibling DOM elements).
 * @param object parent A parent DOM element.
 *
 * @return mixed Returns null if no matches are found, otherwise an object with the
 *               DOM element and the index it was found at.
 */
export const findElemByKey = function(key, parent)
{
    let foundElem = null;
    if (parent.childNodes) {
        parent.childNodes.forEach((elem, index) => {
            if (elem.key === key) {
                foundElem = {
                    elem,
                    index
                };
                return false;
            }
        });
    }

    return foundElem;

};//end findElemByKey()


/**
 * Recursively find the differences between a given virtual DOM node and the child nodes of a
 * parent DOM element.
 *
 * @param object       node   A virtual DOM node.
 * @param object       parent DOM element.
 * @param integer|null index  If known an index can be supplied to help match the virtual node with
 *                            a current child node of the parent DOM element. This is mostly used in
 *                            recursion of node children but can be used for initial comparison if the
 *                            parent node contains children that don't belong to the diff.
 * @param array        diffs  Diff results that get passed and pushed in the correct order for processing.
 *
 * @return array An array of Diff objects.
 */
export const diffNode = function(node, parent, index = null, diffs = []) {
    let elem        = null;
    let created     = false;
    let replaced    = false;
    let key         = null;
    let ref         = null;
    let innerHTML   = null;
    let noPropagate = false;
    let nextState   = {};
    let replacement;

    // Treat null nodes as empty text nodes.
    if (node === null) {
        let textType = CREATE_TEXT;
        if (parent.childNodes[index] && parent.childNodes[index].nodeName === '#text') {
            textType = UPDATE_TEXT;
        }

        diffs.push(createPatch(textType, {elem: parent.childNodes[index], value: ''}));
        return diffs;
    }

    // Detect for a valid node type.
    if (isNode(node) === false) {
        let nodeType = Util.getType(node);
        throw new Error(`Node diff cannot be calculated, provided node value is not a node, instead got "${nodeType}"`);
    }

    // Extract internal props in a single loop.
    if (node.internal) {
        node.internal.forEach(item => {
            if (item.name === 'key') {
                key = item.value;
            }

            if (item.name === 'ref') {
                ref = item.value;
            }

            if (item.name === 'propagate') {
                noPropagate = (item.value === false);
            }

            if (item.name === 'innerhtml') {
                innerHTML = item.value;
            }

            if (item.name === 'state') {
                nextState = Object.assign(nextState, item.value);
            }
        });

        // If the key isn't empty then find any existing element that has the same
        // unique key for the current parent.
        if (key !== null) {
            let foundElem = findElemByKey(key, parent);
            if (foundElem) {
                elem = foundElem.elem;
            }

            if (!foundElem) {
                elem    = createElement(node);
                created = true;
            }
        }
    }//end if

    // Find the element from it's index (only if no key is given).
    if (index !== null
        && parent.childNodes
        && parent.childNodes[index]
        && elem === null
    ) {
        elem = parent.childNodes[index];
    }

    // If we don't have an existing element create one.
    if (!elem) {
        elem = createElement(node);
        if (!elem) {
            // Failed to create element, can't return a diff.
            return diffs;
        }

        created = true;
    }//end if

    // Do we have mismatched keys from a found element? If so it means the node should swapped.
    if (elem
        && key !== null
        && elem.key
    ) {
        let updateKeySortOrder = false;

        // There is not currently a child at the index, one will be created so make sure
        // we push a sort update for it.
        if (!parent.childNodes[index] || parent.childNodes[index].key !== elem.key) {
            updateKeySortOrder = true;
        }

        if (updateKeySortOrder) {
            diffs.push(createPatch(SORT_NODE, {elem, index, parent}));
        }
    }//end if

    // If the node at this position doesn't share the same node name then we
    // need to replace it with a newly rendered element as long as it wasn't replaced from any
    // previous checks.
    if (node.type
        && elem
        && node.type !== elem.nodeName.toLowerCase()
        && innerHTML === null
        && typeof replacement === 'undefined'
    ) {
        replacement = createElement(node);
        if (!replacement) {
            return diffs;
        }

        diffs.push(createPatch(REPLACE_NODE, {elem, replacement, parent}));
        replaced = true;
        elem     = replacement;
    }//end if

    // Create the element (and initialise if it's a widget custom element).
    if (created === true) {
        diffs.push(createPatch(CREATE_NODE, {elem, parent, index, node}));

        // If it's a widget we initialise any state passed.
        if (node.isWidget === true) {
            diffs.push(createPatch(INITIALISE_WIDGET, {elem, node}));
        }
    }//end if

    // Diff element attributes.
    if (node.type && node.attr && elem.setAttribute) {
        let existingAttr = [];
        if (elem.attributes) {
            existingAttr = Array.from(elem.attributes)
                .map(attr => attr.name)
                .filter(name => reservedProps.indexOf(name) === -1);
        }

        const newAttrs = node.attr.map(prop => prop.name);

        // New attributes.
        node.attr.filter(attr => {
            return existingAttr.indexOf(attr.name) === -1;
        }).forEach(attr => {
            diffs.push(createPatch(CREATE_ATTR, {elem, name: attr.name, value: attr.value}));
        });

        // Removed attributes.
        existingAttr.filter(name => {
            return newAttrs.indexOf(name) === -1;
        }).forEach(name => {
            diffs.push(createPatch(REMOVE_ATTR, {elem, name}));
        });

        // Filter and add attributes whose values have changed.
        node.attr.filter(attr => {
            return existingAttr.indexOf(attr.name) !== -1 && attr.value !== elem.attributes[attr.name].nodeValue;
        }).forEach(attr => {
            diffs.push(createPatch(UPDATE_ATTR, {elem, name: attr.name, value: attr.value}));
        });
    }//end if

    // Gather any ref functions.
    if ((created === true || replaced === true) && elem && node.internal && ref !== null) {
        const refType = typeof ref;
        if (refType !== 'function') {
            throw new Error(
                `Reference callback was provided to virtual DOM node but was not a function. Instead got "${refType}"`
            );
        }

        diffs.push(createPatch(INVOKE_REFERENCE_CALLBACK, {elem, ref}));
    }//end if

    // Widget state propagation. If state was supplied in the virtual DOM they need to be passed to the
    // widget element via element.setState() to trigger the right sort of updates.
    if (node.isWidget === true && noPropagate === false) {
        if (Object.getOwnPropertyNames(nextState).length >= 1) {
            diffs.push(createPatch(PROPAGATE_WIDGET_PROPS, {elem, state: nextState}));
        }
    }

    // Text nodes.
    if (typeof node === 'string') {
        if (elem.nodeName === '#text' && elem.nodeValue !== node) {
            diffs.push(createPatch(UPDATE_TEXT, {elem, value: node}));
        }

        if (elem.nodeName !== '#text') {
            const replacement = createElement(node);
            diffs.push(createPatch(REPLACE_NODE, {elem, replacement, parent}));

            if (node.isWidget === true) {
                diffs.push(createPatch(INITIALISE_WIDGET, {elem: replacement, node}));
            }
        }

        // Exit here, nothing below this is valid for text elements.
        return diffs;
    }//end if

    // Create node event diffs.
    if (node.events) {
        const eventTypes = node.events.map(prop => prop.name);

        // Map an event if it doesn't exist.
        eventTypes.forEach((type, i) => {
            let fn = node.events[i].value;
            diffs.push(createPatch(MAP_EVENT, {elem, type, fn}));
        });

        // Remove any events that were previously mapped but no longer exist.
        let elemEvtId = elem[UI_ID];
        if (elemEvtId && eventStore[elemEvtId]) {
            const existingTypes = Object.getOwnPropertyNames(eventStore[elemEvtId]);
            const diffEvents    = existingTypes.filter(t => eventTypes.indexOf(t) === -1);
            diffEvents.forEach(type => {
                diffs.push(createPatch(REMOVE_EVENT, {elem, type}));
            });
        }
    }//end if

    // Inner HTML we set the content and ignore any child processing code.
    if (innerHTML !== null) {
        diffs.push(createPatch(SET_INNER_HTML, {elem, html: innerHTML}));
        return diffs;
    }

    // For any children of a non-widget virtual DOM node we need to recursively check them
    // for differences and add it to the patch.
    if (node.children
        && Array.isArray(node.children)
        && node.isWidget === false
    ) {
        let childKeys = [];

        // Find diffs between child elements. Null children may have come from empty text
        // nodes in an original DOM and need to be filtered out for diffs so they don't
        // throw unnecessary errors when recursing.
        node.children.forEach((child, i) => {
            if (child.hasOwnProperty('internal') === true) {
                child.internal.forEach(item => {
                    if (item.name === 'key') {
                        childKeys.push(item.value);
                    }
                });
            }

            diffs = diffNode(child, elem, i, diffs);
        });

        if (childKeys.length !== 0 && childKeys.length !== node.children.length) {
            throw new Error(`Found child key attributes but not all children have keys.`);
        }

        if (childKeys.length && elem.childNodes && elem.childNodes.length) {
            childKeys = Util.uniqueArray(childKeys);

            // Remove any nodes that were deleted by key.
            for (let i = 0, l = elem.childNodes.length; i<l; i++) {
                if (elem.childNodes[i].hasOwnProperty('key') === false || childKeys.indexOf(elem.childNodes[i].key) === -1) {
                    diffs.push(createPatch(REMOVE_NODE, {elem: elem.childNodes[i], parent: elem}));
                }
            }
        } else if (elem.childNodes && elem.childNodes.length > node.children.length) {
            // Remove any nodes that were deleted by index.
            const childNodes  = Array.from(elem.childNodes);
            const numChildren = node.children.length;
            childNodes.forEach((nodeToRemove, i) => {
                if (i >= numChildren) {
                    diffs.push(createPatch(REMOVE_NODE, {elem: nodeToRemove, parent: elem}));
                }
            });
        }
    }//end if

    // Deal with the case where the node has no children (perhaps removed), but the element still
    // has nodes.
    if (elem
        && node.isWidget === false
        && node.children
        && node.children.length === 0
        && elem.childNodes
        && elem.childNodes.length
    ) {
        Array.from(elem.childNodes).forEach((nodeToRemove) => {
            if (nodeToRemove.parentNode === elem) {
                diffs.push(createPatch(REMOVE_NODE, {parent: elem, elem: nodeToRemove}));
            }
        });
    }

    return diffs;

};//end diffNode()


/**
 * Patch the DOM with a change supplied with a given type and values.
 *
 * @param string type  The type of change to patch.
 * @param object props Contains a list of properties with names and values to be used for
 *                     the currentpatch.
 *
 * @return boolean Return TRUE if the DOM was mutated.
 */
export const patch = function(type, props) {
    let mutated = false;

    switch (type) {

    // Setting HTML of an element directly.
    case SET_INNER_HTML:
        if (props.elem) {
            props.elem.setAttribute('data-inner-html', true);
            props.elem.innerHTML = props.html;
            mutated = true;
        }
        break;

    // Creating a new DOM node.
    case CREATE_NODE:
        if (props.index !== null
            && props.parent.childNodes.length
            && props.parent.childNodes[props.index]
        ) {
            const nextSibling = props.parent.childNodes[props.index].nextSibling;
            if (nextSibling) {
                props.parent.insertBefore(props.elem, nextSibling);
            } else {
                props.parent.appendChild(props.elem);
            }
        } else {
            props.parent.appendChild(props.elem);
        }

        mutated = true;
        break;

    // Initialising a widget that has been defined via a virtual DOM node. Pass any internal state & props
    // to the widget along with any children.
    case INITIALISE_WIDGET:
        if (!props.elem || !props.elem.setState) {
            return;
        }

        let stateToSet = {};
        if (props.node.internal && props.elem) {
            props.node.internal.forEach(internal => {
                if (internal.name === 'state') {
                    stateToSet = Object.assign(stateToSet, internal.value);
                }
            });
        }

        // Pass through any child nodes.
        if (props.node.children && props.elem) {
            stateToSet = Object.assign(
                {},
                stateToSet,
                {
                    children: props.node.children
                }
            );
        }

        if (props.elem && ''+stateToSet !== '{}') {
            props.elem.setState(stateToSet, false);
        }
        break;

    // Propagate changed widget props to the element.
    case PROPAGATE_WIDGET_PROPS:
        props.elem.setState(props.state);
        break;

    // Remove an element from the DOM.
    case REMOVE_NODE:
        removeEvent(props.elem);
        if (props.elem.parentNode === props.parent) {
            props.parent.removeChild(props.elem);
        }
        mutated = true;
        break;

    // Remove a single node at an index. Be careful not to remove multiple indexes
    // because the indexes will change as children are removed.
    case REMOVE_NODE_AT_INDEX:
        if (props.parent.childNodes[props.index]) {
            removeEvent(props.parent.childNodes[props.index]);
            props.parent.removeChild(props.parent.childNodes[props.index]);
            mutated = true;
        }
        break;

    // Remove all nodes from a given index.
    // e.g. [div1, div2, div3] removed from index 1 = [div1].
    case REMOVE_NODES_FROM_INDEX:
        let nodesToRemove = Array.from(props.parent.childNodes).slice(props.index);
        nodesToRemove.forEach(node => {
            props.parent.removeChild(node);
            mutated = true;
        });
        break;

    // Replace an element in the DOM with a new one.
    case REPLACE_NODE:
        props.parent.replaceChild(props.replacement, props.elem);
        mutated = true;
        break;

    // Change the index of an element in the DOM.
    case SORT_NODE:
        let currentNodeAtIndex = props.parent.childNodes[props.index];
        if (currentNodeAtIndex && props.elem !== currentNodeAtIndex) {
            props.parent.insertBefore(props.elem, currentNodeAtIndex);
            mutated = true;
        }
        break;

    // Creates a DOM text node.
    case CREATE_TEXT:
        const textNode = document.createTextNode(props.value);
        if (props.index !== null
            && props.parent.childNodes.length
            && props.parent.childNodes[props.index]
        ) {
            const nextSibling = props.parent.childNodes[props.index].nextSibling;
            if (nextSibling) {
                nextSibling.insertBefore(textNode);
            } else {
                props.parent.appendChild(textNode);
            }
        } else {
            props.parent.appendChild(textNode);
        }

        mutated = true;
        break;

    // Update a text node with a new value.
    case UPDATE_TEXT:
        props.elem.nodeValue = props.value;
        mutated = true;
        break;

    // Create or update attribute values.
    case CREATE_ATTR:
    case UPDATE_ATTR:
        let nodeName = props.elem.nodeName.toLowerCase();

        // Detect if the input is active and we are updating the 'value'. We don't want to update
        // while a user has focus.
        if ((nodeName === 'input' || nodeName === 'textarea')
            && props.name === 'value'
            && props.elem === document.activeElement
        ) {
            return mutated;
        }

        if ((nodeName === 'input' || nodeName === 'textarea')
            && props.name === 'value'
            && props.elem !== document.activeElement
        ) {
            props.elem.value = props.value;
            return mutated;
        }

        if (nodeName === 'input'
            && props.name === 'checked'
        ) {
            props.elem.checked = props.value;
            return mutated;
        }

        // Don't set disabled, just remove the attribute value.
        if ((props.name === 'disabled' || props.name === 'readonly')
            && props.value === false
        ) {
            if (props.elem.hasAttribute(props.name) === true) {
                props.elem.removeAttribute(props.name);
            }
            return mutated;
        }

        props.elem.setAttribute(props.name, convertAttributeValueToString(props.value));
        mutated = true;
        break;

    // Remove an attribute value.
    case REMOVE_ATTR:
        if (props.elem.nodeName === 'INPUT'
            && props.name === 'checked'
        ) {
            props.elem.checked = false;
            return mutated;
        }

        props.elem.removeAttribute(props.name);
        mutated = true;
        break;

    // Map an event to a DOM event listener.
    case MAP_EVENT:
        mapEvent(props.elem, props.type.toLowerCase(), props.fn);
        mutated = true;
        break;

    // Remove an event.
    case REMOVE_EVENT:
        removeEvent(props.elem, props.type);
        mutated = true;
        break;

    // Invoke a callback function if passed as a reference in the virtual DOM. This should be
    // queued after CREATE_NODE and REPLACE_NODE so it passes the correct element to the callback.
    case INVOKE_REFERENCE_CALLBACK:
        props.ref(props.elem);

        break;

    }

    return mutated;

};//end patch()

/**
 * Type casts a parsed attribute value.
 *
 * @param string value The value parsed from a string.
 *
 * @return mixed
 */
const _typeCastParsedValue = value => {
    // Trim whitespace.
    value = value
        .replace(/^\s+/, '')
        .replace(/\s+$/, '');

    // Try number/float value.
    let num = value * 1;
    if (isNaN(num) === false) {
        return num;
    }

    // Unwrap quotes for string.
    let quote = /^['"]+([^'"]*)['"]$/;
    if (value.match(quote) !== null) {
        value = value.replace(quote, '$1');
    }

    return value;

};//end _typeCastParsedValue()


/**
 * Parses an attribute value with special handling for specific attribute types.
 *
 * @param string name  The name of the attribute.
 * @param string value The value of the attribute.
 *
 * @return mixed
 */
export const parseDOMAttributeValue = (name, value) => {
    name = name.toLowerCase();

    if (name === 'style') {
        let props = value.split(/;/)
            .filter(prop => /^\s*$/.exec(prop) === null)
            .map(prop => {
                return prop.split(':')
                    .map(part => _typeCastParsedValue(part));
            });

        let styles = props.reduce((acc, prop) => {
            acc[prop[0]] = prop[1];
            return acc;
        }, {});

        return styles;
    }

    if (name === 'classes') {
        value = value.split(/\s+/);
    }

    return value;

};//end parseDOMAttributeValue()


/**
 * Parses widget state objects from the DOM (basically anything in
 * <script type="text/json>...</script> tags as a direct child).
 *
 * @param object elem The DOM node to search.
 *
 * @return object Null if not found.
 */
export const parseWidgetStateFromDOM = function(elem)
{
    let state         = null;
    let scripts       = elem.querySelectorAll('script') || [];
    let initStateElem = Array.from(scripts).filter(child => {
        if (child.parentNode !== elem) {
            return false;
        }

        return child.getAttribute('type').indexOf('/json') !== -1;
    }).pop();

    if (initStateElem) {
        try {
            state = JSON.parse(initStateElem.innerHTML);
        } catch(e) {
            throw new Error(`Unable to parse state from DOM script tag. ${e.message}`);
        }
    }

    return state;

};//end parseWidgetStateFromDOM()


/**
 * Creates a node from a supplied DOM element. This reverse engineering of the DOM element is useful for
 * Widgets who have been defined in the source HTML and have been given child elements.
 *
 * @param object  elem                DOM element.
 * @param boolean recurse             Set to FALSE to prevent recursively accessing DOM children.
 * @param boolean stripEmptyTextNodes Set to FALSE to allow empty text nodes.
 *
 * @return object Hyperscript DOM node.
 */
export const createNodeFromDOM = function(elem, recurse = true, stripEmptyTextNodes = true) {
    if (elem.nodeType === 1 || elem.nodeType === 11) {
        let attributes = {};
        let nodeName   = elem.nodeName.toLowerCase();

        if (elem.attributes) {
            Array.from(elem.attributes).forEach(attr => {
                attributes[attr.nodeName] = parseDOMAttributeValue(attr.nodeName, attr.nodeValue);
            });
        }

        let children = [];
        if (elem.childNodes.length && recurse === true) {
            children = Array.from(elem.childNodes)
                .map(child => {
                    return createNodeFromDOM(child, recurse, stripEmptyTextNodes);
                })
                .filter(node => node !== null);
        }

        if (isWidget(elem.nodeName.toLowerCase()) === true) {
            let state = parseWidgetStateFromDOM(elem);
            if (state !== null) {
                attributes = Util.assign(attributes, {state});
            }
        }

        return h(nodeName, attributes, children);
    } else  if (elem.nodeType === 3) {
        if (stripEmptyTextNodes === true && elem.textContent.match(/^\s*$/) !== null) {
            return null;
        }

        return elem.textContent;
    } else {
        return null;
    }//end if

};//end createNodeFromDOM()

let mountOps = {};

const _mount = (node, index, parent) => {
    function runDiff() {
        try {
            const diffs = diffNode(node, parent, index);

            diffs.forEach(diff => {
                patch(diff.type, diff.props);
            });
        } catch (e) {
            handleError(e, ERR_DOM);
        }
    }

    function createPromiseDiff(p) {
        return new Promise((resolve, reject) => {
            if (Util.isPromise(p) === true) {
                p.then(() => {
                    runDiff();
                    resolve(parent.childNodes[index]);
                }).catch(e => {
                    // Both resolve and reject this promise. Rejection bubbles the error to any
                    // catch() functions and resolve allows the promise to continue chaining then().
                    resolve();
                    reject(e);
                });
            } else {
                runDiff();
                resolve(parent.childNodes[index]);
            }
        });
    }

    mountOps[parent] = createPromiseDiff(mountOps[parent]);

    return mountOps[parent];
};


/**
 * Mount a virtual DOM node to a given parent element. If the node is already mounted
 * this method will apply a list of diffs from the virtual DOM node and the current state
 * of the DOM.
 *
 * @param object|string|array node The virtual DOM node (or array of DOM nodes) or a string to assign as innerHTML.
 * @param object                   parent DOM element.
 *
 * @return boolean|array Returns array of mounted elements on success, or FALSE on failure.
 */
export const mount = function(node, parent) {
    if (typeof parent === 'undefined') {
        throw new Error(`A parent element is required to mount`);
    }

    const mounted = Array.from(parent.childNodes)
        .map((elem, index) => {
            return {
                elem,
                index
            };
        })
        .filter(node => {
            return node.elem.hasOwnProperty(UI_ID) ? true : false;
        });

    if (!mounted.length) {
        // There are no mounted nodes, we need to ensure the DOM is clean to apply
        // new mounted nodes.
        parent.innerHTML = '';
    }

    if (typeof node === 'string') {
        parent.innerHTML = node;
        return true;
    } else if (isNode(node) === true) {
        return _mount(node, 0, parent);
    } else if (Array.isArray(node) === true) {
        let mountedNodes  = [];
        let mountPromises = [];

        // Mount existing nodes by index.
        node.forEach((n, i) => {
            if (isNode(n) === false) {
                console.warn(`Supplied mount node is not valid, instead got ${n}`);
            } else {
                mountPromises.push(_mount(n, i, parent));
                mountedNodes.push(n);
            }
        });

        // Remove any superflous nodes.
        if (parent.childNodes.length > node.length) {
            patch(REMOVE_NODES_FROM_INDEX, {index: node.length, parent});
        }

        if (mountedNodes.length !== node.length) {
            return false;
        }

        return Promise.all(mountPromises);
    }//end if

    return false;

};


/**
 * Flattens a provided property object (see _convertPropsList) into a single array of key/value pairs.
 *
 * @param object props Properties to flatten.
 *
 * @return array
 */
export const flattenProps = (props) => {
    let flattened = {};
    props.forEach(prop => {
        if (flattened.hasOwnProperty(prop.name) === true
            && typeof flattened[prop.name] === 'object'
        ) {
            flattened[prop.name] = Object.assign(flattened[prop.name], prop.value);
        } else {
            flattened[prop.name] = prop.value;
        }
    });

    return flattened;

};//end flattenProps()

/**
 * Append a child node after the node has been created.
 *
 * @param object node  Virtual DOM node (see h()).
 * @param object child Virtual DOM node to add as a child.
 *
 * @return object Modified virtual DOM node.
 */
export const append = (node, child) => {
    if (Array.isArray(child) === false) {
        child = [child];
    }

    if (Array.isArray(node.children) === false) {
        node.children = [];
    }

    node.children = node.children.concat(child);

    return node;
};//end append()

export const addProp = (node, type, name, value) => {
    if (!node[type].filter(prop => prop.name === name).length) {
        // New event, add it to the array.
        node[type].push({
            type,
            name,
            value
        });
    } else if (typeof value === 'function') {
        // Prop already exists and the value is a function, compose it.
        node[type].map(prop => {
            if (prop.name === name) {
                prop.value = Util.compose(prop.value, value);
            }

            return prop;
        });
    }

    return node;
};

export const addClass = (node, className) => {
    node.attr.map(attr => {
        if (attr.name === 'class') {
            let classes = attr.value.split(/\s+/);
            if (classes.indexOf(className) === -1) {
                attr.value += ` ${className}`;
            }
        }
    });

    return node;
};

export const removeClass = (node, className) => {
    node.attr.map(attr => {
        if (attr.name === 'class') {
            let re = new RegExp('\\b' + className + '\\b', 'g');
            attr.value = attr.value.replace(re, '');
        }
    });

    return node;
};

// Shorthand assignments.
export const div    = Util.curry(h, 'div');
export const span   = Util.curry(h, 'span');
export const button = Util.curry(h, 'button');
export const ul     = Util.curry(h, 'ul');
export const ol     = Util.curry(h, 'ol');
export const li     = Util.curry(h, 'li');
export const input  = Util.curry(h, 'input');

// Public methods.
export default {
    h,
    mount,
    append,
    addProp,
    addClass,
    removeClass,
    div,
    span,
    button,
    ul,
    ol,
    li,
    input,
    html,
    diffNode,
    patch,
    reset,
    isNode,
    flattenProps,
    createNodeFromDOM,
    parseWidgetStateFromDOM,
    parseDOMAttributeValue,
    liveEvent,
    eachNode
};
