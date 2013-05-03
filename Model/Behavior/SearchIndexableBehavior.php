<?php
class SearchIndexableBehavior extends ModelBehavior {
	public $__defaultSettings = array(
		'foreignKey' => false, // primaryKey to save against
		'savedData' => false, // array of what was passed into save()
		'_index' => false, // string to store as data
		'queryAfterSave' => true, // slower, but less likely to corrupt search records
		'rebuildOnUpdate' => true, // do we want to update the record? (yeah!)
	);
	public $settings = array();
	public $SearchIndex;

	public function setup(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = $settings + $this->__defaultSettings;
	}

	public function processData(Model $Model, $data = array()) {
		$backupData = false;
		if (!empty($data)) {
			$backupData = $Model->data;
			$Model->data = $data;
		}
		if (method_exists($Model, 'indexData')) {
			return $Model->indexData();
		} else {
			return $this->index($Model);
		}
		if (!empty($backupData)) {
			$Model->data = $backupData;
		}
	}

	public function beforeSave(Model $Model) {
		if ($Model->id) {
			$this->settings[$Model->alias]['foreignKey'] = $Model->id;
		} else {
			$this->settings[$Model->alias]['foreignKey'] = 0;
		}
		if ($this->settings[$Model->alias]['foreignKey'] == 0 || $this->settings[$Model->alias]['rebuildOnUpdate']) {
			$this->settings[$Model->alias]['savedData'] = $Model->data;
		}
		return true;
	}

	public function afterSave(Model $Model) {
		if (empty($this->settings[$Model->alias]['foreignKey'])) {
			$this->settings[$Model->alias]['foreignKey'] = $Model->getLastInsertID();
		}
		if (!empty($this->settings[$Model->alias]['queryAfterSave'])) {
			$data = $Model->read(null, $this->settings[$Model->alias]['foreignKey']);
			$this->settings[$Model->alias]['_index'] = $this->processData($Model, $data);
		} else {
			//$data = $this->settings[$Model->alias]['savedData'];
			$this->settings[$Model->alias]['_index'] = $this->processData($Model);
		}
		if ($this->settings[$Model->alias]['_index'] !== false) {
			if (!$this->SearchIndex) {
				$this->SearchIndex = ClassRegistry::init('SearchIndex.SearchIndex', true);
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
		}
		$this->settings[$Model->alias]['_index'] = false;
		$this->settings[$Model->alias]['foreignKey'] = false;
		$this->settings[$Model->alias]['savedData'] = false;
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

	/**
	 * MySQL Stop Words (wont be included in the full text index)
	 * TODO: exclude from indexed data?
	 * http://dev.mysql.com/doc/refman/5.1/en/fulltext-stopwords.html
	 * @return array $stopWords
	 */
	public function stopWords() {
		return array(
			'able',	'about',	'above',	'according', 'accordingly',	'across',	'actually',	'after',	'afterwards',
			'again',	'against',	'ain\'t',	'all',	'allow', 'allows',	'almost',	'alone',	'along',	'already',
			'also',	'although',	'always',	'am',	'among', 'amongst',	'an',	'and',	'another',	'any',
			'anybody',	'anyhow',	'anyone',	'anything',	'anyway', 'anyways',	'anywhere',	'apart',	'appear',	'appreciate',
			'appropriate',	'are',	'aren\'t',	'around',	'as', 'aside',	'ask',	'asking',	'associated',	'at',
			'available',	'away',	'awfully',	'be',	'became', 'because',	'become',	'becomes',	'becoming',	'been',
			'before',	'beforehand',	'behind',	'being',	'believe', 'below',	'beside',	'besides',	'best',	'better',
			'between',	'beyond',	'both',	'brief',	'but', 'by',	'c\'mon',	'ci\'s',	'came',	'can',
			'can\'t',	'cannot',	'cant',	'cause',	'causes', 'certain',	'certainly',	'changes',	'clearly',	'co',
			'com',	'come',	'comes',	'concerning',	'consequently', 'consider',	'considering',	'contain',	'containing',	'contains',
			'corresponding',	'could',	'couldn\'t',	'course',	'currently', 'definitely',	'described',	'despite',	'did',	'didn\'t',
			'different',	'do',	'does',	'doesn\'t',	'doing', 'don\'t',	'done',	'down',	'downwards',	'during',
			'each',	'edu',	'eg',	'eight',	'either', 'else',	'elsewhere',	'enough',	'entirely',	'especially',
			'et',	'etc',	'even',	'ever',	'every', 'everybody',	'everyone',	'everything',	'everywhere',	'ex',
			'exactly',	'example',	'except',	'far',	'few', 'fifth',	'first',	'five',	'followed',	'following',
			'follows',	'for',	'former',	'formerly',	'forth', 'four',	'from',	'further',	'furthermore',	'get',
			'gets',	'getting',	'given',	'gives',	'go', 'goes',	'going',	'gone',	'got',	'gotten',
			'greetings',	'had',	'hadn\'t',	'happens',	'hardly', 'has',	'hasn\'t',	'have',	'haven\'t',	'having',
			'he',	'he\'s',	'hello',	'help',	'hence', 'her',	'here',	'here\'s',	'hereafter',	'hereby',
			'herein',	'hereupon',	'hers',	'herself',	'hi', 'him',	'himself',	'his',	'hither',	'hopefully',
			'how',	'howbeit',	'however',	'i\'d',	'i\'ll', 'i\'m',	'i\'ve',	'ie',	'if',	'ignored',
			'immediate',	'in',	'inasmuch',	'inc',	'indeed', 'indicate',	'indicated',	'indicates',	'inner',	'insofar',
			'instead',	'into',	'inward',	'is',	'isn\'t', 'it',	'it\'d',	'it\'ll',	'iti\'s',	'its',
			'itself',	'just',	'keep',	'keeps',	'kept', 'know',	'known',	'knows',	'last',	'lately',
			'later',	'latter',	'latterly',	'least',	'less', 'lest',	'let',	'let\'s',	'like',	'liked',
			'likely',	'little',	'look',	'looking',	'looks', 'ltd',	'mainly',	'many',	'may',	'maybe',
			'me',	'mean',	'meanwhile',	'merely',	'might', 'more',	'moreover',	'most',	'mostly',	'much',
			'must',	'my',	'myself',	'name',	'namely', 'nd',	'near',	'nearly',	'necessary',	'need',
			'needs',	'neither',	'never',	'nevertheless',	'new', 'next',	'nine',	'no',	'nobody',	'non',
			'none',	'noone',	'nor',	'normally',	'not', 'nothing',	'novel',	'now',	'nowhere',	'obviously',
			'of',	'off',	'often',	'oh',	'ok', 'okay',	'old',	'on',	'once',	'one',
			'ones',	'only',	'onto',	'or',	'other', 'others',	'otherwise',	'ought',	'our',	'ours',
			'ourselves',	'out',	'outside',	'over',	'overall', 'own',	'particular',	'particularly',	'per',	'perhaps',
			'placed',	'please',	'plus',	'possible',	'presumably', 'probably',	'provides',	'que',	'quite',	'qv',
			'rather',	'rd',	're',	'really',	'reasonably', 'regarding',	'regardless',	'regards',	'relatively',	'respectively',
			'right',	'said',	'same',	'saw',	'say', 'saying',	'says',	'second',	'secondly',	'see',
			'seeing',	'seem',	'seemed',	'seeming',	'seems', 'seen',	'self',	'selves',	'sensible',	'sent',
			'serious',	'seriously',	'seven',	'several',	'shall', 'she',	'should',	'shouldn\'t',	'since',	'six',
			'so',	'some',	'somebody',	'somehow',	'someone', 'something',	'sometime',	'sometimes',	'somewhat',	'somewhere',
			'soon',	'sorry',	'specified',	'specify',	'specifying', 'still',	'sub',	'such',	'sup',	'sure',
			't\'s',	'take',	'taken',	'tell',	'tends', 'th',	'than',	'thank',	'thanks',	'thanx',
			'that',	'that\'s',	'thats',	'the',	'their', 'theirs',	'them',	'themselves',	'then',	'thence',
			'there',	'there\'s',	'thereafter',	'thereby',	'therefore', 'therein',	'theres',	'thereupon',	'these',	'they',
			'they\'d',	'they\'ll',	'they\'re',	'they\'ve',	'think', 'third',	'this',	'thorough',	'thoroughly',	'those',
			'though',	'three',	'through',	'throughout',	'thru', 'thus',	'to',	'together',	'too',	'took',
			'toward',	'towards',	'tried',	'tries',	'truly', 'try',	'trying',	'twice',	'two',	'un',
			'under',	'unfortunately',	'unless',	'unlikely',	'until', 'unto',	'up',	'upon',	'us',	'use',
			'used',	'useful',	'uses',	'using',	'usually', 'value',	'various',	'very',	'via',	'viz',
			'vs',	'want',	'wants',	'was',	'wasn\'t', 'way',	'we',	'we\'d',	'we\'ll',	'we\'re',
			'we\'ve',	'welcome',	'well',	'went',	'were', 'weren\'t',	'what',	'what\'s',	'whatever',	'when',
			'whence',	'whenever',	'where',	'where\'s',	'whereafter', 'whereas',	'whereby',	'wherein',	'whereupon',	'wherever',
			'whether',	'which',	'while',	'whither',	'who', 'who\'s',	'whoever',	'whole',	'whom',	'whose',
			'why',	'will',	'willing',	'wish',	'with', 'within',	'without',	'won\'t',	'wonder',	'would',
			'wouldn\'t',	'yes',	'yet',	'you',	'you\'d', 'you\'ll',	'you\'re',	'you\'ve',	'your',	'yours',
			'yourself',	'yourselves',	'zero',
		);
	}
}
