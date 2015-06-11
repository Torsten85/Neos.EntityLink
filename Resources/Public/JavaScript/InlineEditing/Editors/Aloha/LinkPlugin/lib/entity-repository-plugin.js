define( [
	'aloha',
	'jquery',
	'aloha/plugin',
	'entity-repository/../extra/entity-repository',
  ''
], function (Aloha, $, Plugin, EntityRepository) {
	/**
	 * Register the plugin with unique name
	 */
	return Plugin.create('entity-repository-plugin', {
		init: function () {
			new EntityRepository();
		},

		/**
		 * @return string
		 */
		toString: function () {
			return 'entity-repository-plugin';
		}
	});
});