<?php
class SearchableBehavior extends ModelBehavior {
	var $settings = array();
	var $model = null;
	
	var $_index = false;
	var $foreignKey = false;
	var $_defaults = array(
		'rebuildOnUpdate' => true
	);
	
	var $SearchIndex = null;

	function setup(&$model, $settings = array()) {
		$settings = array_merge($this->_defaults, $settings);	
		$this->settings[$model->name] = $settings;
		$this->model = &$model;
	}
	
	function _indexData() {
		if (method_exists($this->model, 'indexData')) {
			return $this->model->indexData();
		} else {
			return $this->_index();
		}
	}
	
	function beforeSave(&$model) {
		$this->model =& $model;
		if ($this->model->id) {
			$this->foreignKey = $this->model->id;
		} else {
			$this->foreignKey = 0;
		}
		if ($this->foreignKey == 0 || $this->settings[$this->model->name]['rebuildOnUpdate']) {
			$this->_index = $this->_indexData();
		}
		return true;
	}
	
	function afterSave() {
		if ($this->_index !== false) {
			if (!$this->SearchIndex) {
				$this->SearchIndex = ClassRegistry::init('SearchIndex');
			}
			if ($this->foreignKey == 0) {
				$this->foreignKey = $this->model->getLastInsertID();
				$this->SearchIndex->save(
					array(
						'SearchIndex' => array(
							'model' => $this->model->name,
							'association_key' => $this->foreignKey,
							'data' => $this->_index
						)
					)
				);
			} else {
				$searchEntry = $this->SearchIndex->find('first',array('fields'=>array('id'),'conditions'=>array('model'=>$this->model->name,'association_key'=>$this->foreignKey)));
				$this->SearchIndex->save(
					array(
						'SearchIndex' => array(
							'id' => empty($searchEntry) ? 0 : $searchEntry['SearchIndex']['id'],
							'model' => $this->model->name,
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
	
	function _index() {
		$index = array();
		$data = $this->model->data[$this->model->name];
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$columns = $this->model->getColumnTypes();
				if ($key != $this->model->primaryKey && isset($columns[$key]) && in_array($columns[$key],array('text','varchar','char','string'))) {
					$index []= strip_tags(html_entity_decode($value,ENT_COMPAT,'UTF-8'));
				}
			}
		}
		$index = join('. ',$index);
		$index = iconv('UTF-8', 'ASCII//TRANSLIT', $index);
		$index = preg_replace('/[\ ]+/',' ',$index);
		return $index;
	}

	function afterDelete(&$model) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex');
		}
		$conditions = array('model'=>$model->alias, 'association_key'=>$model->id);
		$this->SearchIndex->deleteAll($conditions);
	}
	
	
	function search(&$model, $q, $findOptions = array()) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex');
		}
		$this->SearchIndex->searchModels($model->name);
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
