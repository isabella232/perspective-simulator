import PropTypes from '../../Core/PropTypes.js';
import DOM from '../../Core/DOM.js';
import Widget from '../../Core/Widget.js';
import { icon } from '../../Core/UITemplates.js';
import { stopClickPropagation } from '../../Core/Util.js';

Widget.define('ui-button', class extends Widget {
    get propTypes() {
        return {
            // Additional classes to add to the created button element.
            classes: PropTypes.arrayOf(PropTypes.string.isRequired),

            // Button content.
            text:    PropTypes.oneOfType([PropTypes.string, PropTypes.DOMNode, PropTypes.array]),

            // primary = blue, save = green, cancel = light-grey, dialog-editor = very dark grey.
            type: PropTypes.oneOf(['', 'primary', 'save', 'cancel', 'dialog-editor', 'dark-grey',
                'orange', 'red', 'white', 'transparent', 'purple', 'predeployment']),

            // default: bordered & drop shadowed
            // icon: unbordered icon with no text
            // flat: unbordered text
            style: PropTypes.oneOf(['', 'default', 'icon', 'flat']),

            // Optional icon.
            icon: PropTypes.string,

            // Read only differs from disabled. Read only won't bind events to the button so it
            // can still be rendered whereas disabled will add disabled attribute to the button.
            isReadOnly: PropTypes.boolean,

            // Disabled state (adds disabled attribute).
            isDisabled: PropTypes.boolean,

            // Visible state (adds hidden class).
            isVisible: PropTypes.boolean,

            // Hide text.
            isTextHidden: PropTypes.boolean,

            // Show/hide borders.
            isBordered: PropTypes.boolean,

            // Enable/disable right hand side chevron icon for the button.
            hasChevron: PropTypes.boolean,

            // Enable/disable right hand side right direction chevron icon for the button.
            hasChevronRight: PropTypes.boolean,

            // Stops the click propagation using a util function. For buttons inside tooltips and bubbles
            // this may need to be set to FALSE to prevent the bubble from hiding.
            stopClickPropagation: PropTypes.boolean,

            // Callbacks.
            onClick: PropTypes.func,

            context: PropTypes.any
        };
    }

    beforeInit() {
        this.state = {
            isDisabled:      false,
            isReadOnly:      false,
            isVisible:       true,
            text:            '',
            classes:         [],
            style:           '',
            type:            '',
            isTextHidden:    false,
            isBordered:      false,
            icon:            null,
            hasChevron:      false,
            hasChevronRight: false,
            context:         null,

            stopClickPropagation: true
        };
    }

    render() {
        if (this.state.isVisible === false) {
            return '';
        }

        let classes  = [this.prefix()].concat(this.state.classes);
        let children = [];
        let style    = {};

        if (this.state.icon !== null) {
            children.push(icon(this.state.icon));
        }

        if (this.state.isBordered === true) {
            classes.push(`${this.prefix()}--bordered`);
        }

        if (this.state.style !== '') {
            classes.push(this.prefix(`style--${this.state.style}`));
        }

        if (this.state.type !== '') {
            classes.push(this.prefix(`type--${this.state.type}`));
        }

        if (this.state.isTextHidden && this.state.isTextHidden === true) {
            classes.push(`is-text-hidden`);
        }

        if (this.state.hasChevron === true) {
            classes.push(`has-chevron`);
        }

        if (this.state.hasChevronRight === true) {
            classes.push(`has-chevron`);
            classes.push(`has-chevron-right`);
        }

        if (this.state.isReadOnly === true) {
            classes.push(`is-read-only`);
        }

        children.push(DOM.span({
            classes: this.prefix('label')
        }, this.state.text));

        let props = {
            classes,
            style
        };

        if (this.state.onClick && this.state.isReadOnly !== true) {
            props.onClick = e => {
                if (this.state.stopClickPropagation) {
                    stopClickPropagation(e);
                }
                this.state.onClick(e, this.state.context);
            };
        }

        if (this.state.isDisabled === true) {
            props.disabled = true;
        }

        return DOM.h('button', props, children);
    }
});
