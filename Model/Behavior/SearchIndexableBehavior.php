<?php
/**
 * SearchIndex
 *
 * Captures data on save into a string index (in own table)
 * Setups searchability on that string index
 */
class SearchIndexableBehavior extends ModelBehavior {

	/**
	 * default settings
	 *
	 * @var array
	 */
	public $__defaultSettings = array(
		'foreignKey' => false, // primaryKey to save against
		'_index' => false, // string to store as data
		'rebuildOnUpdate' => true, // do we want to update the record? (yeah!)
		'queryAfterSave' => true, // slower, but less likely to corrupt search records
		'fields' => '*',
		'stopwords_lang' => 'german',
	);

	/**
	 * placeholder for settings
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * placeholder for the array of stopwords which will be excluded from search inputs
	 * configure your own with Configure::write("SearchIndex.stopwords.$stopwords_lang", array(...))
	 *
	 * @var array
	 */
	public $stopwords = array();

	/**
	 * placeholder for the SearchIndex Model (object)
	 *
	 * @var object
	 */
	public $SearchIndex = null;

	/**
	 * Setup the model
	 *
	 * @param object Model $Model
	 * @param array $settings
	 * @return boolean
	 */
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->__defaultSettings, $config);
		Configure::load('SearchIndex.stopwords');
		$stopwords = Configure::read('SearchIndex.stopwords');
		$stopwords_lang = $this->settings[$Model->name]['stopwords_lang'];
		if (isset($stopwords[$stopwords_lang]) && is_array($stopwords[$stopwords_lang])) {
			$this->stopwords = $stopwords[$stopwords_lang];
			$this->prepareStopwords();
		}
	}

	/**
	 * process the stopwords array (already loaded into the property)
	 * sets the key to be the md5() hash of the word
	 *
	 * @return void
	 */
	private function prepareStopwords() {
		$stopwords = array();
		foreach ($this->stopwords as $word) {
			$stopwords[md5($word)] = $word;
		}
		$this->stopwords = $stopwords;
	}

	/**
	 * Standard afterSave() callback
	 * Collected data to save for a record
	 * - association_key = Model->id
	 * - data = _getIndexData() Model->data
	 * Saves index data for a record
	 *
	 * @param Model $Model
	 * @param boolean $created
	 * @param array $options
	 * @return boolean (always returns true)
	 */
	public function afterSave(Model $Model, $created, $options = array()) {
		// get data to save
		$association_key = $Model->id;
		if (empty($association_key)) {
			$association_key = $Model->getLastInsertID();
		}
		if (empty($association_key)) {
			return true;
		}
		if (!empty($this->settings[$Model->alias]['queryAfterSave'])) {
			$data = $this->_getIndexData($Model, $Model->read(null, $association_key));
		} else {
			$data = $this->_getIndexData($Model);
		}
		if (empty($data)) {
			return true;
		}
		$model = $Model->alias;
		// setup SearchIndex Model
		if (empty($this->SearchIndex)) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
		}
		// look for an existing record to update
		$id = $this->SearchIndex->field('id', compact('model', 'association_key'));
		if (empty($id)) {
			unset($id);
		}
		// setup data to save
		$save = array('SearchIndex' => compact('id', 'model', 'association_key', 'data'));
		// save
		$this->SearchIndex->create(false);
		$saved = $this->SearchIndex->save($save, array('validate' => false, 'callbacks' => false));
		// clear RAM
		unset($save, $id, $model, $data, $association_key);
		return true;
	}

	/**
	 * After a record is deleted, also remove it's SearchIndex row
	 *
	 * @param Model $Model
	 * @return boolean
	 */
	public function afterDelete(Model $Model) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
		}
		$conditions = array('model'=>$Model->alias, 'association_key'=>$Model->id);
		$this->SearchIndex->deleteAll($conditions);
	}

	/**
	 * Process the input data for this Model->data --> SearchIndex->data
	 * gets the index string to store as data for this record
	 *
	 * @param Model $Model
	 * @param string $data (optionally pass in data directly)
	 * @return string $index
	 */
	private function _getIndexData(Model $Model, $data = array()) {
		$backupData = false;
		if (!empty($data)) {
			$backupData = $Model->data;
			$Model->data = $data;
		}
		if (method_exists($Model, 'indexData')) {
			return $Model->indexData();
		} else {
			return $this->__getIndexData($Model);
		}
		if (!empty($backupData)) {
			$Model->data = $backupData;
		}
	}

	/**
	 * get the data to save for the index for this record,
	 *   for all text fields we can find on the data
	 *
	 * @param Model $Model
	 * @return string $index
	 */
	private function __getIndexData(Model $Model) {
		$index = array();
		$data = $Model->data[$Model->alias];

		if ($this->settings[$Model->name]['fields'] === '*') {
			$this->settings[$Model->name]['fields'] = array();
		}
		if (is_string($this->settings[$Model->name]['fields'])) {
			$this->settings[$Model->name]['fields'] = explode(',', $this->settings[$Model->name]['fields']);
		}

		foreach ($data as $key => $value) {
			if (!is_string($value)) {
				continue;
			}
			if (!is_array($this->settings[$Model->name]['fields'])) {
				continue;
			}
			if (!empty($this->settings[$Model->name]['fields']) && !in_array($key, $this->settings[$Model->name]['fields'])) {
				continue;
			}
			$columns = $Model->getColumnTypes();
			if ($key == $Model->primaryKey) {
				continue;
			}
			if (isset($columns[$key]) && in_array($columns[$key],array('text','varchar','char','string'))) {
				$index[] = strip_tags(html_entity_decode($value,ENT_COMPAT,'UTF-8'));
			}
		}

		$index = join(' . ', $index);
		$index = iconv('UTF-8', 'ASCII//TRANSLIT', $index);
		$index = preg_replace('/[\ ]+/',' ',$index);
		$index = $this->__removeStopwords($index);
		return $index;
	}

	/**
	 * Remove known "stop words" from the indexed data.
	 *  MySQL Stop Words (wont be included in the full text index)
	 *  http://dev.mysql.com/doc/refman/5.1/en/fulltext-stopwords.html
	 *
	 * @param string $index
	 * @return string $index
	 */
	private function __removeStopwords($index) {
		$words = explode(' ', $index);
		foreach (array_keys($words) as $i) {
			if (array_key_exists(md5($words[$i]), $this->stopwords)) {
				unset($words[$i]);
			}
		}
		return implode(' ', $words);
	}

	/**
	 * Perform a search on the SearchIndex table for a Model + term
	 *
	 * @param Model $Model
	 * @param string $q query, term
	 * @param array $findOptions
	 * @return array $find on SearchIndex records
	 */
	public function search(Model $Model, $q, $findOptions = array()) {
		if (!$this->SearchIndex) {
			$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
		}
		$this->SearchIndex->searchModels($Model->name);
		if (!isset($findOptions['conditions'])) {
			$findOptions['conditions'] = array();
		}
		App::uses('Sanitize', 'Utility');
		$q = Sanitize::escape($q);
		$q = $this->__removeStopwords($q);
		$findOptions['conditions'] = array_merge(
			$findOptions['conditions'], array("MATCH(SearchIndex.data) AGAINST('$q' IN BOOLEAN MODE)")
		);
		return $this->SearchIndex->find('all', $findOptions);
	}

	/**
	 * A simple retrieval of the stopwords being used, just in case they are needed
	 *
	 * @return array $stopwords
	 */
	public function stopwords() {
		return $this->stopwords;
	}
}
