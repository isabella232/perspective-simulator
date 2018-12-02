/**
 * Content widget.
 *
 * @package    Perspective
 * @subpackage UI
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2017 Squiz Pty Ltd (ABN 77 084 670 600)
 */
import PropTypes from '../../Core/PropTypes.js';
import DOM from '../../Core/DOM.js';
import Widget from '../../Core/Widget.js';

Widget.define('ui-content', class extends Widget {
    get propTypes() {
        return {
            content:       PropTypes.oneOfType([PropTypes.DOMNode, PropTypes.array, PropTypes.isNull]),

            context:       PropTypes.any,
            classes:       PropTypes.arrayOf(PropTypes.string),

            onAfterUpdate:   PropTypes.func,
            onLoading:       PropTypes.func,
            onComplete:      PropTypes.func,
            onDocumentClick: PropTypes.func,

            afterUpdate:   PropTypes.deprecated('16/03/2018'),
            loading:       PropTypes.deprecated('16/03/2018'),
            complete:      PropTypes.deprecated('16/03/2018'),
            documentClick: PropTypes.deprecated('16/03/2018'),
            id:            PropTypes.deprecated('17/05/2018'),
        };
    }

    beforeInit() {
        let classAttr   = this.getAttribute('class');
        let baseClasses = [];
        if (classAttr) {
            baseClasses = classAttr.split(/\s/);
        }

        this.state = {
            baseClasses,
            classes: []
        };
    }

    didUpdate() {
        if (this.state.onLoading) {
            this.state.onLoading();
        }

        // Timeout here to give any appended innerHTML to have a chance
        // to intialise and be accessible to callbacks.
        setTimeout(() => {
            // If we have an iframe in the HTML content then bubble a click
            // event out to the initialised widget.
            const iframe = this.querySelector('iframe');
            if (iframe) {
                if (this.state.onDocumentClick) {
                    iframe.contentWindow.addEventListener(
                        'click',
                        this.state.onDocumentClick
                    );
                }

                iframe.addEventListener('load', () => {
                    if (this.state.onComplete) {
                        this.state.onComplete(iframe);
                    }
                });

            } else {
                if (this.state.onComplete) {
                    this.state.onComplete(this);
                }
            }

            if (this.state.onAfterUpdate) {
                this.state.onAfterUpdate(this, this.state);
            }
        }, 0);
    }

    renderContent(content)
    {
        if (typeof content === 'string') {
            return DOM.html(content);
        }

        return content;

    }

    render() {
        let classes = [];
        if (this.state.classes) {
            classes = this.state.baseClasses.concat(this.state.classes);
        }

        this.setAttribute('class', classes.join(' '));

        if (this.state.content) {
            return this.renderContent(this.state.content);
        } else if (this.state.children) {
            return DOM.div({
                classes: this.prefix()
            }, this.state.children);
        } else {
            return '';
        }
    }
});