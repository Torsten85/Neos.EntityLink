define([
  'original-neos-link-plugin'

], function(NeosLinkPlugin) {

  var originalCreateButtons = NeosLinkPlugin.createButtons;

  NeosLinkPlugin.createButtons = function () {
    originalCreateButtons.apply(this, arguments);

    var blocked = false;
    var $input = this.hrefField.getInputJQuery();
    var originalOnSelect = $input.autocomplete('option').select;

    $input.autocomplete('option', 'select', function (event, ui) {

      var obj = ui.item.obj;
      var $source = $(event.srcElement);

      if ($source.is('button')) {
        console.log('button');
        var controller = $source.data('controller'),
            action = $source.data('action');

        controller = controller.replace(/\\/g,':');

        obj.id = obj.id
          .replace('{controller}', controller)
          .replace('{action}', action);

        obj.url = obj.url
          .replace('{controller}', controller)
          .replace('{action}', action);

      } else {

        obj.id = obj.id.replace('{controller}/{action}/', '');
        obj.url = obj.url.replace('{controller}/{action}/', '');

      }

      originalOnSelect.call(this, event, ui);
    });

  };

  return NeosLinkPlugin;
});