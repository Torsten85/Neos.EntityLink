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
			placeholder: function () {
				return I18n.translate('Main:TYPO3.Neos:typeToSearch', 'Type to search');
			}.property(),

			// lazily initialized content – will be an array of select2 datums [{id: '12a9837…', text: 'My Node'}, …]:
			content: [],

      entityClassName: null,
      searchProperties: null,
      labelProperty: null,
      icon: null,

			// Minimum amount of characters to trigger search
			threshold: 2,

			didInsertElement: function() {
				var that = this,
            icon = that.get('icon');

				var currentQueryTimer = null;
				this.$().select2({
					multiple: true,
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

				this.$().on('change', function() {
					that.set('content', $(this).select2('data'));
				});
			},

			_updateSelect2: function() {
				if (!this.$()) {
					return;
				}
				this.$().select2('data', this.get('content'));
			},

			// actual value used and expected by the inspector:
			value: function(key, value) {
				var that = this,
					currentValue = JSON.stringify(this.get('content').map(function(item) {
						return item.id;
					}));

				if (value && value !== currentValue) {
					// Remove all items so they don't appear multiple times.
					// TODO: cache already found items and load multiple node records at once
					that.set('content', []);
					// load names of already selected nodes via the Node REST service:
					$(JSON.parse(value)).each(function(index, entityIdentifier) {
						var item = Ember.Object.extend({
							id: entityIdentifier,
							text: function() {
								return I18n.translate('Main:TYPO3.Neos:loading', 'Loading ...');
							}.property()
						}).create();

						that.get('content').pushObject(item);

            var parameters = {
              entityClassName: that.get('entityClassName'),
              labelProperty: that.get('labelProperty') || that.get('searchProperties')[0]
            };

						HttpRestClient.getResource('bytorsten-service-editor', entityIdentifier, {data: parameters}).then(function(result) {
							item.set('text', $('.entity-label', result.resource).text().trim());
							item.set('data', {
                identifier: $('.entity-identifier', result.resource).text(),
                type: that.get('entityClassName')
              });
							that._updateSelect2();
						});

					});
					that._updateSelect2();
				}
				return currentValue;
			}.property('content.@each')
		});
	}
);
