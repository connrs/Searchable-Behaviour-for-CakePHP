<?php
/**
 * SearchableHelper 
 * 
 * @uses AppHelper
 * @package 
 * @version $id$
 * @copyright See licence
 * @author Paul Connolley {Connrs] <shunuk@gmail.com> 
 * @license MIT Licence <http://www.opensource.org/licenses/mit-license.php>
 */
class SearchableHelper extends Helper {
	var $helpers = array("Html");

	/**
	 * viewLink 
	 * 
	 * @param mixed $result 
	 * @access public
	 * @return void
	 */
	function viewLink($result) {
		$model = $result["SearchIndex"]["model"];
		$controller = Inflector::tableize($model);
		$action = "view";
		$displayField = $result["SearchIndex"]["displayField"];
		$id = $result["SearchIndex"]["association_key"];
		if ($displayField == 'id') {
			$title = $model;
		} else {
			$title = $result[$model][$displayField];
		}
		return $this->Html->link($title, array('controller'=>$controller, 'action'=>$action, $id));
	}
}

