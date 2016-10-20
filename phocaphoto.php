<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @component Phoca Plugin
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
jimport( 'joomla.plugin.plugin' );

if (!JComponentHelper::isEnabled('com_phocaphoto', true)) {
	$app = JFactory::getApplication();
	$app->enqueueMessage(JText::_('PLG_CONTENT_PHOCAPHOTO_ERROR'), JText::_('PLG_CONTENT_PHOCAPHOTO_COMPONENT_NOT_INSTALLED'), 'error');
	return;
}
class plgContentPhocaPhoto extends JPlugin
{	
	var $_plugin_number	= 0;
	
	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	public function _setPluginNumber() {
		$this->_plugin_number = (int)$this->_plugin_number + 1;
	}
	
	public function onContentPrepare($context, &$article, &$params, $page = 0) {
	
		$db 			= JFactory::getDBO();
		$document		= JFactory::getDocument();
		//$component	= 'com_phocaphoto';
		//$paramsC		= JComponentHelper::getParams($component) ;
		//$param		= (int)$this->params->get( 'medium_image_width', 100 );
		$columns_cats 	= $this->params->get('columns_cats', 3);
	
		// Start Plugin
		$regex_one		= '/({phocaphoto\s*)(.*?)(})/si';
		$regex_all		= '/{phocaphoto\s*.*?}/si';
		$matches 		= array();
		$count_matches	= preg_match_all($regex_all,$article->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
		
		
	// Start if count_matches
	if ($count_matches != 0) {

		for($i = 0; $i < $count_matches; $i++) {
			
			$o = '';
			$this->_setPluginNumber();
			
			// Plugin variables
			$view 	= '';
			$id		= 0;
			$max	= 0;

			// Get plugin parameters
			$phocaphoto	= $matches[0][$i][0];
			preg_match($regex_one,$phocaphoto,$phocaphoto_parts);
			$parts			= explode("|", $phocaphoto_parts[2]);
			$values_replace = array ("/^'/", "/'$/", "/^&#39;/", "/&#39;$/", "/<br \/>/");

			foreach($parts as $key => $value) {
				
				$values = explode("=", $value, 2);
				
				foreach ($values_replace as $key2 => $values2) {
					$values = preg_replace($values2, '', $values);
				}
				
				// Get plugin parameters from article
					 if($values[0]=='view')				{$view					= $values[1];}
				else if($values[0]=='id')				{$id					= $values[1];}
				else if($values[0]=='max')				{$max					= $values[1];}
			}
			
			$limit = '';
			$where = '';
			if ($view == 'category') {
				if ($max > 0) {
					$limit 	= ' LIMIT 0,'.(int)$max;
				}
				$where = ' AND a.catid = '.(int)$id;
			} else if ($view == 'image') {
				$where = ' AND a.id ='.(int)$id;
			}
			
			
			$query = 'SELECT a.id, a.catid, a.title, a.alias, a.filename, a.description, a.extm, a.exts, a.extw, a.exth, a.extid, a.extl, a.exto'
				. ' FROM #__phocagallery AS a'
				. ' WHERE a.published = 1'
				. ' AND a.approved = 1'
				. $where
				. ' ORDER BY a.ordering'
				. $limit;
				
				$db->setQuery($query);
				$images = $db->loadObjectList();
				
				if (!empty($images)) {
					
					require_once( JPATH_ADMINISTRATOR.'/components/com_phocaphoto/helpers/phocaphoto.php' );
					$path = PhocaPhotoHelper::getPath();
					
					if ((int)$this->_plugin_number < 2) {
						JHtml::_('jquery.framework', false);
						JHTML::stylesheet( 'media/com_phocaphoto/js/prettyphoto/css/prettyPhoto.css' );
						$document->addScript(JURI::root(true).'/media/com_phocaphoto/js/prettyphoto/js/jquery.prettyPhoto.js');
						
						$js = "\n". 'jQuery(document).ready(function(){
							jQuery("a[rel^=\'prettyPhoto\']").prettyPhoto({'."\n";
						$js .= '  \'social_tools\': 0'."\n";
						$js .= '  });
						});'."\n";
						$document->addScriptDeclaration($js);
					}
					
					$nc = (int)$columns_cats;
					$nw = 3;
					if ($nc > 0) {
						$nw = 12/$nc;//1,2,3,4,6,12
					}
				
					$count = 0;
					if (count($images) > 1) {
						$count = 1;
					}
					
					$o .= '<div class="row">';
					
					foreach ($images as $k => $v) {
						
						if ($count == 1) {
							$o .= '<div class="col-sm-6 col-md-'.$nw.'">';
							$o .= '<div class="thumbnail ph-thumbnail">';
						} else {
							$o .= '<div class="ph-thumbnail-one">';
						}
						
						$image = PhocaPhotoHelper::getThumbnailName($path, $v->filename, 'medium');
						
						
						if ($v->extm != '') {
							$imageM	= $v->extm;
							$imageL	= $v->extl;
						} else {
							$imageMO = PhocaPhotoHelper::getThumbnailName($path, $v->filename, 'medium');
							if (isset($imageMO->rel) && $imageMO->rel != '') {
								$imageM = JURI::base(false) .$imageMO->rel;
							}
							$imageLO = PhocaPhotoHelper::getThumbnailName($path, $v->filename, 'large');
							if (isset($imageLO->rel) && $imageLO->rel != '') {
								$imageL = JURI::base(false) .$imageLO->rel;
							}
						}
						
						if ($imageL != '') {
							if ($count == 1) {
								$o .= '<a href="'.$imageL.'" rel="prettyPhoto[\'pp_gal_plugin'.(int)$this->_plugin_number.'\']">';
							} else {
								$o .= '<a href="'.$imageL.'" rel="prettyPhoto">';
							}
						}
						
						if ($imageM != '') {
							$o .= '<img src="'.$imageM.'" alt="" />';
						}
						
						if ($imageL != '') {
							$o .= '</a>';
							
						}
						
						$o .= '</div>'; // end thumbnail
						
						if ($count == 1) {
							$o .= '</div>'; // end column
						}
						
					}
					$o .= '</div>';// end row
				}
				
				$article->text = preg_replace($regex_all, $o, $article->text, 1);
			}
			return true;
		}
	}
}
?>