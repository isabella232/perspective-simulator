import './Core/UI.js';
import './Widgets/Button/ButtonWidget.js';
import './Widgets/Tree/TreeWidget.js';
import './Widgets/Content/ContentWidget.js';

const fileTree    = document.querySelector('#fileTree');
const fileContent = document.querySelector('#fileContent');
const href        = window.location.href.replace(/\/$/, '');
let treeNodes = {};

fileTree.setState({
    onSelectionChange: (id) => {
        // Splash Screens.
        let idParts = id[0].split('/');

        let type = 'none';
        let title = 'None';
        if (idParts.length > 2) {
            type  = idParts[2].replace(/\s/g, '').toLowerCase();
            title = idParts[2];
        }

        let content = '<div class="splash splash--'+type+'"><div class="splash-icon"></div><h2 class="splash-title">'+title+'</h2></div>';

        fileContent.setState({
            content
        });

        // let filePath = `${href}/content/${id[0].replace(/^\/|\/$/, '')}`;
        // if (/\.([a-z]{2,4})$/.test(filePath) !== true) {
        //     filePath = `${filePath}/index.html`;
        // }

        // fetch(filePath, {
        //     method:      'GET',
        //     headers:     {
        //         'Content-Type':     'text/html',
        //         'X-Requested-With': 'XMLHttpRequest'
        //     },
        //     credentials: 'same-origin'
        // }).then(response => {
        //     return response.text();
        // }).then(content => {
        //     fileContent.setState({
        //         content
        //     });
        // }).catch(e => {
        //     console.error(`File path ${filePath} may not exist. Check error: ${e}`);
        // });
    }
});

fetch(`${href}/files.php`, {
    method:      'GET',
    headers:     {
        'Content-Type':     'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
}).then(response => {
    return response.json();
}).then(nodes => {
    treeNodes = nodes.map(node => {
        node.isExpanded = false;

        node.childCount = nodes.reduce((acc, other) => {
            if (other.parentid === node.id) {
                acc += 1;
            }
            return acc;
        }, 0);

        if (node.id === '/') {
            node.isExpanded = true;
        }

        node.isSelectable = true;

        return node;
    });

    fileTree.setState({nodes: treeNodes});
});