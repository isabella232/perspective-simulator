/*global $:false*/
import PropTypes from '../../Core/PropTypes.js';
import DOM from '../../Core/DOM.js';
import Widget from '../../Core/Widget.js';
import { icon } from '../../Core/UITemplates.js';
import { clone, findParentNode, stopClickPropagation } from '../../Core/Util.js';
import { _ } from '../../Core/Localise.js';

const treeNodeProps = PropTypes.shape({
    id:         PropTypes.oneOfType([PropTypes.string, PropTypes.integer]).isRequired,
    parentid:   PropTypes.oneOfType([PropTypes.string, PropTypes.integer, PropTypes.isNull, PropTypes.array]).isRequired,

    // Title is used in title attribute of the tree node.
    title:      PropTypes.string,

    // Content to use for the node.
    content:    PropTypes.DOMNode.isRequired,

    classes:    PropTypes.array,
    childCount: PropTypes.integer,
    colour:     PropTypes.oneOf(['default', 'red', 'green', 'yellow']),

    sortOrder: PropTypes.integer,

    // Flags.
    isExpanded:   PropTypes.boolean.isRequired,
    isSelectable: PropTypes.boolean,

    // Optional validation callback for nodes to allow/prevent selection.
    // If added return false to prevent selection on node click.
    onSelectValidation: PropTypes.func,

    // Icon that appears before the content.
    icon: PropTypes.string,

    // Action buttons.
    actions: PropTypes.arrayOf(
        PropTypes.shape({
            id:      PropTypes.string.isRequired,
            icon:    PropTypes.string,
            isVisible:   PropTypes.boolean,
            onClick:     PropTypes.func.isRequired,
            onMouseDown: PropTypes.func,
            classes:     PropTypes.arrayOf(PropTypes.string)
        })
    )
}).isRequired;

Widget.define('ui-tree', class extends Widget {
    get propTypes() {
        return {
            nodes: PropTypes.arrayOf(treeNodeProps),

            onExpandToggle:    PropTypes.func,
            onNodeExpanded:    PropTypes.func,
            onSelectionChange: PropTypes.func,

            mode: PropTypes.shape({
                type: PropTypes.string.isRequired,
                options: PropTypes.object
            }),

            canSelectRoot:  PropTypes.boolean,
            canToggleRoot:  PropTypes.boolean,

            isMultipleSelect: PropTypes.boolean,
            selectedIds:      PropTypes.array,
            currentId:        PropTypes.oneOfType([PropTypes.string, PropTypes.isNull]),

            showEmptyText: PropTypes.boolean,
            emptyText:     PropTypes.string
        };
    }

    beforeInit() {
        this.state = {
            selectedIds:      [],
            currentId:        null,
            canToggleRoot:    false,
            canSelectRoot:    false,
            emptyText:        '',
            showEmptyText:    true,
            isMultipleSelect: true
        };
    }

    toggleExpand(id) {
        if (this.state.onExpandToggle) {
            this.state.onExpandToggle(id);
        } else {
            // By default expand/collapse node.
            let nodes = this.state.nodes.map(val => {
                if (val.id === id) {
                    return {...val, isExpanded: !val.isExpanded};
                }

                return val;
            });

            this.setState({
                nodes: nodes
            });
        }

        let isExpanded = false;
        this.state.nodes.forEach(node => {
            if (node.id === id) {
                isExpanded = node.isExpanded;
            }
        });
        if (this.state.onNodeExpanded) {
            this.state.onNodeExpanded(id, isExpanded);
        }
    }

    getValue() {
        return this.state.selectedIds;
    }

    filterByParentNode(parentid, node) {
        if (Array.isArray(node.parentid)) {
            return node.parentid.indexOf(parentid) !== -1;
        } else {
            return node.parentid === parentid;
        }
    }

    renderTree(nodes, parentid=null) {
        let nodesToRender = nodes.filter(this.filterByParentNode.bind(this, parentid));

        // Sort by sort order if available.
        nodesToRender = nodesToRender.sort((a, b) => {
            if (a.hasOwnProperty('sortOrder') === true && b.hasOwnProperty('sortOrder') === true) {
                if (a.sortOrder > b.sortOrder) {
                    return 1;
                } else {
                    return -1;
                }
            }

            return 0;
        });

        return nodesToRender.map(node => {
            let itemContent = [];
            let itemClasses = [this.prefix('item')];
            let children    = nodes.filter(this.filterByParentNode.bind(this, node.id));

            if (this.state.selectedIds.indexOf(node.id) >= 0) {
                itemClasses.push(`is-selected`);
            }

            if (this.state.currentId === node.id) {
                itemClasses.push(`is-current`);
            }

            if (parentid === null) {
                itemClasses.push(`is-root`);
            }

            if (children.length || (node.hasOwnProperty('childCount') && node.childCount > 0)) {
                itemClasses.push(`has-children`);

                if (node.isExpanded === true) {
                    itemClasses.push(`is-expanded`);
                }
            }

            // Append content.
            let contentChildren = [];

            if ((parentid !== null || this.state.canToggleRoot === true) && children.length) {
                contentChildren.push(
                    DOM.button({
                        classes: this.prefix('expander'),
                        title: _('Toggle display children'),
                        onClick: () => {
                            this.toggleExpand(node.id);
                        }
                    }, icon(node.isExpanded ? 'minus' : 'plus'))
                );
            }

            if (node.icon) {
                contentChildren.push(DOM.div({
                    classes: this.prefix('item-icon'),
                }, icon(node.icon)));
            }

            let nodeWrapper =  DOM.div({
                classes: [this.prefix('nodeWrapper')]
            }, DOM.div({
                classes: [this.prefix('node'), ...node.classes]
            }, node.content));

            contentChildren.push(nodeWrapper);

            let contentClasses = [this.prefix('content')];
            if (node.hasOwnProperty('colour') === true) {
                contentClasses.push(`${this.prefix('content')}--colour-${node.colour}`);
            }

            let contentEl = DOM.div({
                classes: contentClasses
            }, contentChildren);

            itemContent.push(contentEl);

            if (node.actions && node.actions.length > 0) {
                let actionItems = [];
                node.actions.forEach(action => {
                    let actionClasses = [this.prefix('action')];
                    if (action.isVisible) {
                        actionClasses.push('visible');
                    }

                    if (action.classes && action.classes.length) {
                        actionClasses = actionClasses.concat(action.classes);
                    }

                    actionItems.push(
                        DOM.div({
                            classes: actionClasses,
                            onClick: function(e) {
                                if (action.onClick) {
                                    action.onClick(e, node.id, action.id);
                                }
                            },
                            onMouseDown: (e) => {
                                if (action.onMouseDown) {
                                    action.onMouseDown(e, node.id, action.id);
                                }
                            }
                        }, icon(action.icon))
                    );
                });

                let actionsEl = DOM.div({classes: this.prefix('actions')}, actionItems);
                DOM.append(nodeWrapper, actionsEl);
            }

            // Child list recursion.
            if (children.length) {
                itemContent.push(
                    DOM.ul({
                        classes: this.prefix('childlist')
                    }, this.renderTree(nodes, node.id))
                );
            }

            if (node.isSelectable === false) {
                itemClasses.push('not-selectable');
            }

            return DOM.li({
                classes: itemClasses,
                nodeid:  node.id,
                key:     `${node.id}-${node.parentid}`,
                title:   node.title || node.id,
                onclick: async (e) => {
                    e.stopPropagation();

                    // Header node selection.
                    if (this.state.canSelectRoot === false && node.parentid === null) {
                        return;
                    }

                    // Selection validation.
                    if (this.state.onSelectValidation && this.state.onSelectValidation(node) === false) {
                        return;
                    }

                    if (node.isSelectable === false) {
                        return;
                    }

                    let actionClass = findParentNode(e.target, '.UITree__actions');
                    if (!actionClass) {
                        // If click is not from an action, close tooltips as we have will stopPropagation.
                        stopClickPropagation(e);
                    } else {
                        // If click is from an action, always select the current node.
                        if (this.state.selectedIds.includes(node.id) === true) {
                            return;
                        }
                    }

                    if (findParentNode(e.target, `.${this.prefix('expander')}`)) {
                        // If the expander icon is clicked then do not change selection.
                        return;
                    }

                    let currNode = document.querySelector('[nodeid="' + node.id + '"]');
                    if (!currNode || currNode.matches('.not-selectable') === true) {
                        // This node is not selectable.
                        return;
                    }

                    let currSelection = [];
                    let exists        = this.state.selectedIds.indexOf(node.id);

                    if (this.state.isMultipleSelect) {
                        if (exists < 0) {
                            currSelection = [node.id];

                            if (e.metaKey === true || e.ctrlKey === true) {
                                currSelection = currSelection.concat(this.state.selectedIds);
                            } else if (e.shiftKey === true && this.state.selectedIds.length > 0) {
                                // Need to determine position of the previous selected node to current selection.
                                let lastSelid   = this.state.selectedIds[this.state.selectedIds.length - 1];
                                let lastSelNode = document.querySelector('[nodeid="' + lastSelid + '"]');
                                let docPosition = lastSelNode.compareDocumentPosition(currNode);

                                let startNode    = null;
                                let endNode      = null;
                                let rootNode     = findParentNode(currNode, '.UITree');

                                if ((docPosition & Node.DOCUMENT_POSITION_FOLLOWING)) {
                                    startNode = lastSelNode;
                                    endNode   = currNode;
                                } else if ((docPosition & Node.DOCUMENT_POSITION_PRECEDING)) {
                                    startNode = currNode;
                                    endNode   = lastSelNode;
                                }

                                let treeWalker = document.createTreeWalker(
                                    rootNode,
                                    NodeFilter.SHOW_ELEMENT
                                );

                                let accept   = false;
                                let treeNode = null;
                                while (treeNode = treeWalker.nextNode()) {
                                    if (startNode === treeNode) {
                                        accept = true;
                                    }

                                    if (accept === true && treeNode.matches('.UITree__item') === true) {
                                        currSelection.push(treeNode.getAttribute('nodeid'));

                                        if (treeNode === endNode) {
                                            break;
                                        }
                                    }
                                }
                            }
                        } else if (e.metaKey === true || e.ctrlKey === true || e.shiftKey === true || this.state.selectedIds.length === 1) {
                            currSelection = clone(this.state.selectedIds);
                            currSelection.splice(exists, 1);
                        } else {
                            currSelection = [node.id];
                        }
                    } else {
                        currSelection = [node.id];
                    }

                    this.setState({
                        selectedIds: currSelection
                    });

                    if (this.state.onSelectionChange) {
                        this.state.onSelectionChange(this.state.selectedIds);
                    }
                }
            }, itemContent);
        });

    }

    render() {
        if (this.state.nodes && this.state.nodes.length > 0) {
            let content = [];

            let parentList = DOM.ul({
                classes: [this.prefix()],
                ref: elem => {
                    this._ul = elem;
                }
            }, this.renderTree(this.state.nodes, null));
            content.push(parentList);

            return content;
        } else {
            if (this.state.showEmptyText === true) {
                return DOM.div({
                    classes: this.prefix('no-items')
                }, [this.state.emptyText || _('No empty text defined')]);
            }

            return '';
        }
    }
});