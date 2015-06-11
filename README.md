# Neos.EntityLink
Easy linking directly to entities from inline editing or inspector editors

## Inline editing

Settings.yaml:
```yaml
ByTorsten:
  Neos:
    EntityLink:
      entities:
        news:
          className: 'Acme\Demo\Domain\Model\News'
          searchProperties: ['title', 'teaser']
          labelProperty: 'title' # Optional, if not provided the first searchProperty is used
          icon: 'icon-rss-sign' # Optional
          plugin:
            name: 'Acme.Demo:News'
            identifier: '81951f86-17b1-e9a6-b2cc-dcfebf9b297e' #Optional, if not provided first page node of plugin type is used
            controllerActions:
              'Acme\Demo\Controller\StandardController': ['show']
        
```

## Entity editor

NodeTypes.yaml:
```yaml
'Acme.Demo:Teaser':
  ...
  properties:
  	item:
	  type: 'string'
      ui:
        inspector:
          editor: 'ByTorsten.Neos.EntityLink/Inspector/Editors/EntityEditor' # ... or EntitiesEditor
          editorOptions:
            icon: 'icon-gift' #optional
            entityClassName: 'Acme\Demo\Domain\Model\Item'
            searchProperties: ['title', 'description']
            labelProperty: 'title' #optional, falls back to first searchProperty        
```
