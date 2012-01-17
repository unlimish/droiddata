<?php
/*
Plugin Name: WP FDroid
Plugin URI: http://f-droid.org/repository
Description: An FDroid repository browser
Author: Ciaran Gultnieks
Version: 0.01
Author URI: http://ciarang.com

Revision history
0.01 - 2010-12-04: Initial development version

*/

include('android-permissions.php');

class FDroid
{

	// Our text domain, for internationalisation
	private $textdom='wp-fdroid';

	private $site_path;

	// Constructor
	function FDroid() {
		// Add filters etc here!
		add_shortcode('fdroidrepo',array($this, 'do_shortcode'));
		add_filter('query_vars',array($this, 'queryvars'));
		$this->inited=false;
		$this->site_path=getenv('DOCUMENT_ROOT');
	}


	// Register additional query variables. (Handler for the 'query_vars' filter)
	function queryvars($qvars) {
		$qvars[]='fdfilter';
		$qvars[]='fdid';
		$qvars[]='fdpage';
		$qvars[]='fdstyle';
		return $qvars;
	}


	// Lazy initialise. All non-trivial members should call this before doing anything else.
	function lazyinit() {
		if(!$this->inited) {
			load_plugin_textdomain($this->textdom, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));

			$this->inited=true;
		}
	}

	// Gets a required query parameter by name.
	function getrequiredparam($name) {
		global $wp_query;
		if(!isset($wp_query->query_vars[$name]))
			wp_die("Missing parameter ".$name,"Error");
		return $wp_query->query_vars[$name];
	}

	// Handler for the 'fdroidrepo' shortcode.
	//  $attribs - shortcode attributes
	//  $content - optional content enclosed between the starting and
	//			 ending shortcode
	// Returns the generated content.
	function do_shortcode($attribs,$content=null) {
		global $wp_query,$wp_rewrite;
		$this->lazyinit();

		// Init local query vars
		foreach($this->queryvars(array()) as $qv) {
			if(array_key_exists($qv,$wp_query->query_vars)) {
				$query_vars[$qv] = $wp_query->query_vars[$qv];
			} else {
				$query_vars[$qv] = null;
			}
		}

		// Santiy check query vars
		if(!isset($query_vars['fdpage']) || !is_numeric($query_vars['fdpage']) || $query_vars['fdpage'] <= 0) {
			$query_vars['fdpage'] = 1;
		}

		$out = '';

		if(isset($attribs['search']) && $query_vars['fdfilter']===null) {
			$query_vars['fdfilter'] = '';
		}

		if($query_vars['fdid']!==null) {
			$out.=$this->get_app($query_vars);
		} else {
			if($query_vars['fdfilter'] !== null)
				$out.=$this->show_search($query_vars);

			$out.=$this->get_apps($query_vars);
		}

		return $out;
	}


	function get_app($query_vars) {
		$permissions_object = new AndroidPermissions($this->site_path.'/repo/AndroidManifest.xml', $this->site_path.'/repo/strings.xml', $this->site_path.'/repo/android-permissions.cache');
		$permissions_data = $permissions_object->get_permissions_array();

		// Get app data
		$xml = simplexml_load_file($this->site_path.'/repo/index.xml');
		foreach($xml->children() as $app) {

			$attrs=$app->attributes();
			if($attrs['id']==$query_vars['fdid']) {
				$apks=array();;
				foreach($app->children() as $el) {
					switch($el->getName()) {
						case "name":
							$name=$el;
							break;
						case "icon":
							$icon=$el;
							break;
						case "summary":
							$summary=$el;
							break;
						case "description":
							$desc=$el;
							break;
						case "license":
							$license=$el;
							break;
						case "source":
							$source=$el;
							break;
						case "tracker":
							$issues=$el;
							break;
						case "donate":
							$donate=$el;
							break;
						case "web":
							$web=$el;
							break;
						case "package":
							$thisapk=array();
							foreach($el->children() as $pel) {
								switch($pel->getName()) {
								case "version":
									$thisapk['version']=$pel;
									break;
								case "vercode":
									$thisapk['vercode']=$pel;
									break;
								case "apkname":
									$thisapk['apkname']=$pel;
									break;
								case "srcname":
									$thisapk['srcname']=$pel;
									break;
								case "hash":
									$thisapk['hash']=$pel;
									break;
								case "size":
									$thisapk['size']=$pel;
									break;
								case "sdkver":
									$thisapk['sdkver']=$pel;
									break;
								case "permissions":
									$thisapk['permissions']=$pel;
									break;
								}
							}
							$apks[]=$thisapk;

					}
				}
				
				// Generate app diff data
				foreach(array_reverse($apks, true) as $key=>$apk) {
					if(isset($previous)) {
							$apks[$key]['diff']['size'] = $apk['size']-$previous['size'];
					}
					$previous = $apk;
				}

				// Output app information
				$out='<div id="appheader">';
				$out.='<div style="float:left;padding-right:10px;"><img src="http://f-droid.org/repo/icons/'.$icon.'" width=48></div>';
				$out.='<p><span style="font-size:20px">'.$name."</span>";
				$out.="<br>".$summary."</p>";
				$out.="</div>";

				$out.="<p>".$desc."</p>";

				$out.="<p><b>License:</b> ".$license."</p>";

				$out.="<p>";
				if(strlen($web)>0)
					$out.='<b>Website:</b> <a href="'.$web.'">'.$web.'</a><br />';
				if(strlen($issues)>0)
					$out.='<b>Issue Tracker:</b> <a href="'.$issues.'">'.$issues.'</a><br />';
				if(strlen($source)>0)
					$out.='<b>Source Code:</b> <a href="'.$source.'">'.$source.'</a><br />';
				if($donate && strlen($donate)>0)
					$out.='<b>Donate:</b> <a href="'.$donate.'">'.$donate.'</a><br />';
				$out.="</p>";

				$out.='<script type="text/javascript">';
				$out.='function showHidePermissions(id) {';
				$out.='  if(document.getElementById(id).style.display==\'none\')';
				$out.='	document.getElementById(id).style.display=\'block\';';
				$out.='  else';
				$out.='	document.getElementById(id).style.display=\'none\';';
				$out.='  return false;';
				$out.='}';
				$out.='</script>';

				$out.="<h3>Packages</h3>";
				$i=0;
				foreach($apks as $apk) {
					$out.="<p><b>Version ".$apk['version']."</b><br />";
					$out.='<a href="http://f-droid.org/repo/'.$apk['apkname'].'">download apk</a> ';
					$out.=$this->human_readable_size($apk['size']);
					$diffSize = $apk['diff']['size'];
					if(abs($diffSize) > 500) {
						$out.=' <span style="color:#AAAAAA;">(';
						$out.=$diffSize>0?'+':'';
						$out.=$this->human_readable_size($diffSize, 1).')</span>';
					}
					if($apk['srcname']) {
						$out.='<br /><a href="http://f-droid.org/repo/'.$apk['srcname'].'">source tarball</a> ';
						$out.=$this->human_readable_size(filesize($this->site_path.'/repo/'.$apk['srcname']));
					}

					if(isset($apk['permissions'])) {
						/*if($i==0)
							$divStyleDisplay='block';
						else*/
							$divStyleDisplay='none';
						$divId='permissions'.$i;
						$out.='<br /><a href="javascript:void(0);" onClick="showHidePermissions(\''.$divId.'\');">view permissions</a><br/>';
						$out.='<div style="display:'.$divStyleDisplay.';" id="'.$divId.'">';
						$permissions = explode(',',$apk['permissions']);
						usort($permissions,
							function ($a, $b) use (&$permissions_data) {

								$aProtectionLevel = $permissions_data['permission'][$a]['protectionLevel'];
								$bProtectionLevel = $permissions_data['permission'][$b]['protectionLevel'];

								if($aProtectionLevel != $bProtectionLevel) {
									if(strlen($aProtectionLevel)==0) return 1;
									if(strlen($bProtectionLevel)==0) return -1;

									return strcmp($aProtectionLevel, $bProtectionLevel);
								}

								$aGroup = $permissions_data['permission'][$a]['permissionGroup'];
								$bGroup = $permissions_data['permission'][$b]['permissionGroup'];

								if($aGroup != $bGroup) {
									return strcmp($aGroup, $bGroup);
								}

								return strcmp($a, $b);
							}
						);

						$permission_group_last = '';
						foreach($permissions as $permission) {
							$permission_group = $permissions_data['permission'][$permission]['permissionGroup'];
							if($permission_group != $permission_group_last) {
								$permission_group_label = $permissions_data['permission-group'][$permission_group]['label'];
								if($permission_group_label=='') $permission_group_label = 'Extra/Custom';
								$out.='<strong>'.strtoupper($permission_group_label).'</strong><br/>';
								$permission_group_last = $permission_group;
							}

							$out.=$this->get_permission_protection_level_icon($permissions_data['permission'][$permission]['protectionLevel']).' ';
							$out.='<strong>'.$permissions_data['permission'][$permission]['label'].'</strong> [<code>'.$permission.'</code>]<br/>';
							if($permissions_data['permission'][$permission]['description']) $out.=$permissions_data['permission'][$permission]['description'].'<br/>';
							//$out.=$permissions_data['permission'][$permission]['comment'].'<br/>';
							$out.='<br/>';
						}
						$out.='</div>';
					}
					else {
						$out.='<br /><span style="color:#999999;">no permissions</span><br />';
					}

					$out.='</p>';
					$i++;
				}

				$out.='<hr><p><a href="'.makelink($query_vars,array('fdid'=>null)).'">Index</a></p>';

				return $out;
			}
		}
		return "<p>Application not found</p>";
	}

	private function get_permission_protection_level_icon($protection_level) {
		if($protection_level=='dangerous') {
			return '<span style="color:#DD9900;font-size:150%;">&#x26a0;</span>';
		}
		elseif($protection_level=='normal') {
			return '<span style="color:#6666FF;font-size:110%;">&#x24D8;</span>';
		}
		else {
			return '<span style="color:#33AA33;font-size:130%;">&#x2699;</span>';
		}
	}
	
	private function human_readable_size($size, $minDiv=0) {
		$si_prefix = array('bytes','kB','MB');
		$div = 1000;
		
		for($i=0;(abs($size) > $div && $i < count($si_prefix)) || $i<$minDiv;$i++) {
			$size /= $div;
		}

		return round($size,max(0,$i-1)).' '.$si_prefix[$i];
	}

	function get_apps($query_vars) {

		$xml = simplexml_load_file($this->site_path."/repo/index.xml");
		$matches = $this->show_apps($xml,$query_vars,$numpages);

		$out='';

		if(($query_vars['fdfilter']===null || $query_vars['fdfilter']!='') && $numpages>0)
		{
			$out.='<div style="float:left;">';
			if($query_vars['fdfilter']===null)
				$out.="All applications";
			else
				$out.='Applications matching "'.$query_vars['fdfilter'].'"';
			$out.="</div>";

			$out.='<div style="float:right;">';
				$out.='<a href="'.makelink($query_vars, array('fdstyle'=>'list','fdpage'=>'1')).'">List</a> | ';
				$out.='<a href="'.makelink($query_vars, array('fdstyle'=>'grid','fdpage'=>'1')).'">Grid</a>';
			$out.='</div>';

			$out.='<br break="all"/>';
		}

		if($numpages>0) {
			$out.=$matches;

			$out.='<hr><p>';

			$out.='<div style="width:20%; float:left; text-align:left;">';
			$out.=' Page '.$query_vars['fdpage'].' of '.$numpages.' ';
			$out.='</div>';

			$out.='<div style="width:60%; float:left; text-align:center;">';
			if($numpages>1) {
				for($i=1;$i<=$numpages;$i++) {
					if($i == $query_vars['fdpage']) {
						$out.='<b>'.$i.'</b>';
					} else {
						$out.='<a href="'.makelink($query_vars, array('fdpage'=>$i)).'">';
						$out.=$i;
						$out.='</a>';
					}
					$out.=' ';
				}
				$out.=' ';
			}
			$out.='</div>';

			$out.='<div style="width:20%; float:left; text-align:right;">';
			if($query_vars['fdpage']!=$numpages) {
				$out.='<a href="'.makelink($query_vars, array('fdpage'=>($query_vars['fdpage']+1))).'">next&gt;</a> ';
			}
			$out.='</div>';

			$out.='</p>';
		} else if($query_vars['fdfilter']!='') {
			$out.='<p>No matches</p>';
		}

		return $out;
	}


	function show_search($query_vars) {

		$out='';
		$out.='<form name="searchform" action="" method="get">';
		$out.='<p><input name="fdfilter" type="text" value="'.$query_vars['fdfilter'].'" size="30"> ';
		$out.='<input type="submit" value="Search"></p>';

		$out.='<input type="hidden" name="page_id" value="'.get_query_var('page_id').'">';
		foreach($query_vars as $name => $value) {
			if($value !== null && $name != 'fdfilter')
				$out.='<input type="hidden" name="'.$name.'" value="'.$value.'">';
		}

		$out.='</form>'."\n";

		return $out;
	}


	function show_apps($xml,$query_vars,&$numpages) {

		$skipped=0;
		$got=0;
		$total=0;

		if($query_vars['fdstyle']=='grid') {
			$outputter = new FDOutGrid();
		} else {
			$outputter = new FDOutList();
		}

		$out = "";

		$out.=$outputter->outputStart();

		foreach($xml->children() as $app) {

			if($app->getName() == 'repo') continue;
			$appinfo['attrs']=$app->attributes();
			$appinfo['id']=$appinfo['attrs']['id'];
			foreach($app->children() as $el) {
				switch($el->getName()) {
					case "name":
						$appinfo['name']=$el;
						break;
					case "icon":
						$appinfo['icon']=$el;
						break;
					case "summary":
						$appinfo['summary']=$el;
						break;
					case "description":
						$appinfo['description']=$el;
						break;
					case "license":
						$appinfo['license']=$el;
						break;
				}
			}

			if($query_vars['fdfilter']===null || $query_vars['fdfilter']!='' && (stristr($appinfo['name'],$query_vars['fdfilter']) || stristr($appinfo['summary'],$query_vars['fdfilter']) || stristr($appinfo['description'],$query_vars['fdfilter']))) {
				if($skipped<($query_vars['fdpage']-1)*$outputter->perpage) {
					$skipped++;
				} else if($got<$outputter->perpage) {
					$out.=$outputter->outputEntry($query_vars, $appinfo);
					$got++;
				}
				$total++;
			}

		}

		$out.=$outputter->outputEnd();

		$numpages = ceil((float)$total/$outputter->perpage);

		return $out;
	}
}

// Class to output app entries in a detailed list format
class FDOutList
{
	var $perpage=30;

	function FDOutList() {
	}

	function outputStart() {
		return '';
	}

	function outputEntry($query_vars, $appinfo) {
		$out="";
		$out.="<hr>\n";
		$out.='<div id="appheader">';

		$out.='<div style="float:left;padding-right:10px;"><img src="http://f-droid.org/repo/icons/'.$appinfo['icon'].'" style="width:48px;"></div>';

		$out.='<div style="float:right;">';
		$out.='<p><a href="';
		$out.=makelink($query_vars, array('fdid'=>$appinfo['id']));
		$out.='">Details...</a>';
		$out.="</p>";
		$out.="</div>\n";

		$out.='<p><span style="font-size:20px">'.$appinfo['name']."</span>";
		$out.="<br>".$appinfo['summary']."</p>\n";

		$out.="</div>\n";

		return $out;
	}

	function outputEnd() {
		return '';
	}
}

// Class to output app entries in a compact grid format
class FDOutGrid
{
	var $perpage=80;

	var $itemCount = 0;

	function FDOutGrid() {
	}

	function outputStart() {
		return "\n".'<table border="0" width="100%"><tr>'."\n";
	}

	function outputEntry($query_vars, $appinfo) {
		$link=makelink($query_vars, array('fdid'=>$appinfo['id']));

		$out='';

		if($this->itemCount%4 == 0 && $this->itemCount > 0)
		{
			$out.='</tr><tr>'."\n";
		}

		$out.='<td align="center" valign="top" style="background-color:#F8F8F8;">';
		$out.='<p>';
		$out.='<div id="appheader" style="text-align:center;width:110px;">';

		$out.='<a href="'.$link.'" style="border-bottom-style:none;">';
		$out.='<img src="http://f-droid.org/repo/icons/'.$appinfo['icon'].'" style="width:48px;border-width:0;padding-top:5px;padding-bottom:5px;"><br/>';
		$out.=$appinfo['name'].'<br/>';
		$out.='</a>';

		$out.="</div>";
		$out.='</p>';
		$out.="</td>\n";

		$this->itemCount++;
		return $out;
	}

	function outputEnd() {
		return '</tr></table>'."\n";
	}
}

// Make a link to this page, with the current query vars attached and desired params added/modified
function makelink($query_vars, $params=array()) {
	$link=get_permalink();
	$vars=linkify(array_merge($query_vars, $params));
	if(strlen($vars)==0)
		return $link;
	if(strpos($link,'?')===false)
		$link.='?';
	else
		$link.='&';
	return $link.$vars;
}

// Return the key value pairs in http-get-parameter format as a string
function linkify($vars) {
	$retvar = '';
	foreach($vars as $k => $v) {
		if($k!==null && $v!==null && $v!='')
			$retvar .= $k.'='.$v.'&';
	}
	return substr($retvar,0,-1);
}


$wp_fdroid = new FDroid();


?>
