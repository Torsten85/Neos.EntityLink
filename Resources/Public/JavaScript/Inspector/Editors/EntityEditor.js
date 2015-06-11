define(
  [
    'Library/jquery-with-dependencies',
    'emberjs',
    'Shared/HttpRestClient',
    'Shared/NodeTypeService',
    'Shared/I18n',
    'Shared/Utility'
  ],
  function($, Ember, HttpRestClient, NodeTypeService, I18n, Utility) {
    return Ember.View.extend({
      tagName: 'input',
      attributeBindings: ['type'],
      type: 'hidden',
      placeholder: function() {
        return I18n.translate('Main:TYPO3.Neos:typeToSearch.typeToSearch', 'Type to search');
      }.property(),

      content: null,

      entityClassName: null,
      searchProperties: null,
      labelProperty: null,
      icon: null,

      // Minimum amount of characters to trigger search
      threshold: 2,

      didInsertElement: function() {

        var that = this,
          icon = that.get('icon'),
          currentQueryTimer = null;

        this.$().select2({
          multiple: true,
          maximumSelectionSize: 1,
          minimumInputLength: that.get('threshold'),
          placeholder: this.get('placeholder'),
          formatResult: function(item, container, query, escapeMarkup) {
            var markup = [];
            Utility.Select2.util.markMatch(item.text, query.term, markup, escapeMarkup);
            var $itemContent = $('<span>' + markup.join('') + '</span>');

            if (icon) {
              $itemContent.prepend('<i class="' + icon + '"></i>');
            }

            return $itemContent.get(0).outerHTML;
          },
          formatSelection: function(item) {
            var $itemContent = $('<span>' + item.text + '</span>');

            if (icon) {
              $itemContent.prepend('<i class="' + icon + '"></i>');
            }

            return $itemContent.get(0).outerHTML;
          },
          query: function(query) {
            if (currentQueryTimer) {
              window.clearTimeout(currentQueryTimer);
            }
            currentQueryTimer = window.setTimeout(function() {
              currentQueryTimer = null;

              var parameters = {
                searchTerm: query.term,
                entityClassName: that.get('entityClassName'),
                searchProperties: that.get('searchProperties'),
                labelProperty: that.get('labelProperty')
              };

              HttpRestClient.getResource('bytorsten-service-editor', null, {data: parameters}).then(function(result) {

                var data = {results: []};
                $(result.resource).find('li').each(function(index, value) {
                  var identifier = $('.entity-identifier', value).text();
                  data.results.push({
                    id: identifier,
                    text: $('.entity-label', value).text().trim(),
                    data: {identifier: identifier, type: that.get('entityClassName')}
                  });
                });
                query.callback(data);
              });
            }, 200);
          }
        });

        this.$().select2('container').find('.neos-select2-input').attr('placeholder', this.get('placeholder'));
        if (this.get('content')) {
          this.$().select2('container').find('.neos-select2-input').css({'display': 'none'});
        } else {
          this.$().select2('container').find('.neos-select2-input').css({'display': 'inline-block'});
        }

        this._updateSelect2();

        this.$().on('change', function() {
          var data = $(this).select2('data');
          if (data.length > 0) {
            that.set('content', data[0]);
            that.$().select2('container').find('.neos-select2-input').css({'display': 'none'});
          } else {
            that.set('content', '');
            that.$().select2('container').find('.neos-select2-input').css({'display': 'inline-block'});
          }
        });
      },

      // actual value used and expected by the inspector, in case of this Editor a string (node identifier):
      value: function(key, value) {
        var that = this;

        if (value && value !== this.get('content.id')) {
          var item = Ember.Object.extend({
            id: value,
            text: function() {
              return I18n.translate('Main:TYPO3.Neos:loading', 'Loading ...');
            }.property()
          }).create();
          that.set('content', item);

          var parameters = {
            entityClassName: that.get('entityClassName'),
            labelProperty: that.get('labelProperty') || that.get('searchProperties')[0]
          };
          HttpRestClient.getResource('bytorsten-service-editor', value, {data: parameters}).then(function(result) {
            item.set('text', $('.entity-label', result.resource).text().trim());
            item.set('data', {
              identifier: $('.entity-identifier', result.resource).text(),
              type: parameters.entityClassName
            });
            that._updateSelect2();
          });

          that._updateSelect2();
        }
        return this.get('content.id') || '';
      }.property('content', 'content.id'),

      _updateSelect2: function() {
        if (!this.$()) {
          return;
        }
        if (this.get('content')) {
          this.$().select2('data', [this.get('content')]);
        } else {
          this.$().select2('data', []);
        }
      }
    });
  }
);
