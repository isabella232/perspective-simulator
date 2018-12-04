/**
 * Return a curried function with supplied arguments.
 *
 * @param Function fn    The function to curry.
 * @param ...mixed args1 Arguments to supply.
 *
 * @return function
 */
export const curry = (f, ...args) => {
    if (f.length <= args.length) {
        return f(...args);
    }

    return (...more) => curry(f, ...args, ...more);
};

/**
 * Composes several functions together.
 * https://gist.github.com/WaldoJeffers/905e14d03f4283599bac753f73b7716b
 *
 * @param  {...function} fns Array of functions.
 *
 * @return function
 */
export const compose = (...fns) => fns.reduce((f, g) => (...args) => f(g(...args)));

/**
 * Return a new state object given previous state and new values.
 *
 * @param object prevState Previous state object.
 * @param object newState  New state values to assign.
 *
 * Adapted from this: https://jsperf.com/structured-clone-objects/2
 *
 * @return object
 */
export const clone = (obj) => {
    let copy;

    // Values that cannot be copied.
    if (null === obj || typeof obj !== 'object') {
        return obj;
    }

    // Handle Date.
    if (obj instanceof Date) {
        copy = new Date();
        copy.setTime(obj.getTime());
        return copy;
    }

    // Handle Array.
    if (Array.isArray(obj) === true) {
        copy = [];
        for (let i = 0, len = obj.length; i < len; ++i) {
            copy[i] = clone(obj[i]);
        }
        return copy;
    }

    // Handle Object.
    if (obj instanceof Object) {
        copy = {};
        for (let attr in obj) {
            if (obj.hasOwnProperty(attr)) {
                copy[attr] = clone(obj[attr]);
            }
        }
        return copy;
    }

    throw new Error(`Unable to copy obj! Its type isn't supported.`);
};

/**
 * Recursively freeze an object or array's properties so they
 * cannot be modified.
 *
 * @param object obj The object to freeze.
 *
 * @return object
 */
export const deepFreeze = (obj) => {
    if (obj === null || typeof obj !== 'object') {
        return obj;
    }

    Object.getOwnPropertyNames(obj).forEach(name => {
        if (typeof obj[name] === 'object' && obj[name] !== null) {
            deepFreeze(obj[name]);
        }
    });

    return Object.freeze(obj);
};

/**
 * Merge 2 or more objects together returning a new object.
 *
 * @param ...object args 2 or more objects as individual function arguments.
 *
 * @return object
 */
export const assign = (...args) => {
    return Object.assign.apply(null, [{}].concat(args));
};


/**
 * Flatten an array.
 *
 * @param array arr Array to flatten.
 *
 * @return array
 */
export const flatten = arr => {
    while (arr.find(el => Array.isArray(el))) {
        arr = Array.prototype.concat(...arr);
    }

    return arr;

};//end flatten()


export const uniqueArray = arr => {
    return arr.filter((elem, pos, arr) => {
        return arr.indexOf(elem) === pos;
    });
};


/**
 * Get the variable type.
 *
 * @param mixed v The variable to check.
 *
 * @return string
 */
export const getType = (v) => {
    let type = typeof v;

    if (v === null) {
        return 'null';
    }

    if (type === 'object' && Array.isArray(v) === true) {
        type = 'array';
    }

    return type;

};//end getType()

/**
 * A union of 2 arrays with de-duplicated values.
 *
 * @param array a1 Array one.
 * @param array a2 Array two.
 *
 * @return array
 */
export const union = (a1, a2) => {
    let s1 = new Set(a1);
    let s2 = new Set(a2);
    let combined = new Set([...s1, ...s2]);
    return Array.from(combined);
};

/**
 * Returns the intersection of 2 arrays (shared values).
 *
 * @param array a1 Array one.
 * @param array a2 Array two.
 *
 * @return array
 */
export const intersection = (a1, a2) => {
    let intersection = new Set(
        [...a1].filter(x => a2.indexOf(x) !== -1)
    );
    return Array.from(intersection);
};

/**
 * Returns the difference of 2 arrays (values in a1 that are not shared with a2).
 *
 * @param array a1 Array one.
 * @param array a2 Array two.
 *
 * @return array
 */
export const difference = (a1, a2) => {
    let diff = new Set(
        [...a1].filter(x => a2.indexOf(x) === -1)
    );
    return Array.from(diff);
};


/**
 * Variable type and value comparisons.
 */
export const same = (() => {
    /**
     * Compares any variable with the value of another.
     *
     * @param mixed a First variable.
     * @param mixed b Second variable.
     *
     * @return boolean TRUE if the values match, FALSE otherwise.
     */
    const compare = (a, b) => {
        const t1 = getType(a);
        const t2 = getType(b);

        // Types are different
        if (t1 !== t2) {
            return false;
        }

        if (t1 === 'object') {
            return sameObject(a, b);
        } else if (t1 === 'function') {
            return sameFunction(a, b);
        } else if (t1 === 'array') {
            return sameArray(a, b);
        } else {
            return a === b;
        }
    };

    /**
     * Compares an object with the value of another. Keys need to match but order does not.
     *
     * @param mixed a First object.
     * @param mixed b Second object.
     *
     * @return boolean TRUE if the values match, FALSE otherwise.
     */
    const sameObject = (a, b) => {
        const t1 = getType(a);
        const t2 = getType(b);

        if (t1 !== 'object' || t2 !== 'object') {
            return false;
        }

        if ((a === null && b !== null) || (a !== null && b === null)) {
            return false;
        } else if (a === null && b === null) {
            return true;
        }

        let pass = true;
        Object.getOwnPropertyNames(a).forEach(p => {
            if (typeof b[p] === 'undefined') {
                pass = false;
            } else if (compare(a[p], b[p]) === false) {
                pass = false;
            }
        });

        Object.getOwnPropertyNames(b).forEach(p => {
            if (typeof a[p] === 'undefined') {
                pass = false;
            }
        });

        return pass;

    };//end sameObject()


    /**
     * Compares any function with the definition of another.
     *
     * @param mixed a First function.
     * @param mixed b Second function.
     *
     * @return boolean TRUE if the values match, FALSE otherwise.
     */
    const sameFunction = (a, b) => {
        const t1 = getType(a);
        const t2 = getType(b);

        if (t1 !== 'function' || t2 !== 'function') {
            return false;
        }

        if (''+a !== ''+b) {
            return false;
        }

        return true;

    };//end sameFunction()

    /**
     * Compares any array with the value of another.
     *
     * @param mixed a First array.
     * @param mixed b Second array.
     *
     * @return boolean TRUE if the values match, FALSE otherwise.
     */
    const sameArray = (a, b) => {
        const t1 = getType(a);
        const t2 = getType(b);

        if (t1 !== 'array' || t2 !== 'array') {
            return false;
        }

        if (a.length !== b.length) {
            return false;
        }

        let passCounter = a.filter((val1, i) => {
            const val2 = b[i];
            return compare(val1, val2);
        }).length;

        return passCounter === a.length;

    };//end sameArray()

    return {
        compare: compare,
        object:  sameObject,
        func:    sameFunction,
        array:   sameArray
    };
})();

export const ucfirst = (str) => {
    return str.charAt(0).toUpperCase() + str.slice(1);
};

// const memoize = fn => {
//     let fnCache = {}, key;
//     return (...args) => {
//         key = JSON.stringify(args);
//         if (cache.hasOwnProperty(key) === false) {
//             cache[key] = fn.apply(null, args));
//         }
//         return cache[key];
//     }
// };

/**
 * Generates a function to test new values from previous values as distinctly
 * different using a custom comparitor function.
 *
 * @param mixed    initialValue The initial value for comparison.
 * @param function comparitorFn The comparitor function which should return a boolean if arguments
 *                              a & b do not match.
 *
 * @return function
 */
export const distinctValue = function(initialValue=null, comparitorFn=same.compare) {
    let currentValue = initialValue;

    /**
     * Returns TRUE if the value supplied differs from a previous cached value.
     *
     * @param mixed newValue The new value to test.
     *
     * @return bool
     */
    return newValue => {
        const comparison = comparitorFn(currentValue, newValue);

        // Comparison is different, re-store the new value for future checks.
        if (comparison === false) {
            // If the value is an object store a copy, not the provided object.
            if (typeof newValue === 'object') {
                newValue = clone(newValue);
            }

            currentValue = newValue;
        }

        return comparison;
    };
};

/**
 * Promise wrapper for setTimeout to delay execution.
 *
 * @param integer ms Delay in milliseconds.
 *
 * @return Promise
 */
export const delay = function(ms) {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            resolve();
        }, ms);
    });
};

/**
 * Polls a function call with set intervals for a maximum number of times unless
 * the function does not throw an exception. The function will be immediately invoked
 * followed by retries at the specified interval.
 *
 * @param function fn          The function to call. Will invoke the function with the current
 *                             attempt number (mostly for testing).
 * @param integer  ms          The interval time in milliseconds.
 * @param integer  maxAttempts The maximum number of attempts to make.
 *
 * @return Promise.
 */
export const poll = function(fn, ms, maxAttempts=10) {
    return new Promise((resolve, reject) => {
        let intervalId = null;
        let attempts   = 0;

        const check = () => {
            attempts += 1;
            try {
                fn(attempts);
                clearInterval(intervalId);
                return resolve();
            } catch (e) {
                if (attempts >= maxAttempts) {
                    return reject(e);
                }
            }
        };

        check();
        intervalId = setInterval(check, ms);
    });
};

export const findParentNode = (node, selector) => {
    if (node && node.matches && node.matches(selector)) {
        return node;
    }

    while (node && node.parentNode) {
        node = node.parentNode;

        if (node && node.matches && node.matches(selector)) {
            return node;
        }
    }

    return null;
};

export const hasParentNode = (node, elem) => {
    if (node === elem) {
        return true;
    }

    while (node && node.parentNode) {
        node = node.parentNode;

        if (node && node === elem) {
            return true;
        }
    }

    return false;
};

export const isPromise = obj => {
    return !!obj && (typeof obj === 'object' || typeof obj === 'function') && typeof obj.then === 'function';
};

export const getElementPosition = elem => {
    let xPos = 0;
    let yPos = 0;

    while (elem) {
        if (elem.nodeName === 'BODY') {
            const xScroll = elem.scrollLeft || elem.ownerDocument.defaultView.scrollX;
            const yScroll = elem.scrollTop || elem.ownerDocument.defaultView.scrollY;

            xPos += (elem.offsetLeft - xScroll + elem.clientLeft);
            yPos += (elem.offsetTop - yScroll + elem.clientTop);
        } else {
            xPos += (elem.offsetLeft - elem.scrollLeft + elem.clientLeft);
            yPos += (elem.offsetTop - elem.scrollTop + elem.clientTop);
        }

        elem = elem.offsetParent;
    }

    return {
        xPos,
        yPos
    };
};

export const getOffsetAreaSize = (coordsX, coordsY, winWidth, winHeight, elWidth, elHeight) => {
    var offsetX =  null;
    var size    = null;
    if (coordsX < 0) {
        offsetX = Math.abs(coordsX - 0);
    } else if (coordsX + elWidth > winWidth) {
        offsetX = Math.abs((coordsX + elWidth) - winWidth);
    }

    var offsetY = null;
    if (coordsY < 0) {
        offsetY = Math.abs(coordsY - 0);
    } else if (coordsY + elHeight > winHeight) {
        offsetY = Math.abs((coordsY + elHeight) - winHeight);
    }

    if (offsetX !== null || offsetY !== null) {
        if (offsetX !== null && offsetY === null) {
            size = offsetX * elHeight;
        } else if (offsetX === null && offsetY !== null) {
            size = offsetY * elWidth;
        } else if (offsetX !== null && offsetY !== null) {
            size = (offsetX * elHeight) + (offsetY * elWidth) - (offsetX * offsetY);
        }

        return size;
    }

    return false;
};

/**
 * Returns TRUE if the element is off the visible screen.
 *
 * @param object element HTML element to check.
 *
 * @return boolean.
 */
export const isElementOffScreen = elem => {
    const coords       = getElementPosition(elem);
    const dims         = getElementDimensions(elem);
    const scrollCoords = getScrollCoords(elem.ownerDocument.defaultView);
    const windowDims   = getWindowDimensions(elem.ownerDocument.defaultView);

    coords.xPos -= scrollCoords.xPos;
    coords.yPos -= scrollCoords.yPos;
    if (coords.yPos + dims.height < 0
        || coords.yPos > windowDims.height
        || coords.xPos + dims.width < 0
        || coords.xPos > windowDims.width
    ) {
        return true;
    }

    return false;
};

/**
 * Get the height and width dimensions for an element.
 *
 * @param object  element The HTML element to get dimensions for.
 * @param boolean inner   Set to TRUE to only use the inner dimensions.
 *
 * @return object.
 */
export const getElementDimensions = (element, inner=false) => {
    if (inner) {
        return {
            width: element.clientWidth,
            height: element.clientHeight
        };
    } else {
        return {
            width: element.offsetWidth,
            height: element.offsetHeight
        };
    }
};

/**
 * Get the current X and Y scroll coordinates for a window object.
 *
 * @param object win The window object to use.
 *
 * @return object.
 */
export const getScrollCoords = win => {
    win = win || window;
    const coords = {
        xPos: win.scrollX,
        yPos: win.scrollY
    };

    return coords;
};

/**
 * Loops all parents and calculates the accumulated scroll values in both directions.
 *
 * @param object elem          The element to retrieve scroll for.
 * @param boolean includeSelct Whether to include the selected element in the calculations.
 */
export const getComputedScrollCoords = (elem, includeSelf=false) => {
    const coords = {
        xPos: 0,
        yPos: 0
    };

    if (includeSelf === false && elem.parentNode) {
        elem = elem.parentNode;
    }

    while (elem.parentNode) {
        coords.xPos += elem.scrollLeft;
        coords.yPos += elem.scrollTop;
        elem = elem.parentNode;
    }

    return coords;
};

/**
 * Get the scroll offset of an element. If recursive is true it will accumulate all the
 * scroll values from parent element.
 *
 * @param object  node      The node to start.
 * @param boolean recursive Recursive mode. If FALSE only the node provided will be used.
 * @param object  acc       The accumulation object.
 */
export const getScrollOffset = (node, recursive=true, acc={top: 0, left: 0}) => {
    if (node) {
        if (node.scrollTop && node.scrollTop !== 0) {
            acc.top += node.scrollTop;
        }
        if (node.scrollLeft && node.scrollLeft !== 0) {
            acc.left += node.scrollLeft;
        }
    }

    if (recursive === true && node.parentNode) {
        return getScrollOffset(node.parentNode, recursive, acc);
    }

    return acc;
};

export const getWindowDimensions = win => {
    win = win || window;
    const dim = {
        width:  win.innerWidth,
        height: win.innerHeight
    };

    return dim;
};

/**
 * Returns the position of the element relative to the specified top frame.
 *
 * @param {DomElement} elem     The element.
 * @param {DomElement} topFrame The most outer frame or null for main frame.
 *
 * @return void
 */
export const getRelativeWindowPosition = (elem, topFrame) => {
    let offset = null;
    if (elem.ownerDocument.defaultView.frameElement) {
        const frameElement = elem.ownerDocument.defaultView.frameElement;
        offset = getElementPosition(elem);
        if (frameElement !== topFrame) {
            const frameOffset = getRelativeWindowPosition(frameElement, topFrame);
            offset.xPos += frameOffset.xPos;
            offset.yPos += frameOffset.yPos;
        }

    } else {
        offset = getElementPosition(elem);
    }

    let scrollCoords = getScrollCoords(elem.ownerDocument.defaultView);

    offset.yPos        -= scrollCoords.yPos;
    offset.xPos        -= scrollCoords.xPos;

    return offset;
};

/**
 * Returns the coordinates of a rectangle that will cover the element.
 *
 * Returns the coordinates of the top left corner (x1, y1) as well as the
 * bottom-right corner (x2, y2).
 *
 * @param {DomElement} element The element which we want the dimensions for.
 *
 * @return The 2 x and 2 y coordinates of the element's bounding rectangle.
 * @type   Object
 */
export const getBoundingRectangle = element => {
    // Retrieve the coordinates and dimensions of the element.
    // Use getBoundingClientRect because it would respect css transform position.
    const coords     = element.getBoundingClientRect();

    // Create an array by using the elements dimensions.
    const result = {
        x1: parseInt(coords.x),
        y1: parseInt(coords.y),
        x2: parseInt(coords.x + coords.width),
        y2: parseInt(coords.y + coords.height)
    };

    return result;

};//end dom.getBoundingRectangle()


export const positionElement = (element, targetElement, options={}) => {
    let relPos;

    if (targetElement === null || !element) {
        return;
    }

    // Check if the target element is off screen.
    if (isElementOffScreen(targetElement) === true
        && targetElement.ownerDocument.defaultView.frameElement === true
        && element.ownerDocument.defaultView.frameElement === true
    ) {
        relPos = getRelativeWindowPosition(
            targetElement.ownerDocument.defaultView.frameElement,
            element.ownerDocument.defaultView.frameElement
        );

        return {
            style: {
                left: relPos.xPos,
                top:  relPos.yPos,
            }
        };
    }

    // Get target elements position.
    if (element.ownerDocument.defaultView.frameElement === true) {
        relPos = getRelativeWindowPosition(targetElement, element.ownerDocument.defaultView.frameElement);
    } else {
        relPos = getRelativeWindowPosition(targetElement, element.ownerDocument.defaultView);
    }

    const targetRect = {
        x1: relPos.xPos,
        y1: relPos.yPos,
        x2: relPos.xPos + targetElement.offsetWidth,
        y2: relPos.yPos + targetElement.offsetHeight
    };

    // Get the rectangle of the element that will be moved.
    const elemRect = getBoundingRectangle(element);
    const elemH    = (elemRect.y2 - elemRect.y1);
    const elemW    = (elemRect.x2 - elemRect.x1);

    // Get window dimensions.
    const winDim   = getWindowDimensions(element.ownerDocument.defaultView);

    // Get scroll offset.
    let scrollOffset = {
        top:  0,
        left: 0
    };

    if (options.useScrollOffset === true) {
        scrollOffset = getScrollOffset(element);
    }

    let offsetArea      = null;
    let fallbackPosInfo = {};
    const _positionElement = (position, arrowPositions) => {
        let targetMidX = 0;
        let targetMidY = 0;

        if (Array.isArray(arrowPositions) === false) {
            throw new Error('Expecting an array for arrowPositions');
        }

        switch (position) {
        case 'top':
            targetMidX = targetRect.x1 + ((targetRect.x2 - targetRect.x1) / 2);
            targetMidY = targetRect.y1;
            break;

        case 'bottom':
            targetMidX = targetRect.x1 + ((targetRect.x2 - targetRect.x1) / 2);
            targetMidY = targetRect.y2;
            break;

        case 'left':
            targetMidX = targetRect.x1;
            targetMidY = targetRect.y1 + ((targetRect.y2 - targetRect.y1) / 2);
            break;

        case 'right':
            targetMidX = targetRect.x2;
            targetMidY = targetRect.y1 + ((targetRect.y2 - targetRect.y1) / 2);
            break;

        default:
            return false;
        }//end switch

        targetMidX -= scrollOffset.left;
        targetMidY -= scrollOffset.top;

        // Using the default position top left (of the intervention box) determine
        // the correct position.
        let arrowProps = options.arrowProps;
        if (!options.arrowProps) {
            const arrow = element.querySelector(`.${options.classPrefix}__arrow`) || {};
            arrowProps  = Object.assign({}, {
                width:  arrow.offsetWidth || 0,
                height: arrow.offsetHeight
            });
        }

        let oln           = arrowPositions.length;
        const arrowMargin = 6;

        for (let o = 0; o < oln; o++) {
            let posX  = 0;
            let posY  = 0;
            let classParts = arrowPositions[o].split('.');
            switch (classParts[0]) {
            case 'top':
                posY = targetMidY;
                break;

            case 'bottom':
                posY = (targetMidY - elemH - (arrowProps.height*2) - (targetElement.offsetHeight/2));
                break;

            case 'right':
                posX = (targetMidX - elemW - arrowProps.width/2);
                break;

            case 'left':
                posX = targetMidX;
                break;

            default:
                // Unknown type.
                break;
            }//end switch

            switch (classParts[1]) {
            case 'left':
                posX = targetMidX - arrowMargin;
                break;

            case 'right':
                posX = targetMidX - (elemW - arrowMargin - (arrowProps.width/2));
                break;

            case 'middle':
                if (classParts[0] === 'top' || classParts[0] === 'bottom') {
                    posX = (targetMidX - (elemW / 2));
                } else {
                    posY = (targetMidY - (elemH / 2));
                }
                break;

            case 'top':
                posY = (targetMidY - (arrowProps.height / 2) - arrowMargin);
                break;

            case 'bottom':
                posY = (targetMidY - (elemH - (arrowProps.height / 2) - arrowMargin));
                break;

            default:
                // Unknown type.
                break;
            }//end switch

            posX += options.offsetX || 0;
            posY += options.offsetY || 0;

            if (options.isFixed === true) {
                posX  = parseInt(posX, 10);
                posY  = parseInt(posY, 10);
            } else {
                //posX += scrollCoords.xPos;
                //posY += scrollCoords.yPos;
            }

            // Check if the element is on the screen.
            const currOffsetArea = getOffsetAreaSize(posX, posY, winDim.width, winDim.height, elemW, elemH);
            if (currOffsetArea === false) {
                // The target element fits on the screen well. Or it's the last position to try in the loop.
                // Either way, it needs to return true.
                return {
                    arrowPosition: classParts[0],
                    arrowAlign:    classParts[1],
                    style: {
                        left: posX,
                        top:  posY
                    }
                };
            } else {
                // If the element is cut off, then record the size of area being cut off so that later it can pick
                // the position that is cut off the least amount.
                if (offsetArea === null || currOffsetArea < offsetArea) {
                    offsetArea = currOffsetArea;
                    fallbackPosInfo = {
                        arrowPosition: classParts[0],
                        arrowAlign:    classParts[1],
                        posX,
                        posY
                    };
                }
            }
        }//end for

        return false;
    };

    if (options.positions) {
        for (var pos in options.positions) {
            if (options.positions.hasOwnProperty(pos) === true) {
                let posInfo = _positionElement(pos, options.positions[pos]);
                if (posInfo !== false) {
                    return posInfo;
                }
            }
        }
    }

    // Every options have been exhausted now. Let's just use the least covered position.
    if (offsetArea !== null) {
        return {
            arrowPosition: fallbackPosInfo.arrowPosition,
            arrowAlign:    fallbackPosInfo.arrowAlign,
            style: {
                left: fallbackPosInfo.posX,
                top:  fallbackPosInfo.posY
            }
        };
    }

    return false;
};


/**
 * Search an array of strings for matches to a search term.
 *
 * @param string      needle   String to search.
 * @param array|string haystack Array of strings to match.
 *
 * @return array
 */
export const wordMatch = (needle, haystack) => {
    const words = needle.match(/[A-Za-z0-9]+/gi);

    if (Array.isArray(haystack) === false) {
        haystack = [haystack];
    }

    return haystack.filter(search => {
        let found = false;

        words.forEach(word => {
            const reg = new RegExp(word, 'gim');
            if (reg.test(search) === true) {
                found = true;
            }
        });

        return found;
    });
};

const tagExpr = new RegExp(/<\/?(\w+)((\s+\w+(\s*=\s*(?:".*?"|'.*?'|[^'">\s]+))?)+\s*|\s*)\/?>/gim);

/**
 * Strip HTML tags from a string.
 *
 * @param string value       Value to replace.
 * @param array  allowedTags Any allowed tags to keep.
 *
 * @return string
 */
export const stripTags = (value, allowedTags=[]) => {
    let match;
    let resCont = value;
    while ((match = tagExpr.exec(value)) != null) {
        if (allowedTags.length === 0 || allowedTags.inArray(match[1]) !== true) {
            resCont = resCont.replace(match[0], '');
        }
    }

    return resCont;
};

/**
 * Sanitise a string value.
 *
 * @param string value The value to sanitise.
 *
 * @return string
 */
export const sanitiseValue = value => {
    return stripTags(value);
};

/**
 * Sanitise a file name value.
 *
 * @param string filename The file name to sanitise.
 *
 * @return string
 */
export const sanitiseFileName = filename => {
    filename = sanitiseValue(filename);
    filename = filename.replace(/[\/\\\\:\?\(\)\|\*]+/g, '');
    if (filename[0] === '.' || filename[0] === '_') {
        filename = filename.replace(/^[_\.]+/, '');
    }

    const dotIndex = filename.indexOf('.');
    if (dotIndex === -1 || dotIndex === 0) {
        filename = null;
    }

    return encodeURIComponent(filename);
};

/**
 * Convert a pixel value string to a number.
 *
 * @param string str Pixel value.
 *
 * @return number
 */
export const convertPixelValue = (str) => {
    if (typeof str === 'string' && str.indexOf('px') !== -1) {
        str = str.replace('px', '');
        str = 1*str;
    }

    return str;
};


/**
 * returns a trimmed string.
 *
 * @param {String} value The string to trim.
 * @param {String} trimChars The different chars to trim off both sides.
 *
 * @return {String}
 */
export const trim = (value, trimChars) => {
    if (!trimChars) {
        value = value.trim();
    } else {
        value = ltrim(rtrim(value, trimChars), trimChars);
    }

    return value;
};

/**
 * returns a left trimmed string.
 *
 * @param {String} value The string to trim.
 * @param {String} trimChars The different chars to trim off the left.
 *
 * @return {String}
 */
export const ltrim = (str, trimChars) => {
    trimChars = trimChars || '\\s';
    return str.replace(new RegExp('^[' + trimChars + ']+', 'g'), '');
};

/**
 * returns a right trimmed string.
 *
 * @param {String} value The string to trim.
 * @param {String} trimChars The different chars to trim off the right.
 *
 * @return {String}
 */
export const rtrim = (str, trimChars) => {
    trimChars = trimChars || '\\s';
    return str.replace(new RegExp('[' + trimChars + ']+$', 'g'), '');
}


export const getCSSRule = (element, key=[]) => {
    let value    = element.ownerDocument.defaultView.getComputedStyle(element, null);
    let filtered = {};

    key.forEach(ruleName => {
        filtered[ruleName] = convertPixelValue(value.getPropertyValue(ruleName));
    });

    return filtered;


};


export const getFilesFromDropEvent = e => {
    let files = [];
    const { dataTransfer } = e;

    if (dataTransfer.items) {
        for (let i = 0; i < dataTransfer.items.length; i ++) {
            if (dataTransfer.items[i].kind == 'file') {
                let file = dataTransfer.items[i].getAsFile();
                files.push(file);
            }
        }
    } else {
        for (let i = 0; i < dataTransfer.files.length; i ++) {
            files.push(dataTransfer.files[i]);
        }
    }

    return files;
};

export const isFileDragged = e => {
    // Only activate when files are being dragged.
    if (e.dataTransfer.items.length === 0
        || Array.from(e.dataTransfer.items).filter(item => item.kind === 'file').length === 0
    ) {
        return false;
    }

    return true;
};

/**
 * Parse a functions arguments into a readable array of variable names.
 *
 * @param function fn The function to parse.
 *
 * @return array
 */
export const parseFunctionArguments = function(fn)
{
    const DEFAULT_PARAMS = /=[^,]+/mg;
    const FUNC_NAME      = /^(async\s*)?(function\s*)?([^\(]*)?(\*\s*)?/;

    try {
        let fnStr = fn.toString();
        return fnStr
            .replace('=>', '')
            .replace(DEFAULT_PARAMS, '')
            .split('{')
            .shift()
            .replace(FUNC_NAME, '')
            .replace(/^\s*\(/, '')
            .replace(/\)\s*$/, '')
            .split(',')
            .map(arg => {
                if (arg.indexOf('=') !== -1) {
                    return arg.split('=').shift().trim();
                }
                return arg.trim();
            })
            .filter(arg => arg !== '');
    } catch (e) {
        return [];
    }

};//end parseFunctionArguments()

export const getAverageRGB = (image) => {
    var blockSize = 5;
    var canvas    = document.createElement('canvas');
    canvas.width  = image.naturalWidth;
    canvas.height = image.naturalHeight;
    var context   = canvas.getContext && canvas.getContext('2d');
    var height    = image.naturalHeight || canvas.height;
    var width     = image.naturalWidth || canvas.width;
    var data      = null;

    context.drawImage(image, 0, 0);

    try {
        data = context.getImageData(0, 0, width, height);
    } catch (e) {
        return false;
    }

    var rgb   = {r: 0, g: 0, b: 0};
    var count = 0;

    for (var i = ((blockSize * 4) - 4); i < data.data.length; (i += blockSize * 4)) {
        rgb.r += data.data[i];
        rgb.g += data.data[i + 1];
        rgb.b += data.data[i + 2];
        ++count;
    }

    rgb.r = ~~(rgb.r / count);
    rgb.g = ~~(rgb.g / count);
    rgb.b = ~~(rgb.b / count);

    var adjust = 30;
    if (((rgb.r + rgb.g + rgb.b) / 3) > 127) {
        rgb.r -= adjust;
        rgb.g -= adjust;
        rgb.b -= adjust;
    } else {
        rgb.r += adjust;
        rgb.g += adjust;
        rgb.b += adjust;
    }

    return rgb;
};

export const getBrightness = (image) => {
    const colourRGB  = getAverageRGB(image);
    return (Math.sqrt(
        colourRGB.r * colourRGB.r * 0.241 +
        colourRGB.g * colourRGB.g * 0.691 +
        colourRGB.b * colourRGB.b * 0.068
    ));
};

export const date = function(format, timestamp, tsIso8601)
{
    if (timestamp === null && tsIso8601) {
        timestamp = tsIso8601ToTimestamp(tsIso8601);
        if (!timestamp) {
            return;
        }
    }

    const names  = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
        'September', 'October', 'November', 'December'
    ];

    const date    = new Date(timestamp);
    const formats = format.split('');
    const fc      = formats.length;
    let dateStr   = '';

    for (let i = 0; i < fc; i++) {
        let r = '';
        let f = formats[i];
        switch (f) {
        case 'D':
        case 'l':
            r = names[date.getDay()];
            if (f === 'D') {
                r = r.substring(0, 3);
            }
            break;

        case 'm':
            r = addNumberPadding(date.getMonth()+1);
            break;

        case 'F':
        case 'M':
            r      = months[date.getMonth()];
            if (f === 'M') {
                r = r.substring(0, 3);
            }
            break;

        case 'd':
            r = addNumberPadding(date.getDate());
            break;

        case 'j':
            r = date.getDate();
            break;

        case 'S':
            r = getOrdinalSuffix(date.getDate());
            break;

        case 'Y':
        case 'y':
            r = date.getFullYear();
            if (f === 'y') {
                r = r.toString().substring(2);
            }
            break;

        case 'H':
            r = addNumberPadding(date.getHours());
            break;

        case 'h':
            r = date.getHours();
            if (r === 0) {
                r = 12;
            } else if (r > 12) {
                r -= 12;
            }
            break;

        case 'i':
            r = addNumberPadding(date.getMinutes());
            break;

        case 'a':
            r = 'am';
            if (date.getHours() >= 12) {
                r = 'pm';
            }
            break;

        default:
            r = f;
            break;
        }//end switch

        // Append the replacement to our date string.
        dateStr += r;
    }//end for

    return dateStr;

};

export const getUTCDate = function(timestamp, format)
{
    timestamp = new Date(timestamp).toUTCString();
    if (format) {
        timestamp = date(format, timestamp);
    }

    return timestamp;

}

export const getOrdinalSuffix = function(number)
{
    let suffix = '';
    const tmp  = (number % 100);

    if (tmp >= 4 && tmp <= 20) {
        suffix = 'th';
    } else {
        switch (number % 10) {
        case 1:
            suffix = 'st';
            break;

        case 2:
            suffix = 'nd';
            break;

        case 3:
            suffix = 'rd';
            break;

        default:
            suffix = 'th';
            break;
        }
    }//end if

    return suffix;

};

export const addNumberPadding = function(number)
{
    if (number < 10) {
        number = '0' + number;
    }

    return number;

};

export const tsIso8601ToTimestamp = function(tsIso8601)
{
    const regexp = /(\d\d\d\d)(?:-?(\d\d)(?:-?(\d\d)(?:[T ](\d\d)(?::?(\d\d)(?::?(\d\d)(?:\.(\d+))?)?)?(?:Z|(?:([-+])(\d\d)(?::?(\d\d))?)?)?)?)?)?/;
    const d      = tsIso8601.match(new RegExp(regexp));

    if (d) {
        let date = new Date();
        date.setDate(d[3]);
        date.setFullYear(d[1]);
        date.setMonth(d[2] - 1);
        date.setHours(d[4]);
        date.setMinutes(d[5]);
        date.setSeconds(d[6]);

        let timestamp = null;
        if (d[9]) {
            let offset = (d[9] * 60);

            if (d[8] === '+') {
                offset *= -1;
            }

            offset       -= date.getTimezoneOffset();
            timestamp = (date.getTime() + (offset * 60 * 1000));
        } else {
            timestamp = date.getTime();
        }

        return timestamp;
    }

    return null;

};

/**
 * Get the readable age of the difference of 2 dates.
 *
 * @param string      dateString A valid date string.
 * @param string|null now        Valid state string to compare, or null for current date time.
 * @param boolean     short      If TRUE use a short format output.
 *
 * @return string.
 */
export const readableAge = function(dateString, now=null, short=false)
{
    let dFrom     = new Date(dateString);
    let timestamp = dFrom.getTime();

    if (now === null) {
        let dNow = new Date();
        now = dNow.getTime();
    } else if (typeof now === 'string') {
        let dNow = new Date(now);
        now = dNow.getTime();
    } else {
        now = now.getTime();
    }

    let secs = (now - timestamp)/1000;
    let ago  = ' ago';
    let fn   = 'floor';
    if (secs < 0) {
        secs = (-secs);
        ago  = '';
        fn   = 'ceil';
    }

    let unit = 0;
    let word = null;

    if (secs > 2592000) {
        // More than 30 days.
        return date('M d', (timestamp * 1000));
    } else if (secs > 86400) {
        // More than 24 hours.
        unit = Math[fn]((secs / 86400));
        word = 'day';
    } else if (secs > 3600) {
        // More than 60 minutes.
        unit = Math[fn]((secs / 3600));
        word = 'hour';
    } else if (secs > 60) {
        // More than 1 minute.
        unit = Math[fn]((secs / 60));
        word = 'minute';
    }

    if (word === null) {
        if (short === true) {
            return `now`;
        } else {
            return `Just now`;
        }
    } else {
        let plural = '';
        if (unit > 1) {
            plural = 's';
        }

        if (short === true) {
            if (word === 'minute') {
                word = 'min';
            } else if (word === 'hour') {
                word = 'hr';
            }

            return `${unit} ${word}${plural}`;
        } else {
            return `${unit} ${word}${plural}${ago}`;
        }
    }
};


/**
 * Asynchronous state selector (Time To Live version). This function will store the response from
 * the provided async function for a set time (in seconds). When it expires it will allow the async
 * function to be run again and return a new result.
 *
 * @param function asyncFn     The async function.
 * @param function conditionFn The condition check function.
 * @param integer  timeToLive  The time to live in seconds.
 *
 * @return function.
 */
export const asyncSelectorTimeToLive = function(
    asyncFn,
    conditionFn,
    timeToLive=0
) {
    let lastRun = null;
    let cache   = null;

    return asyncSelector(
        asyncFn,
        conditionFn,
        () => {
            // TTL cached response.
            if (cache !== null && lastRun && timeToLive !== 0) {
                const now = (new Date()).getTime();
                const diff = (now - lastRun)/1000;
                if (diff < timeToLive) {
                    return cache;
                }
            }

            return null;
        },
        (value) => {
            lastRun = (new Date()).getTime();
            cache   = value;
        }
    );
};

/**
 * Asynchronous state selector (Time To Live version). This function will store the response from
 * the provided async function for a set time (in seconds). When it expires it will allow the async
 * function to be run again and return a new result.
 *
 * @param function asyncFn     The async function (must be async or return a promise).
 * @param function conditionFn The async condition check function. If this returns TRUE and cache
 *                             is null the asyncFn is called.
 * @param function cacheGet    (optional) The cache check function to return cached data, or null.
 * @param function cacheSet    (optional) Sets the cache value for later.
 *
 * @return function.
 */
export const asyncSelector = function(
    asyncFn,
    conditionFn=()=>{return true;},
    cacheGet=()=>{return null;},
    cacheSet=()=>{}
) {
    let request  = null;
    let response = null;

    return async (state) => {
        const pass = conditionFn(state);
        let cache  = cacheGet(state);

        if (cache !== null && request === null) {
            return cache;
        }

        if (request === null && pass === true) {
            request  = asyncFn(state);
            response = await request;
            request  = null;
        } else if (isPromise(request) === true) {
            response = await request;
            request  = null;
        }

        cacheSet(response);

        return response;
    };
};

export const getIndexOfElement = function(node) {
    return Array.from(node.parentNode.children).indexOf(node);
};

/**
 * Removes a query string part from URL.
 *
 * @param string url     The whole URL.
 * @param string idenifier The query identifier.
 *
 * @return string.
 */
export const removeFromQueryString = function (url, identifier) {
    if (url == undefined) {
        url = '';
    }

    if (identifier == undefined) {
        identifier = '';
    }

    // Remove the index we are after.
    var trimmedUrl = url.replace(new RegExp('&*' + identifier + '=[^&\\s\#]*', 'g'), '');

    // Remove any ? then nothing.
    trimmedUrl = trimmedUrl.replace(/^[?&]+|[?&]+$/g, '');

    // Replace any leftover ?& with ? .
    trimmedUrl = trimmedUrl.replace(/\?&/g, '?');

    // Replace any leftover ?# with # .
    trimmedUrl = trimmedUrl.replace(/\?\#/g, '\#');

    return trimmedUrl;

};

export const addToQueryString = function(url, addQueries) {
    var mergedUrl        = '';
    var base          = baseUrl(url);
    var queryStringArray = queryString(url);
    var mergedQry        = {...queryStringArray, ...addQueries};

    var queryStr = '?';
    for (var key in mergedQry) {
        queryStr = queryStr + key + '=' + mergedQry[key] + '&';
    }

    // More than just a ? to add to the URL?
    if (queryStr.length > 1) {
        // Put the URL together with qry str and take off the trailing &.
        mergedUrl = base + queryStr.substr(0, (queryStr.length - 1));
    } else {
        mergedUrl = url;
    }

    var anchorPartURL = anchorPart(url);
    if (anchorPartURL.length > 0) {
        mergedUrl = mergedUrl + anchorPartURL;
    }

    return mergedUrl;

};

export const anchorPart = function(url) {
    if (typeof url === 'string') {
        var aStartIdx = url.search(/\#/);
        if (aStartIdx === -1) {
            url = '';
        } else {
            url = url.substr(aStartIdx, (url.length - aStartIdx));
        }
    }

    return url;

};

export const queryString = function(url) {
    var result    = {};
    var qStartIdx = url.search(/\?/);
    if (qStartIdx === -1) {
        return result;
    } else {
        var aStartIdx = url.search(/\#/);
        if (aStartIdx === -1) {
            var anchorPartAdj = 0;
        } else {
            var anchorPartAdj = (url.length - aStartIdx + 1);
        }

        // QryStr part is between ? and # in the URL.
        var queryStr = url.substr((qStartIdx + 1), (url.length - qStartIdx - anchorPartAdj));
        if (queryStr.length > 0) {
            var pairs = queryStr.split('&');
            var len   = pairs.length;
            var pair  = [];
            for (var i = 0; i < len; i++) {
                // Is it a valid key value pair?
                if (pairs[i].search('=') !== -1) {
                    pair            = pairs[i].split('=');
                    result[pair[0]] = pair[1];
                }
            }

            return result;
        } else {
            return result;
        }
    }//end if

};

/**
 * Get file ext from filename string.
 *
 * @param string filename The whole filename.
 *
 * @return string.
 */
export const getFileExtension = function(filename) {
    let parts = filename.split('.');
    if (parts.length === 1) {
        return '';
    }

    let ext = parts[(parts.length - 1)].toLowerCase();
    return ext;

};

/**
 * Get file ext from filename string.
 *
 * @param string filename The whole filename.
 *
 * @return string.
 */
export const getUrlPath = function(fullUrl) {
    let protocolStrippedUrl = stripUrlProtcol(fullUrl);
    let protocolFreeBaseUrl = baseUrl(protocolStrippedUrl);

    let pStartIdx = protocolFreeBaseUrl.search(/\//);
    if (pStartIdx === -1) {
        return '';
    } else {
        // Get rid of the first slash.
        pStartIdx += 1;
        let path   = protocolFreeBaseUrl.substr(pStartIdx);
        return path;
    }

};

/*
 * Strips a protcol from a URL.
 *
 * @param {string} url The URL to strip the protocol from.
 *
 * @return string
 */
export const stripUrlProtcol = function(url) {
    let pStartIdx = url.search(/:\/\//);
    if (pStartIdx === -1) {
        return url;
    } else {
        // Add three so get rid of all of the ://.
        pStartIdx += 3;
        let protocolStrippedUrl = url.substr(pStartIdx);
        return protocolStrippedUrl;
    }

};

/*
 * Get base URL from a URL.
 *
 * @param {string} url The target URL.
 *
 * @return string
 */
export const baseUrl = function(fullUrl) {
    let qStartIdx = fullUrl.search(/\?|#/);
    if (qStartIdx === -1) {
        return fullUrl;
    } else {
        let baseUrl = fullUrl.substr(0, qStartIdx);
        return baseUrl;
    }

};

/*
 * Is a variable set?
 *
 * @param {objecy} The variable to test.
 *
 * @return boolean
 */
export const isset = function(v) {
    if (typeof v !== 'undefined' && v !== null) {
        return true;
    }

    return false;

};

/**
 * Get a mime category for a supplied mime type.
 *
 * @param {string} mimeType The file mime type.
 *
 * @returns {string}
 */
export const getMimeCategory = (mimeType) => {
    let mimeCategory = 'file';
    if (mimeType === 'text/javascript') {
        mimeCategory = 'js';
    } else if (mimeType === 'text/css') {
        mimeCategory = 'css';
    } else if (mimeType.indexOf('svg') !== -1 || mimeType.indexOf('xml') !== -1) {
        mimeCategory = 'xml';
    } else if (mimeType.indexOf('font') !== -1) {
        mimeCategory = 'font';
    } else if (mimeType.indexOf('image/') === 0) {
        mimeCategory = 'image';
    } else if (mimeType.indexOf('video') === 0 || mimeType.indexOf('mpeg') !== -1) {
        mimeCategory = 'video';
    }

    return mimeCategory;
};

let _uploadedFiles = {};
export const storeUploadedFile = (id, file) => {
    _uploadedFiles[id] = file;
};

export const getUploadedFile = id => {
    if (_uploadedFiles.hasOwnProperty(id) === false) {
        return null;
    }

    return _uploadedFiles[id];
};

export const clearUploadedFiles = (id=null) => {
    if (id !== null && _uploadedFiles.hasOwnProperty(id) === true) {
        delete _uploadedFiles[id];
        return;
    }

    _uploadedFiles = {};
};

/**
 * Check if a value is a valid string id.
 *
 * @param {string} value
 *
 * @returns boolean
 */
export const isValidStringid = (value) => {
    // No leading . or _.
    if (/^[\.0-9]+/.test(value) === true) {
        return false;
    }

    // Letter and number.
    if (/^[a-zA-Z0-9\-\_\.]+$/.test(value) === false) {
        return false;
    }

    return true;
};

export const isEmpty = (value) => {
    if (value) {
        if (value instanceof Array) {
            if (value.length > 0) {
                return false;
            }
        } else {
            for (var id in value) {
                if (value.hasOwnProperty(id) === true) {
                    return false;
                }
            }
        }
    }

    return true;

};

let _timeouts = {};
export const debounce = (id, fn, timeout=1000) => {
    if (_timeouts.hasOwnProperty(id)) {
        clearTimeout(_timeouts[id]);
    }

    _timeouts[id] = setTimeout(fn, timeout);
};

/**
 * Stops click propagation but still triggers the document click so that modals
 * and bubble widget states can still reset correctly.
 *
 * @param {object} e Click event object.
 */
export const stopClickPropagation = (e) => {
    e.stopPropagation();
    let event = new Event('click');
    document.dispatchEvent(event);
};