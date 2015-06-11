/**
 * Create the Aloha Repositories object.
 */
define(
[
	'aloha',
	'jquery'
], function(
	Aloha,
	$
) {
	return Aloha.AbstractRepository.extend({
		_type: 'entity',
		_repositoryIdentifier: 'entity-repository',

    _controllerActions: null,

		_constructor: function() {
			this._super(this._repositoryIdentifier);
		},

		getQueryRequestData: function(searchTerm) {
			return {
				searchTerm: searchTerm
			};
		},

		getObjectQueryRequestData: function() {
			return {};
		},

    getControllerActions: function ($result) {
      if (!this._controllerActions) {
        var controllerActions = {};
        $result.find('ul.controller-actions > li').each(function () {
          var entity = $(this).find('span.action-entity').text();
          controllerActions[entity] = {};

          $(this).find('> ul > li').each(function () {
            var controller = $(this).find('span.action-controller').text();
            controllerActions[entity][controller] = {};

            $(this).find('> ul > li').each(function () {
              controllerActions[entity][controller][$(this).find('span.action-name').text()] = $(this).find('span.action-label').text();
            })
          });
        });

        this._controllerActions = controllerActions;
      }

      return this._controllerActions;
    },

		/**
		 * Searches a repository for repository items matching queryString if none found returns null.
		 * The returned repository items must be an array of Aloha.Repository.Object
		 *
		 * @param {object} params object with properties
		 * @param {function} callback this method must be called with all resulting repository items
		 * @return {void}
		 */
		query: function(params, callback) {
			var that = this;
			require({context: 'neos'}, ['Shared/HttpRestClient'], function(HttpRestClient) {

        var data = {
          searchTerm: params.queryString,
          includeControllerActions: that._controllerActions === null
        };

        HttpRestClient.getResource('bytorsten-service-inline', null, {data: data}).then(function (result) {
          var convertedResults = [];

          var $result = $(result.resource);
          var controllerActions = that.getControllerActions($result);

          $result.find('ul.entities > li').each(function () {
            var entityIdentifier = $('.entity-identifier', this).text(),
                type = $('.entity-type', this).text();

            var buttons = [];

            if (controllerActions[type]) {
              $.each(controllerActions[type], function (controller, actions) {
                $.each(actions, function (action, label) {
                  buttons.push('<button class="neos-button-small" data-controller="' + controller + '" data-action="' + action + '">' + label + '</button>');
                });
              });
            }

            convertedResults.push({
              'id': type + '/{controller}/{action}/' + entityIdentifier,
              '__icon': $('.entity-icon', this).html() || '',
              '__path': '<br />' + entityIdentifier + (buttons.length > 0 ? '<br />' + buttons.join(' ') : ''),
              '__thumbnail': '',
              'name': $('.entity-label', this).text(),
              'url': that._type + '://' + type + '/{controller}/{action}/' + entityIdentifier,
              'type': that._type,
              'repositoryId': that._repositoryIdentifier
            });
          });

          callback.call(this, convertedResults);
        });
      });
		},

		/**
		 * Get the repositoryItem with given id
		 * Callback: {Aloha.Repository.Object} item with given id
		 *
		 * @param {string} itemId  id of the repository item to fetch
		 * @param {function} callback callback function
		 * @return {void}
		 */
		getObjectById: function(itemId, callback) {
			var that = this;

			require({context: 'neos'}, ['Shared/HttpRestClient'], function(HttpRestClient) {
				HttpRestClient.getResource('bytorsten-service-inline', itemId, {data: that.getObjectQueryRequestData()}).then(function(result) {
					var $entity = $('.entity', result.resource),
						entityIdentifier = $('.entity-identifier', $entity).text(),
            type = $('.entity-type', $entity).text(),
						url = that._type + '://' + itemId;

					callback.call(this, [{
						'id': type + '/' + entityIdentifier,
						'name': $('.entity-label', $entity).text() + ' (' + url + ')',
						'url': url,
						'type': that._type,
						'repositoryId': that._repositoryIdentifier
					}]);
				});
			});
		}
	});
});
