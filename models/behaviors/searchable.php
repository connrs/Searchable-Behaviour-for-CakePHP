<?php
class SearchableBehavior extends ModelBehavior {
	var $settings = array();
	var $model = null;
	
	var $_index = false;
	var $_indexForId = false;
	var $rebuildOnUpdate = false;
	
	var $SearchIndex = null;

	function setup(&$model, $settings = array()) {
		// no special setup required	
		$this->settings[$model->name] = $settings;
		$this->model = &$model;	
	}
	
	function indexData() {
		if (is_callable(array($this->model, 'indexData'))) {
			return $this->model->indexData();
		} else {
			return $this->_index();
		}
	}
	
	function beforeSave() {
		if (isset($this->model->data[$this->model->name]['id']) && $this->model->data[$this->model->name]['id']!=0) {
			$this->_indexForId = $this->model->data[$this->model->name]['id'];		
		} else {
			$this->_indexForId = 0;
		}
		if ($this->_indexForId == 0 || $this->rebuildOnUpdate) {
			$this->_index = $this->indexData();
		}
		return true;
	}
	
	function afterSave() {
		if ($this->_index !== false) {
			if (!$this->SearchIndex) {
				App::import('Model','SearchIndex');
				$this->SearchIndex = new SearchIndex();
			}
			if ($this->_indexForId == 0) {
				$this->_indexForId = $this->model->getLastInsertID();
				$this->SearchIndex->save(
					array(
						'SearchIndex' => array(
							'model' => $this->model->name,
							'association_key' => $this->_indexForId,
							'data' => $this->_index
						)
					)
				);
			} else {
				$this->SearchIndex->saveField('data',$this->_index);
			}
			$this->_index = false;
			$this->_indexForId = false;
		}
		return true;
	}
	
	function _index() {
		$index = '';
		$data = $this->model->data[$this->model->name];
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$columns = $this->model->getColumnTypes();
				if (isset($columns[$key]) && in_array($columns[$key],array('text','varchar','char'))) {
					$index = $index . ' ' . strip_tags(html_entity_decode($value,ENT_COMPAT,'UTF-8'));
				}
			}
		}		
		$index = iconv('UTF-8', 'ASCII//TRANSLIT', $index);
		$index = preg_replace('/[\ ]+/',' ',$index);
		return $index;
	}
	
	function search(&$model, $q, $findOptions = array()) {
		if (!$this->SearchIndex) {
			App::import('Model','SearchIndex');
			$this->SearchIndex = new SearchIndex();
		}
		$this->SearchIndex->searchModels($this->model->name);		
		if (!isset($findOptions['conditions'])) $findOptions['conditions'] = array();
		$findOptions['conditions'] = array_merge($findOptions['conditions'],array("MATCH(SearchIndex.data) AGAINST('$q' IN BOOLEAN MODE)"));
		return $this->SearchIndex->find('all',$findOptions);	
	}
	
}
?>
