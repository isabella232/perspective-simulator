import * as DOM from './DOM.js';
import PropTypes from './PropTypes.js';

export const icon = function(name) {
    let classes = ['icon', 'glyphicon', `glyphicon-${name}`];
    return DOM.div({
        classes
    });
};
