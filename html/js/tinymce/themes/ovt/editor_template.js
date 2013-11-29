(function () {
    var a = tinymce, b = a.DOM;
    a.create('tinymce.themes.ovt', {init: function (c, d) {
        this.e = c;
        c.contentCSS.push(d + '/content.css');
        b.loadCSS(d + '/ui.css');
    }, renderUI: function (o) {
        var c = this.e;
        return{iframeContainer: b.insertAfter(b.create('div', {id: c.id + 'mce', 'class': 'mceEditor'}), o.targetNode), editorContainer: c.id + 'mce'};
    }});
    a.ThemeManager.add('ovt', a.themes.ovt);
})();