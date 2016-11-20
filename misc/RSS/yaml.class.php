<?php
// https://github.com/mustangostang/spyc/
class Spyc {
  const REMPTY = "\0\0\0\0\0";
  public $setting_dump_force_quotes = false;
  public $setting_use_syck_is_possible = false;
  private $_dumpIndent;
  private $_dumpWordWrap;
  private $_containsGroupAnchor = false;
  private $_containsGroupAlias = false;
  private $path;
  private $result;
  private $LiteralPlaceHolder = '___YAML_Literal_Block___';
  private $SavedGroups = array();
  private $indent;
  private $delayedPath = array();
  public $_nodeId;
  public static function YAMLLoadString($input) {
    $Spyc = new Spyc;
    return $Spyc->__loadString($input);
  }
  private function _doLiteralBlock($value,$indent) {
    if ($value === "\n") return '\n';
    if (strpos($value, "\n") === false && strpos($value, "'") === false) {
      return sprintf ("'%s'", $value);
    }
    if (strpos($value, "\n") === false && strpos($value, '"') === false) {
      return sprintf ('"%s"', $value);
    }
    $exploded = explode("\n",$value);
    $newValue = '|';
    if (isset($exploded[0]) && ($exploded[0] == "|" || $exploded[0] == "|-" || $exploded[0] == ">")) {
        $newValue = $exploded[0];
        unset($exploded[0]);
    }
    $indent += $this->_dumpIndent;
    $spaces   = str_repeat(' ',$indent);
    foreach ($exploded as $line) {
      $line = trim($line);
      if (strpos($line, '"') === 0 && strrpos($line, '"') == (strlen($line)-1) || strpos($line, "'") === 0 && strrpos($line, "'") == (strlen($line)-1)) {
        $line = substr($line, 1, -1);
      }
      $newValue .= "\n" . $spaces . ($line);
    }
    return $newValue;
  }
  private function _doFolding($value,$indent) {
    if ($this->_dumpWordWrap !== 0 && is_string ($value) && strlen($value) > $this->_dumpWordWrap) {
      $indent += $this->_dumpIndent;
      $indent = str_repeat(' ',$indent);
      $wrapped = wordwrap($value,$this->_dumpWordWrap,"\n$indent");
      $value   = ">\n".$indent.$wrapped;
    } else {
      if ($this->setting_dump_force_quotes && is_string ($value) && $value !== self::REMPTY)
        $value = '"' . $value . '"';
      if (is_numeric($value) && is_string($value))
        $value = '"' . $value . '"';
    }
    return $value;
  }
  private function isTrueWord($value) {
    $words = self::getTranslations(array('true', 'on', 'yes', 'y'));
    return in_array($value, $words, true);
  }
  private function isFalseWord($value) {
    $words = self::getTranslations(array('false', 'off', 'no', 'n'));
    return in_array($value, $words, true);
  }
  private function isNullWord($value) {
    $words = self::getTranslations(array('null', '~'));
    return in_array($value, $words, true);
  }
  private function isTranslationWord($value) {
    return (
      self::isTrueWord($value)  ||
      self::isFalseWord($value) ||
      self::isNullWord($value)
    );
  }
  private function coerceValue(&$value) {
    if (self::isTrueWord($value)) {
      $value = true;
    } else if (self::isFalseWord($value)) {
      $value = false;
    } else if (self::isNullWord($value)) {
      $value = null;
    }
  }
  private static function getTranslations(array $words) {
    $result = array();
    foreach ($words as $i) {
      $result = array_merge($result, array(ucfirst($i), strtoupper($i), strtolower($i)));
    }
    return $result;
  }
  private function __loadString($input) {
    $Source = $this->loadFromString($input);
    return $this->loadWithSource($Source);
  }
  private function loadWithSource($Source) {
    if (empty ($Source)) return array();
    if ($this->setting_use_syck_is_possible && function_exists ('syck_load')) {
      $array = syck_load (implode ("\n", $Source));
      return is_array($array) ? $array : array();
    }
    $this->path = array();
    $this->result = array();
    $cnt = count($Source);
    for ($i = 0; $i < $cnt; $i++) {
      $line = $Source[$i];
      $this->indent = strlen($line) - strlen(ltrim($line));
      $tempPath = $this->getParentPathByIndent($this->indent);
      $line = self::stripIndent($line, $this->indent);
      if (self::isComment($line)) continue;
      if (self::isEmpty($line)) continue;
      $this->path = $tempPath;
      $literalBlockStyle = self::startsLiteralBlock($line);
      if ($literalBlockStyle) {
        $line = rtrim ($line, $literalBlockStyle . " \n");
        $literalBlock = '';
        $line .= ' '.$this->LiteralPlaceHolder;
        $literal_block_indent = strlen($Source[$i+1]) - strlen(ltrim($Source[$i+1]));
        while (++$i < $cnt && $this->literalBlockContinues($Source[$i], $this->indent)) {
          $literalBlock = $this->addLiteralLine($literalBlock, $Source[$i], $literalBlockStyle, $literal_block_indent);
        }
        $i--;
      }
      if (strpos ($line, '#')) {
          $line = preg_replace('/\s*#([^"\']+)$/','',$line);
      }
      while (++$i < $cnt && self::greedilyNeedNextLine($line)) {
        $line = rtrim ($line, " \n\t\r") . ' ' . ltrim ($Source[$i], " \t");
      }
      $i--;
      $lineArray = $this->_parseLine($line);
      if ($literalBlockStyle)
        $lineArray = $this->revertLiteralPlaceHolder ($lineArray, $literalBlock);
      $this->addArray($lineArray, $this->indent);
      foreach ($this->delayedPath as $indent => $delayedPath)
        $this->path[$indent] = $delayedPath;
      $this->delayedPath = array();
    }
    return $this->result;
  }
  private function loadFromString ($input) {
    $lines = explode("\n",$input);
    foreach ($lines as $k => $_) {
      $lines[$k] = rtrim ($_, "\r");
    }
    return $lines;
  }
  private function _parseLine($line) {
    if (!$line) return array();
    $line = trim($line);
    if (!$line) return array();
    $array = array();
    $group = $this->nodeContainsGroup($line);
    if ($group) {
      $this->addGroup($line, $group);
      $line = $this->stripGroup ($line, $group);
    }
    if ($this->startsMappedSequence($line))
      return $this->returnMappedSequence($line);
    if ($this->startsMappedValue($line))
      return $this->returnMappedValue($line);
    if ($this->isArrayElement($line))
     return $this->returnArrayElement($line);
    if ($this->isPlainArray($line))
     return $this->returnPlainArray($line);
    return $this->returnKeyValuePair($line);
  }
  private function _toType($value) {
    if ($value === '') return "";
    $first_character = $value[0];
    $last_character = substr($value, -1, 1);
    $is_quoted = false;
    do {
      if (!$value) break;
      if ($first_character != '"' && $first_character != "'") break;
      if ($last_character != '"' && $last_character != "'") break;
      $is_quoted = true;
    } while (0);
    if ($is_quoted) {
      $value = str_replace('\n', "\n", $value);
      if ($first_character == "'")
        return strtr(substr ($value, 1, -1), array ('\'\'' => '\'', '\\\''=> '\''));
      return strtr(substr ($value, 1, -1), array ('\\"' => '"', '\\\''=> '\''));
    }
    if (strpos($value, ' #') !== false && !$is_quoted)
      $value = preg_replace('/\s+#(.+)$/','',$value);
    if ($first_character == '[' && $last_character == ']') {
      $innerValue = trim(substr ($value, 1, -1));
      if ($innerValue === '') return array();
      $explode = $this->_inlineEscape($innerValue);
      $value  = array();
      foreach ($explode as $v) {
        $value[] = $this->_toType($v);
      }
      return $value;
    }
    if (strpos($value,': ')!==false && $first_character != '{') {
      $array = explode(': ',$value);
      $key   = trim($array[0]);
      array_shift($array);
      $value = trim(implode(': ',$array));
      $value = $this->_toType($value);
      return array($key => $value);
    }
    if ($first_character == '{' && $last_character == '}') {
      $innerValue = trim(substr ($value, 1, -1));
      if ($innerValue === '') return array();
      $explode = $this->_inlineEscape($innerValue);
      $array = array();
      foreach ($explode as $v) {
        $SubArr = $this->_toType($v);
        if (empty($SubArr)) continue;
        if (is_array ($SubArr)) {
          $array[key($SubArr)] = $SubArr[key($SubArr)]; continue;
        }
        $array[] = $SubArr;
      }
      return $array;
    }
    if ($value == 'null' || $value == 'NULL' || $value == 'Null' || $value == '' || $value == '~') {
      return null;
    }
    if ( is_numeric($value) && preg_match ('/^(-|)[1-9]+[0-9]*$/', $value) ){
      $intvalue = (int)$value;
      if ($intvalue != PHP_INT_MAX && $intvalue != ~PHP_INT_MAX)
        $value = $intvalue;
      return $value;
    }
    if (is_numeric($value) && preg_match('/^0[xX][0-9a-fA-F]+$/', $value)) {
      return hexdec($value);
    }
    $this->coerceValue($value);
    if (is_numeric($value)) {
      if ($value === '0') return 0;
      if (rtrim ($value, 0) === $value)
        $value = (float)$value;
      return $value;
    }
    return $value;
  }
  private function _inlineEscape($inline) {
    $seqs = array();
    $maps = array();
    $saved_strings = array();
    $saved_empties = array();
    $regex = '/("")|(\'\')/';
    if (preg_match_all($regex,$inline,$strings)) {
      $saved_empties = $strings[0];
      $inline  = preg_replace($regex,'YAMLEmpty',$inline);
    }
    unset($regex);
    $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
    if (preg_match_all($regex,$inline,$strings)) {
      $saved_strings = $strings[0];
      $inline  = preg_replace($regex,'YAMLString',$inline);
    }
    unset($regex);
    $i = 0;
    do {
    while (preg_match('/\[([^{}\[\]]+)\]/U',$inline,$matchseqs)) {
      $seqs[] = $matchseqs[0];
      $inline = preg_replace('/\[([^{}\[\]]+)\]/U', ('YAMLSeq' . (count($seqs) - 1) . 's'), $inline, 1);
    }
    while (preg_match('/{([^\[\]{}]+)}/U',$inline,$matchmaps)) {
      $maps[] = $matchmaps[0];
      $inline = preg_replace('/{([^\[\]{}]+)}/U', ('YAMLMap' . (count($maps) - 1) . 's'), $inline, 1);
    }
    if ($i++ >= 10) break;
    } while (strpos ($inline, '[') !== false || strpos ($inline, '{') !== false);
    $explode = explode(',',$inline);
    $explode = array_map('trim', $explode);
    $stringi = 0; $i = 0;
    while (1) {
    if (!empty($seqs)) {
      foreach ($explode as $key => $value) {
        if (strpos($value,'YAMLSeq') !== false) {
          foreach ($seqs as $seqk => $seq) {
            $explode[$key] = str_replace(('YAMLSeq'.$seqk.'s'),$seq,$value);
            $value = $explode[$key];
          }
        }
      }
    }
    if (!empty($maps)) {
      foreach ($explode as $key => $value) {
        if (strpos($value,'YAMLMap') !== false) {
          foreach ($maps as $mapk => $map) {
            $explode[$key] = str_replace(('YAMLMap'.$mapk.'s'), $map, $value);
            $value = $explode[$key];
          }
        }
      }
    }
    if (!empty($saved_strings)) {
      foreach ($explode as $key => $value) {
        while (strpos($value,'YAMLString') !== false) {
          $explode[$key] = preg_replace('/YAMLString/',$saved_strings[$stringi],$value, 1);
          unset($saved_strings[$stringi]);
          ++$stringi;
          $value = $explode[$key];
        }
      }
    }
    if (!empty($saved_empties)) {
      foreach ($explode as $key => $value) {
        while (strpos($value,'YAMLEmpty') !== false) {
          $explode[$key] = preg_replace('/YAMLEmpty/', '', $value, 1);
          $value = $explode[$key];
        }
      }
    }
    $finished = true;
    foreach ($explode as $key => $value) {
      if (strpos($value,'YAMLSeq') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLMap') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLString') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLEmpty') !== false) {
        $finished = false; break;
      }
    }
    if ($finished) break;
    $i++;
    if ($i > 10)
      break;
    }
    return $explode;
  }
  private function literalBlockContinues ($line, $lineIndent) {
    if (!trim($line)) return true;
    if (strlen($line) - strlen(ltrim($line)) > $lineIndent) return true;
    return false;
  }
  private function referenceContentsByAlias ($alias) {
    do {
      if (!isset($this->SavedGroups[$alias])) { echo "Bad group name: $alias."; break; }
      $groupPath = $this->SavedGroups[$alias];
      $value = $this->result;
      foreach ($groupPath as $k) {
        $value = $value[$k];
      }
    } while (false);
    return $value;
  }
  private function addArrayInline ($array, $indent) {
      $CommonGroupPath = $this->path;
      if (empty ($array)) return false;
      foreach ($array as $k => $_) {
        $this->addArray(array($k => $_), $indent);
        $this->path = $CommonGroupPath;
      }
      return true;
  }
  private function addArray ($incoming_data, $incoming_indent) {
    if (count ($incoming_data) > 1)
      return $this->addArrayInline ($incoming_data, $incoming_indent);
    $key = key ($incoming_data);
    $value = isset($incoming_data[$key]) ? $incoming_data[$key] : null;
    if ($key === '__!YAMLZero') $key = '0';
    if ($incoming_indent == 0 && !$this->_containsGroupAlias && !$this->_containsGroupAnchor) { // Shortcut for root-level values.
      if ($key || $key === '' || $key === '0') {
        $this->result[$key] = $value;
      } else {
        $this->result[] = $value; end ($this->result); $key = key ($this->result);
      }
      $this->path[$incoming_indent] = $key;
      return;
    }
    $history = array();
    $history[] = $_arr = $this->result;
    foreach ($this->path as $k) {
      $history[] = $_arr = $_arr[$k];
    }
    if ($this->_containsGroupAlias) {
      $value = $this->referenceContentsByAlias($this->_containsGroupAlias);
      $this->_containsGroupAlias = false;
    }
    if (is_string($key) && $key == '<<') {
      if (!is_array ($_arr)) { $_arr = array (); }
      $_arr = array_merge ($_arr, $value);
    } else if ($key || $key === '' || $key === '0') {
      if (!is_array ($_arr))
        $_arr = array ($key=>$value);
      else
        $_arr[$key] = $value;
    } else {
      if (!is_array ($_arr)) { $_arr = array ($value); $key = 0; }
      else { $_arr[] = $value; end ($_arr); $key = key ($_arr); }
    }
    $reverse_path = array_reverse($this->path);
    $reverse_history = array_reverse ($history);
    $reverse_history[0] = $_arr;
    $cnt = count($reverse_history) - 1;
    for ($i = 0; $i < $cnt; $i++) {
      $reverse_history[$i+1][$reverse_path[$i]] = $reverse_history[$i];
    }
    $this->result = $reverse_history[$cnt];
    $this->path[$incoming_indent] = $key;
    if ($this->_containsGroupAnchor) {
      $this->SavedGroups[$this->_containsGroupAnchor] = $this->path;
      if (is_array ($value)) {
        $k = key ($value);
        if (!is_int ($k)) {
          $this->SavedGroups[$this->_containsGroupAnchor][$incoming_indent + 2] = $k;
        }
      }
      $this->_containsGroupAnchor = false;
    }
  }
  private static function startsLiteralBlock ($line) {
    $lastChar = substr (trim($line), -1);
    if ($lastChar != '>' && $lastChar != '|') return false;
    if ($lastChar == '|') return $lastChar;
    if (preg_match ('#<.*?>$#', $line)) return false;
    return $lastChar;
  }
  private static function greedilyNeedNextLine($line) {
    $line = trim ($line);
    if (!strlen($line)) return false;
    if (substr ($line, -1, 1) == ']') return false;
    if ($line[0] == '[') return true;
    if (preg_match ('#^[^:]+?:\s*\[#', $line)) return true;
    return false;
  }
  private function addLiteralLine ($literalBlock, $line, $literalBlockStyle, $indent = -1) {
    $line = self::stripIndent($line, $indent);
    if ($literalBlockStyle !== '|') {
        $line = self::stripIndent($line);
    }
    $line = rtrim ($line, "\r\n\t ") . "\n";
    if ($literalBlockStyle == '|') {
      return $literalBlock . $line;
    }
    if (strlen($line) == 0)
      return rtrim($literalBlock, ' ') . "\n";
    if ($line == "\n" && $literalBlockStyle == '>') {
      return rtrim ($literalBlock, " \t") . "\n";
    }
    if ($line != "\n")
      $line = trim ($line, "\r\n ") . " ";
    return $literalBlock . $line;
  }
   function revertLiteralPlaceHolder ($lineArray, $literalBlock) {
     foreach ($lineArray as $k => $_) {
      if (is_array($_))
        $lineArray[$k] = $this->revertLiteralPlaceHolder ($_, $literalBlock);
      else if (substr($_, -1 * strlen ($this->LiteralPlaceHolder)) == $this->LiteralPlaceHolder)
	       $lineArray[$k] = rtrim ($literalBlock, " \r\n");
     }
     return $lineArray;
   }
  private static function stripIndent ($line, $indent = -1) {
    if ($indent == -1) $indent = strlen($line) - strlen(ltrim($line));
    return substr ($line, $indent);
  }
  private function getParentPathByIndent ($indent) {
    if ($indent == 0) return array();
    $linePath = $this->path;
    do {
      end($linePath); $lastIndentInParentPath = key($linePath);
      if ($indent <= $lastIndentInParentPath) array_pop ($linePath);
    } while ($indent <= $lastIndentInParentPath);
    return $linePath;
  }
  private function clearBiggerPathValues ($indent) {
    if ($indent == 0) $this->path = array();
    if (empty ($this->path)) return true;
    foreach ($this->path as $k => $_) {
      if ($k > $indent) unset ($this->path[$k]);
    }
    return true;
  }
  private static function isComment ($line) {
    if (!$line) return false;
    if ($line[0] == '#') return true;
    if (trim($line, " \r\n\t") == '---') return true;
    return false;
  }
  private static function isEmpty ($line) {
    return (trim ($line) === '');
  }
  private function isArrayElement ($line) {
    if (!$line || !is_scalar($line)) return false;
    if (substr($line, 0, 2) != '- ') return false;
    if (strlen ($line) > 3)
      if (substr($line,0,3) == '---') return false;
    return true;
  }
  private function isHashElement ($line) {
    return strpos($line, ':');
  }
  private function isLiteral ($line) {
    if ($this->isArrayElement($line)) return false;
    if ($this->isHashElement($line)) return false;
    return true;
  }
  private static function unquote ($value) {
    if (!$value) return $value;
    if (!is_string($value)) return $value;
    if ($value[0] == '\'') return trim ($value, '\'');
    if ($value[0] == '"') return trim ($value, '"');
    return $value;
  }
  private function startsMappedSequence ($line) {
    return (substr($line, 0, 2) == '- ' && substr ($line, -1, 1) == ':');
  }
  private function returnMappedSequence ($line) {
    $array = array();
    $key         = self::unquote(trim(substr($line,1,-1)));
    $array[$key] = array();
    $this->delayedPath = array(strpos ($line, $key) + $this->indent => $key);
    return array($array);
  }
  private function checkKeysInValue($value) {
    if (strchr('[{"\'', $value[0]) === false) {
      if (strchr($value, ': ') !== false) {
          throw new Exception('Too many keys: '.$value);
      }
    }
  }
  private function returnMappedValue ($line) {
    $this->checkKeysInValue($line);
    $array = array();
    $key         = self::unquote (trim(substr($line,0,-1)));
    $array[$key] = '';
    return $array;
  }
  private function startsMappedValue ($line) {
    return (substr ($line, -1, 1) == ':');
  }
  private function isPlainArray ($line) {
    return ($line[0] == '[' && substr ($line, -1, 1) == ']');
  }
  private function returnPlainArray ($line) {
    return $this->_toType($line);
  }
  private function returnKeyValuePair ($line) {
    $array = array();
    $key = '';
    if (strpos ($line, ': ')) {
      if (($line[0] == '"' || $line[0] == "'") && preg_match('/^(["\'](.*)["\'](\s)*:)/',$line,$matches)) {
        $value = trim(str_replace($matches[1],'',$line));
        $key   = $matches[2];
      } else {
        $explode = explode(': ', $line);
        $key     = trim(array_shift($explode));
        $value   = trim(implode(': ', $explode));
        $this->checkKeysInValue($value);
      }
      $value = $this->_toType($value);
      if ($key === '0') $key = '__!YAMLZero';
      $array[$key] = $value;
    } else {
      $array = array ($line);
    }
    return $array;
  }
  private function returnArrayElement ($line) {
     if (strlen($line) <= 1) return array(array()); 
     $array = array();
     $value   = trim(substr($line,1));
     $value   = $this->_toType($value);
     if ($this->isArrayElement($value)) {
       $value = $this->returnArrayElement($value);
     }
     $array[] = $value;
     return $array;
  }
  private function nodeContainsGroup ($line) {
    $symbolsForReference = 'A-z0-9_\-';
    if (strpos($line, '&') === false && strpos($line, '*') === false) return false;
    if ($line[0] == '&' && preg_match('/^(&['.$symbolsForReference.']+)/', $line, $matches)) return $matches[1];
    if ($line[0] == '*' && preg_match('/^(\*['.$symbolsForReference.']+)/', $line, $matches)) return $matches[1];
    if (preg_match('/(&['.$symbolsForReference.']+)$/', $line, $matches)) return $matches[1];
    if (preg_match('/(\*['.$symbolsForReference.']+$)/', $line, $matches)) return $matches[1];
    if (preg_match ('#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $matches)) return $matches[1];
    return false;
  }
  private function addGroup ($line, $group) {
    if ($group[0] == '&') $this->_containsGroupAnchor = substr ($group, 1);
    if ($group[0] == '*') $this->_containsGroupAlias = substr ($group, 1);
  }
  private function stripGroup ($line, $group) {
    $line = trim(str_replace($group, '', $line));
    return $line;
  }
}