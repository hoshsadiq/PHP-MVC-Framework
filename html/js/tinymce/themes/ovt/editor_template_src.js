(function () {
    var DOM = tinymce.DOM;

    // Tell it to load theme specific language pack(s)
    tinymce.ThemeManager.requireLangPack('ovt');

    tinymce.create('tinymce.themes.ovt', {
        init: function (ed, url) {
            var t = this;

            t.editor = ed;
            ed.contentCSS.push(url + '/content.css');
            DOM.loadCSS(url + '/ui.css');
        },

        renderUI: function (o) {
            var t = this, n = o.targetNode, ic, tb, ed = t.editor, cf = ed.controlManager;

            n = DOM.insertAfter(DOM.create('div', {id: ed.id + 'mce', 'class': 'mceEditor'}), n);

            return {
                iframeContainer: n,
                editorContainer: ed.id + 'mce',
                deltaHeight: -20
            };
        }
    });

    tinymce.ThemeManager.add('ovt', tinymce.themes.ovt);
})();