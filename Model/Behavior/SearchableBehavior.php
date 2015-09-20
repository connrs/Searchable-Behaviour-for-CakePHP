<?php
class SearchableBehavior extends ModelBehavior {
    public $__defaultSettings = array(
        'foreignKey' => false,
        '_index' => false,
        'rebuildOnUpdate' => true,
        'fields' => '*',
        'stopwords_lang' => 'german'
    );
    public $settings = array();
    public $stopwords = array();
    public $SearchIndex;
    public $model;

    public function setup(Model $Model, $config = array()) {
        $this->settings[$Model->name] = array_merge($this->__defaultSettings, $config);
        $this->model =& $Model;

        Configure::load('Searchable.stopwords');
        $stopwords = Configure::read('Searchable.stopwords');
        $stopwords_lang = $this->settings[$Model->name]['stopwords_lang'];
        if (isset($stopwords[$stopwords_lang]) && is_array($stopwords[$stopwords_lang])) {
            $this->stopwords = $stopwords[$stopwords_lang];
            $this->prepareStopwords();
        }
    }

    private function prepareStopwords() {
        $stopwords = array();
        foreach ($this->stopwords as $word) {
            $stopwords[md5($word)] = $word;
        }
        $this->stopwords = $stopwords;
    }
    
    private function processData(Model $Model) {
        if (method_exists($Model, 'indexData')) {
            return $Model->indexData();
        } else {
            return $this->index($Model);
        }
    }
    
    public function beforeSave(Model $Model, $options = array()) {
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
    
    public function afterSave(Model $Model, $created, $options = array()) {
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
    
    private function index(Model $Model) {
        $index = array();
        $data = $Model->data[$Model->alias];

        if ($this->settings[$Model->name]['fields'] === '*') {
            $this->settings[$Model->name]['fields'] = array();
        }

        if (is_string($this->settings[$Model->name]['fields'])) {
            $this->settings[$Model->name]['fields'] = array($this->settings[$Model->name]['fields']);
        }

        foreach ($data as $key => $value) {
            if ((is_array($this->settings[$Model->name]['fields']) && count($this->settings[$Model->name]['fields']) < 1) || (is_array($this->settings[$Model->name]['fields']) && in_array($key, $this->settings[$Model->name]['fields']))) {
                if (is_string($value)) {
                    $columns = $Model->getColumnTypes();
                    if ($key != $Model->primaryKey && isset($columns[$key]) && in_array($columns[$key],array('text','varchar','char','string'))) {
                        $index []= strip_tags(html_entity_decode($value,ENT_COMPAT,'UTF-8'));
                    }
                }
            }
        }

        $index = join('. ',$index);
        $index = iconv('UTF-8', 'ASCII//TRANSLIT', $index);
        $index = preg_replace('/[\ ]+/',' ',$index);
        $index = $this->removeStopwords($index);
        return $index;
    }

    private function removeStopwords($index) {
        $words = explode(' ', $index);
        foreach ($words as $word) {
            if (isset($this->stopwords[md5($word)])) {
                $search = ' ' . $word . ' ';
                $index = str_replace($search, ' ', $index);
            }
        }

        return $index;
    }

    public function afterDelete(Model $Model) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex', true);
        }
        $conditions = array('model'=>$Model->alias, 'association_key'=>$Model->id);
        $this->SearchIndex->deleteAll($conditions);
    }

    public function search(Model $Model, $q, $findOptions = array()) {
        if (!$this->SearchIndex) {
            $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex', true);
        }
        $this->SearchIndex->searchModels($Model->name);
        if (!isset($findOptions['conditions'])) {
            $findOptions['conditions'] = array();
        }
        App::uses('Sanitize', 'Utility');
        $q = Sanitize::escape($q, $Model->useDbConfig);
        $findOptions['conditions'] = array_merge(
            $findOptions['conditions'], array("MATCH(SearchIndex.data) AGAINST('$q' IN BOOLEAN MODE)")
        );
        return $this->SearchIndex->find('all', $findOptions);       
    }
    
}
