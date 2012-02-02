<?php
class SearchableBehavior extends ModelBehavior {
    public $__defaultSettings = array(
        'foreignKey' => false,
        '_index' => false,
        'rebuildOnUpdate' => true
    );
    public $settings = array();
    public $SearchIndex;

    function setup(Model $Model, $settings = array()) {
        $this->settings[$Model->alias] = $settings + $this->__defaultSettings;
    }
    
    function processData(Model $Model) {
        if (method_exists($Model, 'indexData')) {
            return $Model->indexData();
        } else {
            return $this->index($Model);
        }
    }
    
    function beforeSave(Model $Model) {
        if ($Model->id) {
            $this->settings[$Model->alias]['foreignKey'] = $Model->id;
        } else {
            $this->settings[$Model->alias]['foreignKey'] = 0;
        }
        if ($this->settings[$Model->alias]['foreignKey'] == 0 || $this->settings[$Model->alias]['rebuildOnUpdate']) {
            $this->settings[$Model->alias]['_index'] = $this->processData($Model);
        }
        return true;
    }
    
    function afterSave(Model $Model) {
        if ($this->settings[$Model->alias]['_index'] !== false) {
            if (!$this->SearchIndex) {
                $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex', true);
            }
            if ($this->settings[$Model->alias]['foreignKey'] == 0) {
                $this->settings[$Model->alias]['foreignKey'] = $Model->getLastInsertID();
                $this->SearchIndex->save(
                    array(
                        'SearchIndex' => array(
                            'model' => $Model->alias,
                            'association_key' => $this->settings[$Model->alias]['foreignKey'],
                            'data' => $this->settings[$Model->alias]['_index']
                        )
                    )
                );
            } else {
                $searchEntry = $this->SearchIndex->find('first',array(
                    'conditions' => array(
                        'model' => $Model->alias,
                        'association_key'=>$this->settings[$Model->alias]['foreignKey']
                    )
                ));
                $this->SearchIndex->save(
                    array(
                        'SearchIndex' => array(
                            'id' => empty($searchEntry) ? 0 : $searchEntry['SearchIndex']['id'],
                            'model' => $Model->alias,
                            'association_key' => $this->settings[$Model->alias]['foreignKey'],
                            'data' => $this->settings[$Model->alias]['_index']
                        )
                    )
                );              
            }
            $this->settings[$Model->alias]['_index'] = false;
            $this->settings[$Model->alias]['foreignKey'] = false;
        }
        return true;
    }
    
    function index(Model $Model) {
        $index = array();
        $data = $Model->data[$Model->alias];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $columns = $Model->getColumnTypes();
                if ($key != $Model->primaryKey && isset($columns[$key]) && in_array($columns[$key],array('text','varchar','char','string'))) {
                    $index []= strip_tags(html_entity_decode($value,ENT_COMPAT,'UTF-8'));
                }
            }
        }
        $index = join('. ',$index);
        $index = iconv('UTF-8', 'ASCII//TRANSLIT', $index);
        $index = preg_replace('/[\ ]+/',' ',$index);
        return $index;
    }

    function afterDelete(Model $Model) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex', true);
        }
        $conditions = array('model'=>$Model->alias, 'association_key'=>$Model->id);
        $this->SearchIndex->deleteAll($conditions);
    }

    function search(Model $Model, $q, $findOptions = array()) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex', true);
        }
        $this->SearchIndex->searchModels($Model->name);
        if (!isset($findOptions['conditions'])) {
            $findOptions['conditions'] = array();
        }
        App::import('Core', 'Sanitize');
        $q = Sanitize::escape($q);
        $findOptions['conditions'] = array_merge(
            $findOptions['conditions'], array("MATCH(SearchIndex.data) AGAINST('$q' IN BOOLEAN MODE)")
        );
        return $this->SearchIndex->find('all', $findOptions);       
    }
    
}
