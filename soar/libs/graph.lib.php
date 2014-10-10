<?php
require_once dirname(__FILE__)."/graph/custom.graph.php";
define('ITEM' , 0);
define('PLATFORM' , 1);
define('SAVE_VAL' , 1);
class Graph extends CustomGraph {
	public static function SaveGraphValue($graph_name , array $items , $spec_time = null) {
		/*
		 * $items = array(
		 * 				array('item_1','value'),
		 * 				array('item_2','value'),
		 * 				...
		 * 				)
		 */
		$gitems = self::GetGraphItems($graph_name);
		if ($gitems == null) {
			return false;
		}
		$gitems_idx_by_item = array();
		foreach($gitems as $item) {
			$gitems_idx_by_item[$item[0]] = $item;
		}
		$items_idx = array();
		$is_single_set = false;
		$error_detected = false;
		if (count($items) == 2) {
			$i_cnt = 0;
			foreach($items as $t_item) {
				if (is_array($item)) {
					if ($i_cnt) {
						$error_detected = true;
					}
					break;
				} else {
					++$i_cnt;
				}
			}
			if ($i_cnt == 2) {
				$is_single_set = true;
			}
		}
		if ($error_detected) {
			return false;
		}
		if ($is_single_set) {
			$items_idx[$items[0]] = $items;
		} else {
			foreach ($items as $t_item) {
				if (!is_array($t_item) || count($t_item) != 2) {
					return false;
				}
				foreach($t_item as $element) {
					if (!is_string($element) && !is_numeric($element)) {
						return false;
					}
				}
				$items_idx[$t_item[0]] = $t_item;
			}
		}
		foreach($items_idx as $idx => $item) {
			if (!isset($gitems_idx_by_item[$idx]) ) {
				continue;
			}
			$colloctor = self::GetCollector($gitems_idx_by_item[$idx][PLATFORM]);
			if ($colloctor == null) {
				return false;
			}
			if ($collector = "ZabbixCollector") {
				if(ZabbixCollector::SaveStatistic($graph_name."-".$item[ITEM], $item[SAVE_VAL] , $spec_time) === false) {
					return false;
				}
			} else if ($collector = "YunluCollector") {
				if(YunluCollector::SaveStatistic($graph_name."-".$item[ITEM], $item[SAVE_VAL] , $spec_time) === false) {
					return false;
				}
			}
		}
		return true;
	}
	
	public static function GetGraphValue($graph_name , $start_time , $end_time) {
		$items = self::GetGraphItems($graph_name);
		if ($items == null) {
			return null;
		}
		$platform_items = array();
		foreach ($items as $item) {
			if (!isset($platform_items[$item[PLATFORM]])) {
				$platform_items[$item[PLATFORM]] = array();
			}
			$platform_items[$item[PLATFORM]][] = $graph_name."-".$item[ITEM];
		}
		$retvals = array();
		foreach ($platform_items as $key => $t_items) {
			$collector = self::GetCollector($key);
			if ($collector == null) {
				continue;
			}
			$tmp = $collector::GetStatisticValue($t_items, $start_time, $end_time);
			if (is_array($tmp)) {
				$retvals = array_merge($tmp , $retvals);
			}
		}
		if (!count($retvals)) {
			$retvals = null;
		}
		return $retvals;
	}
	
	public static function GetGraphValueSum($graph_name , $start_time , $end_time) {
		$items = self::GetGraphItems($graph_name);
		if ($items == null) {
			return null;
		}
		$platform_items = array();
		foreach ($items as $item) {
			if (!isset($platform_items[$item[PLATFORM]])) {
				$platform_items[$item[PLATFORM]] = array();
			}
			$platform_items[$item[PLATFORM]][] = $graph_name."-".$item[ITEM];
		}
		$retvals = array();
		foreach ($platform_items as $key => $t_items) {
			$collector = self::GetCollector($key);
			if ($collector == null) {
				continue;
			}
			$tmp = $collector::GetStatisticSum($t_items, $start_time, $end_time);
			if (is_array($tmp)) {
				$retvals = array_merge($tmp , $retvals);
			}
		}
		if (!count($retvals))
			$retvals = null;
		return $retvals;
	}
	
	public static function GetGraphValueAver($graph_name , $start_time , $end_time) {
		$items = self::GetGraphItems($graph_name);
		if ($items == null) {
			return null;
		}
		$platform_items = array();
		foreach ($items as $item) {
			if (!isset($platform_items[$item[PLATFORM]])) {
				$platform_items[$item[PLATFORM]] = array();
			}
			$platform_items[$item[PLATFORM]][] = $graph_name."-".$item[ITEM];
		}
		$retvals = array();
		foreach ($platform_items as $key => $t_items) {
			$collector = self::GetCollector($key);
			if ($collector == null) {
				continue;
			}
			$tmp = $collector::GetStatisticAver($t_items, $start_time, $end_time);
			if (is_array($tmp)) {
				$retvals = array_merge($tmp , $retvals);
			}
		}
		if (!count($retvals))
			$retvals = null;
		return $retvals;
	}
}

















