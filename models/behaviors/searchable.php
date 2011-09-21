<?php
class SearchableBehavior extends ModelBehavior {
    var $foreignKey = false;
    var $_index = false;
    var $rebuildOnUpdate = true;
    var $SearchIndex = null;

    function setup(&$Model, $settings = array()) {
        $this->_set($settings);
    }
    
    function processData(&$Model) {
        if (method_exists($Model, 'indexData')) {
            return $Model->indexData();
        } else {
            return $this->index($Model);
        }
    }
    
    function beforeSave(&$Model) {
        if ($Model->id) {
            $this->foreignKey = $Model->id;
        } else {
            $this->foreignKey = 0;
        }
        if ($this->foreignKey == 0 || $this->rebuildOnUpdate) {
            $this->_index = $this->processData($Model);
        }
        return true;
    }
    
    function afterSave(&$Model) {
        if ($this->_index !== false) {
            if (!$this->SearchIndex) {
                $this->SearchIndex = ClassRegistry::init('SearchIndex');
            }
            if ($this->foreignKey == 0) {
                $this->foreignKey = $Model->getLastInsertID();
                $this->SearchIndex->save(
                    array(
                        'SearchIndex' => array(
                            'model' => $Model->alias,
                            'association_key' => $this->foreignKey,
                            'data' => $this->_index
                        )
                    )
                );
            } else {
                $searchEntry = $this->SearchIndex->find('first',array('fields'=>array('id'),'conditions'=>array('model'=>$Model->alias,'association_key'=>$this->foreignKey)));
                $this->SearchIndex->save(
                    array(
                        'SearchIndex' => array(
                            'id' => empty($searchEntry) ? 0 : $searchEntry['SearchIndex']['id'],
                            'model' => $Model->alias,
                            'association_key' => $this->foreignKey,
                            'data' => $this->_index
                        )
                    )
                );              
            }
            $this->_index = false;
            $this->foreignKey = false;
        }
        return true;
    }
    
    function index(&$Model) {
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

    function afterDelete(&$Model) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('SearchIndex');
        }
        $conditions = array('model'=>$Model->alias, 'association_key'=>$Model->id);
        $this->SearchIndex->deleteAll($conditions);
    }

    function search(&$Model, $q, $findOptions = array()) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('SearchIndex');
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
?>
