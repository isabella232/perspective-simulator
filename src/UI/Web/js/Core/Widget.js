import { attachController } from './UI.js';
import * as Util from './Util.js';
import PropTypes from './PropTypes.js';
import DOM from './DOM.js';
import { handleError, ERR_WIDGET } from './Error.js';

/**
* Widget class.
*
* @package    Perspective
* @subpackage UI
* @author     Squiz Pty Ltd <products@squiz.net>
* @copyright  2017 Squiz Pty Ltd (ABN 77 084 670 600)
*/

/**
 * Returns a new candidate state by combining 2 state objects. If nextState
 * is passed as a function then the candidate state assignment is delegated
 * to that function.
 *
 * @param object          prevState The previous state.
 * @param object|function nextState The next state as an object, or a function
 *                                  that returns a next state object.
 *
 * @return object
 */
function getCandidateState(prevState, nextState)
{
    const stateType = typeof nextState;
    if (stateType === 'function') {
        return nextState(Object.assign({}, prevState));
    }

    if (stateType === 'object') {
        return Object.assign({}, prevState, nextState);
    } else {
        throw new Error(`Unable to generate new candidate state, the next state must be a function or object. Receive state type '${stateType}'`);
    }

}//end getCandidateState();


// Widget base class.
export default class Widget extends HTMLElement {
    /**
     * Constructor.
     *
     * Generally widget constructors won't be called since they are
     * initialised via attachment to the DOM (Web Component callback).
     *
     * @return void
     */
    constructor()
    {
        super();
        this.init();

    }//end constructor()


    /**
     * Define HTML Element with the browser.
     *
     * @param string tagName The element tag name.
     * @param object obj     The widget class object.
     * @param object options Any additional options to pass.
     *
     * @return void
     */
    static define(tagName, obj, options) {
        try {
            customElements.define(tagName, obj, options);
        } catch (e) {
            var eMsg = 'Custom elements not supported';
            if (e instanceof TypeError
                && (e.message != 'oldCustomElements is undefined' && e.message != 'customElements is undefined')
            ) {
                // The custom element is broke, better relay this message instead.
                eMsg = e.message;
            }

            throw new Error(eMsg);
        }

    }//end define()


    isInitialised() {
        return this._initialised === true;
    }


    /**
     * Initialises the Widget.
     *
     * @return void
     */
    init() {
        // Don't allow this to re-init.
        if (this.isInitialised() === true) {
            return;
        }

        // Set a default class prefix based on the node name.
        let prefix = this.nodeName
            .toLowerCase()
            .split('-')
            .reduce((acc, part, i) => {
                if (i === 0) {
                    return acc += part.toUpperCase();
                }

                return acc += Util.ucfirst(part);
            }, '');

        this.setPrefix(prefix);


        // When the widget is deleted from the DOM these callbacks
        // are triggered so it can be properly cleaned.
        this._detachedCallbacks = [];

        if (this.beforeInit) {
            this.beforeInit();
        }

        if (!this.state) {
            this.state = {};
        }

        this.state.children = [];

        // Intial state passed via JSON in <script> tags.
        const stateFromDOM = DOM.parseWidgetStateFromDOM(this);

        // Parse child nodes from the DOM and turn them into virtual children as properties.
        if (this.childNodes && this.childNodes.length) {
            const elemsToMap = Array.from(this.childNodes)
                .filter(elem => elem.nodeType === 1
                    && elem.nodeName !== 'SCRIPT'
                    && elem.nodeName !== 'TEMPLATE'
                );

            if (elemsToMap.length) {

                this.state.children = elemsToMap.map(elem => DOM.createNodeFromDOM(elem, true));

                // Remove child nodes.
                elemsToMap.forEach(elem => {
                    this.removeChild(elem);
                });
            }
        }

        // Mixin initial state from the DOM (script tag json content).
        if (stateFromDOM !== null) {
            if (typeof stateFromDOM === 'object') {
                this.state = Object.assign({}, this.state, stateFromDOM);
            }
        }

        this._initialised = true;

        // This controls the timing of the controller attachment.
        if (document.readyState !== 'complete') {
            window.addEventListener('load', this._attachController.bind(this));
        } else {
            requestAnimationFrame(() => {
                this._attachController();
            });
        }
    }

    _attachController() {
        attachController(this)
            .then(function attachControllerCallback() {
                // Run the updater.
                this._update();

                // Run after init code.
                if (this.afterInit) {
                    this.afterInit();
                }
            }.bind(this));
    }

    setPrefix(prefix)
    {
        this._prefix = prefix;

    }


    prefix(name=null)
    {
        if (name !== null) {
            return `${this._prefix}__${name}`;
        }

        return this._prefix;
    }

    validatePropTypes(state, requiredCheck = true)
    {
        // Validate against propTypes.
        if (this.propTypes) {
            const errors = PropTypes.validate(state, this.propTypes, requiredCheck);
            if (errors.length) {
                let errorMsg = `Value type check failure. `;
                errorMsg += errors.join('\n');
                let err = new Error(errorMsg);
                handleError(err, ERR_WIDGET, this);
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Runs if the widget will receive new state. Widgets are able
     * to manipulate incoming properties by using this method and is
     * useful for solving possible conflicts with property names, or
     * preparing property values for rendering.
     *
     * @param object state State that will be set.
     *
     * @return object This method must return a state object,
     */
    willReceiveState(state)
    {
        return state;

    }//end willReceiveState()


    afterInit() {

    }

    willUpdate() {

    }

    didUpdate() {

    }

    didReceiveState(state) {}

    /**
     * Lifecycle callback after the widget receives new state.
     *
     * @return void
     */
    _update()
    {
        try {
            const data = Object.assign({}, {state: this.state});

            if (this.validatePropTypes(data.state) === true) {
                this.willUpdate();

                const node = this.render();
                DOM.mount(node, this);

                this.didUpdate();

                // Trigger mount lifecycle callback once.
                if (!this._mounted && this.didMount) {
                    this.didMount(node);
                    this._mounted = true;
                }
            }
        } catch (err) {
            handleError(err, ERR_WIDGET, this);
        }

    }//end _update()


    /**
     * Sets the next value for state. Passed state must be an object
     * but doesn't need to match the current state (i.e. you may pass an object
     * with a single key and value if that is all you wish to set). Old and new
     * state will be merged together.
     *
     * @param object nextState Next state to set.
     *
     * @return void
     */
    setState(nextState, update = true)
    {
        try {
            if (nextState === null || typeof nextState === 'undefined') {
                return;
            }

            const candidateState = getCandidateState(this.state, nextState);

            if (Util.same.object(candidateState, this.state) === false) {
                const state = this.willReceiveState(candidateState);
                if (this.validatePropTypes(nextState, false) === true) {
                    this.state = {...state};
                    this.didReceiveState(nextState, update);
                    if (update === true) {
                        this._update();
                    }
                }
            }
        } catch (err) {
            handleError(err, ERR_WIDGET, this);
        }

    }//end setState()


    /**
     * Web component attached lifecycle event. Whenever this component
     * is mounted to the DOM this method will be called. Using this behavior
     * this is the main bootstrapper method for initialising a new widget.
     *
     * @return void
     */
    attachedCallback()
    {
        this.init.call(this);

    }//end attachedCallback()

    // DO NOT overload this.
    addDetachedCallback(fn) {
        this._detachedCallbacks.push(fn);
    }

    // DO NOT overload this.
    detachedCallback() {
        if (this.state.didUnMount) {
            this.state.didUnMount();
        }

        this._detachedCallbacks.forEach(fn => fn());
    }

    /**
     * See this.attachedCallback().
     *
     * This is required for customElements.define().
     */
    connectedCallback()
    {
        this.attachedCallback.call(this);

    }//end connectedCallback()


    /**
     * See this.detachedCallback().
     *
     * This is for customElements.define().
     */
    disconnectedCallback()
    {
        this.detachedCallback.call(this);

    }//end disconnectedCallback()

    /**
     * Widget render method. This should be implemented by every widget
     * that inherits from this class and should return either a string value
     * or virtual DOM node (UI.DOM.h - hyperscript object).
     *
     * @return void
     */
    render()
    {
        throw 'The widget requires a render() method';

    }//end render()

}
