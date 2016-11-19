<?php
class RSS {
	public $old_file;
	public $db_file;
	
	public function __construct($old_file,$db_file) {
		require('yaml.class.php');
		date_default_timezone_set('GMT');
		$this->old_file = $old_file;
		$this->db_file = $db_file;
	}
	public function getYAML() {
		return spyc_load(file_get_contents("https://raw.githubusercontent.com/TrumpTracker/trumptracker.github.io/master/_data/data.yaml"));
	}
	public function stripQuotes($text) {
		$unquoted = preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $text);
		return $unquoted;
	} 
	public function newMessage($message,$title,$original) {
		if(file_exists($this->db_file)) {
			$old = json_decode(file_get_contents($this->db_file),true);
		}
		else {
			$old = array();
		}
		$old[] = array("content" => $this->stripQuotes($message),"time" => time(),"title" => $title,"author" => "TrumpTracker","uri" => "https://trumptracker.github.io","url" => $original['source']);
		$old = json_encode($old);
		file_put_contents($this->db_file,$old);
	}
	public function parsePoints($yaml) {
		$points = array();
		foreach($yaml['tabs'] as $t) {
			foreach($t['sections'] as $s) {
				foreach($s['points'] as $p) {
					$points[$p['text']] = $p;
				}
			}
		}
		return $points;
	}
	public function parseDifference($points) {
		if(file_exists($this->old_file)) {
			$yaml_old = file_get_contents($this->old_file);
			$yaml_old = json_decode($yaml_old,true);
			foreach($yaml_old as $y) {
				if(!isset($points[$y['text']])) {
					$this->newMessage("\"$y[text]\" has been removed from the list of policies.","Policy removed",$y);
				}
				elseif($points[$y['text']]['status'] != $y['status']) {
					if($points[$y['text']]['status'] == "notStarted") {
						$this->newMessage("\"$y[text]\" is not started anymore :(","Policy updated",$y);
					}
					elseif($points[$y['text']]['status'] == "inProgress") {
						$this->newMessage("\"$y[text]\" is now in progress!","Policy updated",$y);
					}
					elseif($points[$y['text']]['status'] == "achieved") {
						$this->newMessage("\"$y[text]\" has been achieved!","Policy updated",$y);
					}
					elseif($points[$y['text']]['status'] == "broken") {
						$this->newMessage("\"$y[text]\" has been broken :(","Policy updated",$y);
					}
				}
			}
			foreach($points as $p) {
				if(!isset($yaml_old[$p['text']])) {
					$this->newMessage("\"$p[text]\" has been added to the list of policies!","Policy added",$p);
				}
			}
		}
	}
	public function updateOld($points) {
		$points = json_encode($points);
		file_put_contents($this->old_file,$points);
	}
	public function getDB() {
		if(file_exists("db.json")) {
			$rss = file_get_contents("db.json");
			$rss = json_decode($rss,true);
			krsort($rss);
			return $rss;
		}
	}
}