<?php
class SearchIndex extends SearchIndexAppModel {
	/**
	 * name of the table used
	 */
	public $useTable = 'search_index';

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

	public function searchModels($models = array()) {
		if (is_string($models)) $models = array($models);
		$this->models = $models;
		foreach ($models as $model) {
			$this->bindTo($model);
		}
	}

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

	public function afterFind($results, $primary) {
		if ($primary) {
			foreach($results as $x => $result) {
				if (!empty($result['SearchIndex']['model'])) {
					$Model = ClassRegistry::init($result['SearchIndex']['model']);
					$results[$x]['SearchIndex']['displayField'] = $Model->displayField;
				}
			}
		}
		return $results;
	}

	public function fuzzyize($query) {
		/*$query = preg_replace('/\s+/i', ' ', $query);
		$fuzzies = str_split($query);
		foreach ($fuzzies as $i => $fuzz) {
			$fuzzies[$i] = preg_quote($fuzz);
			if ($fuzz == ' ') {
				//$fuzzies[$i] = '[:blank:]*';
				$fuzzies[$i] = '\s*';
			} else {
				$fuzzies[$i] = $fuzz . '[a-zA-Z0-9]*';
			}
		}*/
		//$query = join('', $fuzzies);
		//$query = preg_replace('/\s+/i', '\s*', $query);
		$query = preg_replace('/\s+/', '\s*', $query);
		return $query;
	}
}
