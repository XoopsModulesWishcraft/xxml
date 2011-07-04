<?php
function obj2array($objects) {
	$ret = array();
	foreach($objects as $key => $value) {
		if (is_a($value, 'stdClass')) {
			$ret[$key] = obj2array((array)$value);
		} elseif (is_array($value)) {
			$ret[$key] = obj2array((array)$value);
		} else {
			$ret[$key] = $value;
		}
	}
	return $ret;
}


if (!function_exists("xortify_xml2array")) {
	function xortify_xml2array($contents, $get_attributes=1, $priority = 'tag') { 
	    if(!$contents) return array(); 
	
	    if(!function_exists('xml_parser_create')) { 
	        return array(); 
	    } 
	
	    //Get the XML parser of PHP - PHP must have this module for the parser to work
	     $parser = xml_parser_create(''); 
	    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	     xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
	    xml_parse_into_struct($parser, trim($contents), $xml_values); 
	    xml_parser_free($parser); 
	
	    if(!$xml_values) return;//Hmm... 
	
	    //Initializations 
	    $xml_array = array(); 
	    $parents = array(); 
	    $opened_tags = array(); 
	    $arr = array(); 
	
	    $current = &$xml_array; //Refference 
	
	    //Go through the tags. 
	    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
	     foreach($xml_values as $data) { 
	        unset($attributes,$value);//Remove existing values, or there will be trouble
	 
	        //This command will extract these variables into the foreach scope 
	        // tag(string), type(string), level(int), attributes(array). 
	        extract($data);//We could use the array by itself, but this cooler. 
	
	        $result = array(); 
	        $attributes_data = array(); 
	         
	        if(isset($value)) { 
	            if($priority == 'tag') $result = $value; 
	            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
	         } 
	
	        //Set the attributes too. 
	        if(isset($attributes) and $get_attributes) { 
	            foreach($attributes as $attr => $val) { 
	                if($priority == 'tag') $attributes_data[$attr] = $val; 
	                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
	             } 
	        } 
	
	        //See tag status and do the needed. 
	        if($type == "open") {//The starting of the tag '<tag>' 
	            $parent[$level-1] = &$current; 
	            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
	                 $current[$tag] = $result; 
	                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
	                 $repeated_tag_index[$tag.'_'.$level] = 1; 
	
	                $current = &$current[$tag]; 
	
	            } else { //There was another element with the same tag name 
	
	                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
	                     $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
	                     $repeated_tag_index[$tag.'_'.$level]++; 
	                } else {//This section will make the value an array if multiple tags with the same name appear together
	                     $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
	                     $repeated_tag_index[$tag.'_'.$level] = 2; 
	                     
	                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
	                         $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
	                        unset($current[$tag.'_attr']); 
	                    } 
	
	                } 
	                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1; 
	                $current = &$current[$tag][$last_item_index]; 
	            } 
	
	        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
	            //See if the key is already taken. 
	            if(!isset($current[$tag])) { //New Key 
	                $current[$tag] = $result; 
	                $repeated_tag_index[$tag.'_'.$level] = 1; 
	                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
	 
	            } else { //If taken, put all things inside a list(array) 
	                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
	 
	                    // ...push the new element into that array. 
	                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
	                      
	                    if($priority == 'tag' and $get_attributes and $attributes_data) {
	                         $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
	                     } 
	                    $repeated_tag_index[$tag.'_'.$level]++; 
	
	                } else { //If it is not an array... 
	                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
	                     $repeated_tag_index[$tag.'_'.$level] = 1; 
	                    if($priority == 'tag' and $get_attributes) { 
	                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
	                              
	                            $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
	                            unset($current[$tag.'_attr']); 
	                        } 
	                         
	                        if($attributes_data) { 
	                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
	                         } 
	                    } 
	                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
	                 } 
	            } 
	
	        } elseif($type == 'close') { //End of tag '</tag>' 
	            $current = &$parent[$level-1]; 
	        } 
	    } 
	     
	    return($xml_array); 
	}
}  

function xortifyserver_toXML($array, $name='array', $standalone=TRUE, $beginning=TRUE, $nested = 0) 
{
		if ($beginning) {
			if ($standalone)
				header("content-type:text/xml;charset="._CHARSET);
			$output .= '<'.'?'.'xml version="1.0" encoding="'._CHARSET.'"'.'?'.'>' . "\n";    
			$output .= '<' . $name . '>' . "\n";
			$nested = 0;
		}    
		
		if (is_array($array)) {
			foreach ($array as $key=>$value) {
				$nested++;	
				if (is_array($value)) {
					$output .= str_repeat("\t", (1 * $nested)) . '<' . (is_string($key) ? $key : $name.'_' . $key) . '>' . "\n";
					$nested++;				
					$output .= xortify_toXML($value, $name, false, false, $nested++);
					$nested--;
					$output .= str_repeat("\t", (1 * $nested)) . '</' . (is_string($key) ? $key : $name.'_' . $key) . '>' . "\n";
				} else {
					if (strlen($value)>0) {
					$nested++;				
						$output .= str_repeat("\t", (1 * $nested)) . '  <' . (is_string($key) ? $key : $name.'_' . $key) . '>' . trim($value) . '</' . (is_string($key) ? $key : $name.'_' . $key) . '>' . "\n";
						$nested--;
					}
				}
				$nested--;
			}
		} elseif (strlen($array)>0) {
			$nested++; 
			$output .= str_repeat("\t", (1 * $nested)) . trim($array) ."\n";
			$nested--;
		}
			
		if ($beginning) {
			$output .= '</' . $name . '>';
			return $output;
		} else {
			return $output;
		}
} 




global $xoopsModuleConfig,$xoopsModule;
$ttlresult = array();

xoops_load("xoopscache");

require_once(XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->dirname().'/class/class.functions.php');
require_once('common.php');
$funct = new FunctionsHandler();

$FunctionDefine = array();
foreach($funct->GetServerExtensions() as $extension){
	global $xoopsDB;
	$sql = "SELECT count(*) rc FROM ".$xoopsDB->prefix('xml_plugins'). " where active = 1 and plugin_file = '".$extension."'";
	$ret = $xoopsDB->query($sql);
	$row = $xoopsDB->fetchArray($ret);
	if ($row['rc']==1){
		require_once(XOOPS_ROOT_PATH.'/modules/xxml/plugins/'. $extension);
		$FunctionDefine[] = substr( $extension,0,strlen( $extension)-4);
	}	
}

$FunctionDefine = array_unique($FunctionDefine);

foreach($FunctionDefine as $id => $func)  {
	if (!empty($_REQUEST[$func])) {
		$opfunc = $func;
		$xsd = $func.'_xsd';	
		$opxsd = $xsd();
		if (class_exists('SimpleXMLElement'))
			$opdata = obj2array(new SimpleXMLElement(str_replace('\\"', '"', $_REQUEST[$func])));
		elseif(!function_exists('xml_parser_create')) {
			$opdata = xortify_xml2array(str_replace('\\"', '"', $_REQUEST[$func]), true);
			$opdata = $opdata[$func];
		}
		
		if (!$result = XoopsCache::read('xxml_'.$opfunc.'_'.sha1(implode(':', $opdata)))) {

			$tmp=array();
			if (!empty($opfunc)) {
				$fields=0;
				foreach($opxsd['request'] as $ii => $request) {
					foreach($request['items']['data'] as $iu => $field)
					{
						if (!empty($field['items'])) {
							$tmp[$fields] = $opdata[$field['items']['objname']]		;
							$fields++;
						} elseif (!empty($field['name'])&&!empty($field['type'])) {
							switch($field['type']) {
							default:
							case "string":
								$tmp[$fields] = (string)$opdata[$field['name']];
								break;
							case "integer":
								$tmp[$fields] = (integer)$opdata[$field['name']];					
								break;
							}
							$fields++;				
						}
					}
				}
				
				switch($fields) {
				case 0:
					$result = $opfunc($ttlresult);
					break;
				case 1:
					$result = $opfunc($tmp[0], $ttlresult);
					break;
				case 2:
					$result = $opfunc($tmp[0], $tmp[1], $ttlresult);
					break;
				case 3:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $ttlresult);
					break;
				case 4:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $ttlresult);
					break;
				case 5:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $ttlresult);
					break;
				case 6:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $ttlresult);
					break;
				case 7:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $ttlresult);
					break;
				case 8:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $ttlresult);
					break;
				case 9:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $ttlresult);
					break;
				case 10:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $ttlresult);
					break;
				case 11:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $ttlresult);
					break;
				case 12:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $ttlresult);
					break;		
				case 13:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $ttlresult);
					break;		
				case 14:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $ttlresult);
					break;		
				case 15:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $ttlresult);
					break;		
				case 16:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $tmp[15], $ttlresult);
					break;		
				case 17:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $tmp[15], $tmp[16], $ttlresult);
					break;		
				case 18:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $tmp[15], $tmp[16], $tmp[17], $ttlresult);
					break;		
				case 19:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $tmp[15], $tmp[16], $tmp[17], $tmp[18], $ttlresult);
					break;		
				case 20:
					$result = $opfunc($tmp[0], $tmp[1], $tmp[2], $tmp[3], $tmp[4], $tmp[5], $tmp[6], $tmp[7], $tmp[8], $tmp[9], $tmp[10], $tmp[11], $tmp[12], $tmp[13], $tmp[14], $tmp[15], $tmp[16], $tmp[17], $tmp[18], $tmp[19], $ttlresult);
					break;		
				}
				XoopsCache::write('xxml_'.$opfunc.'_'.sha1(implode(':', $opdata)), $result, $GLOBALS['xoopsModuleConfig']['function_cache']);
			}
			$ttlresult = array_merge($ttlresult, $result);
			$elename .= (strlen($elename)>0)?'_'.$func:''.$func;
		} else {
			$ttlresult = array_merge($ttlresult, $result);
			$elename .= (strlen($elename)>0)?'_'.$func:''.$func;
		}
	}	
}
echo xortifyserver_toXML($ttlresult, $elename, true);
exit(0);
?>