define(function () {

  // Hooking into aloha settings.

  var plugins = Aloha.settings.plugins.load.split(',');
  plugins.push('neosAloha/entity-repository');
  Aloha.settings.plugins.load = plugins.join(',');

  Aloha.settings.requireConfig.paths['entity-repository'] = requirePaths['ByTorsten.Neos.EntityLink'] + 'InlineEditing/Editors/Aloha/LinkPlugin/lib';

  Aloha.settings.contentHandler.sanitize.protocols.a.href.push('entity');

  var neosPluginReplacement = requirePaths['ByTorsten.Neos.EntityLink'] + 'InlineEditing/Editors/Aloha/LinkPlugin/lib/neos-link-plugin.js';
  Aloha.settings.requireConfig.map['*']['neos-link/neos-link-plugin'] = neosPluginReplacement;
  Aloha.settings.requireConfig.map[neosPluginReplacement] = {
    'original-neos-link-plugin': 'neos-link/neos-link-plugin'
  };
});