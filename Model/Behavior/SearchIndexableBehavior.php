<?php
class SearchIndexableBehavior extends ModelBehavior {
	public $__defaultSettings = array(
		'foreignKey' => false,
		'_index' => false,
		'rebuildOnUpdate' => true
	);
	public $settings = array();
	public $SearchIndex;

	public function setup(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = $settings + $this->__defaultSettings;
	}

	public function processData(Model $Model) {
		if (method_exists($Model, 'indexData')) {
			return $Model->indexData();
		} else {
			return $this->index($Model);
		}
	}

	public function beforeSave(Model $Model) {
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

	public function afterSave(Model $Model) {
		if ($this->settings[$Model->alias]['_index'] !== false) {
			if (!$this->SearchIndex) {
				$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
			}
			if (empty($this->settings[$Model->alias]['foreignKey'])) {
				$this->settings[$Model->alias]['foreignKey'] = $Model->getLastInsertID();
			}
			// setup data to save
			$data = array(
				'SearchIndex' => array(
					'model' => $Model->alias,
					'association_key' => $this->settings[$Model->alias]['foreignKey'],
					'data' => $this->settings[$Model->alias]['_index']
				)
			);
			// look for an existing record to update
			$existingId = $this->SearchIndex->field('id', array(
				'model' => $Model->alias,
				'association_key'=>$this->settings[$Model->alias]['foreignKey']
			));
			if (!empty($existingId)) {
				$data['SearchIndex']['id'] = $existingId;
			}
			// save
			$this->SearchIndex->create(false);
			$saved = $this->SearchIndex->save($data, array('validate' => false, 'callbacks' => false));
			$this->settings[$Model->alias]['_index'] = false;
			$this->settings[$Model->alias]['foreignKey'] = false;
		}
		return true;
	}

	public function index(Model $Model) {
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

	public function afterDelete(Model $Model) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
		}
		$conditions = array('model'=>$Model->alias, 'association_key'=>$Model->id);
		$this->SearchIndex->deleteAll($conditions);
	}

	public function search(Model $Model, $q, $findOptions = array()) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
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
