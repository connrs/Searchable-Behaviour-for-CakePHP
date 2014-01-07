<?php
/**
 * SearchIndex
 *
 * Model where data is stored for the search index
 */
class SearchIndex extends SearchIndexAppModel {

	/**
	 * name of the table used
	 */
	public $useTable = 'search_indices';

	/**
	 * placeholder for models tracked/used
	 */
	private $models = array();

	/**
	 * custom auto-bind function
	 */
	private function bindTo($model) {
		$this->bindModel(
			array(
				'belongsTo' => array(
					$model => array (
						'className' => $model,
						'conditions' => 'SearchIndex.model = \''.$model.'\'',
						'foreignKey' => 'association_key'
					)
				)
			),false
		);
	}

	/**
	 * beforeFind callback
	 * correct search with conditions
	 *
	 * @param mixed $queryData
	 * @return mixed $queryData
	 */
	public function beforeFind($queryData) {
		$models_condition = false;
		if (!empty($this->models)) {
			$models_condition = array();
			foreach ($this->models as $model) {
				$Model = ClassRegistry::init($model);
				$models_condition[] = $model . '.'.$Model->primaryKey.' IS NOT NULL';
			}
		}
		if (isset($queryData['conditions'])) {
			if ($models_condition) {
				if (is_string($queryData['conditions'])) {
					$queryData['conditions'] .= ' AND (' . join(' OR ',$models_condition) . ')';
				} else {
					$queryData['conditions'][] = array('OR' => $models_condition);
				}
			}
		} else {
			if ($models_condition) {
				$queryData['conditions'][] = array('OR' => $models_condition);
			}
		}
		return $queryData;
	}

	/**
	 * afterFind callback
	 * cleanup results data with the SearchIndex children
	 *
	 * @param mixed $results array or false
	 * @param boolean $primary
	 * @return mixed $results
	 */
	public function afterFind($results, $primary = false) {
		if ($primary) {
			foreach ($results as $x => $result) {
				if (!empty($result['SearchIndex']['model'])) {
					$Model = ClassRegistry::init($result['SearchIndex']['model']);
					$results[$x]['SearchIndex']['displayField'] = $Model->displayField;
				}
			}
		}
		return $results;
	}

	/**
	 * do a simple search/bindTo on multiple models
	 *
	 * @param array $models
	 * @return void;
	 */
	public function searchModels($models = array()) {
		if (is_string($models)) {
			$models = array($models);
		}
		$this->models = $models;
		foreach ($models as $model) {
			$this->bindTo($model);
		}
	}

	/**
	 * clean a query string
	 *
	 * @param string $query
	 * @return string $query
	 */
	public function fuzzyize($query) {
		$query = preg_replace('/\s+/', '\s*', $query);
		return $query;
	}
}
